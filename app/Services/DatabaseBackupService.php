<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Nightly SQL backup of the whole database.
 *
 * A backup is two files, both uploaded to one Cloudinary folder per day:
 *
 *   4ftdshm54xvhks/backup_<date>/schema_<date>.sql   the structure (SYSTEM_SCHEMA.sql)
 *   4ftdshm54xvhks/backup_<date>/seed_<date>.sql     every row of every table
 *
 * Restoring is running them in that order: the schema creates the tables, the
 * seed fills them. The folder therefore stands on its own, even if SYSTEM_SCHEMA.sql
 * is edited later — which is why the schema is copied into every backup rather
 * than assumed to exist on the machine doing the restore.
 *
 * The table list and each table's column list are read from SYSTEM_SCHEMA.sql
 * itself, so the dump follows the constant structure instead of a hand-kept
 * list that would drift away from it. File order is used for the INSERTs too,
 * and it is already parent-before-child, so the rows restore without tripping
 * foreign keys.
 *
 * Rows are read with `select=*` paged 1000 at a time — the same way
 * SYSTEM_SEED.sql was produced — through SupabaseService, so this works whether
 * the app is talking to Supabase over HTTP or to the local Postgres connection.
 */
class DatabaseBackupService
{
    /** Backup "midnight" is Tanzania midnight, whatever the server clock is set to. */
    public const TIMEZONE = 'Africa/Dar_es_Salaam';

    /** Cloudinary root folder that holds every day's backup folder. */
    public const CLOUD_ROOT_FOLDER = '4ftdshm54xvhks';

    /**
     * Where one day's backup folder lives, e.g. "4ftdshm54xvhks/backup_2026-10-29".
     */
    public static function folderFor(string $date): string
    {
        return self::CLOUD_ROOT_FOLDER . '/backup_' . $date;
    }

    /**
     * One of the two files a backup is made of: "schema" or "seed".
     */
    public static function fileName(string $date, string $kind): string
    {
        return $kind . '_' . $date . '.sql';
    }

    /**
     * The Cloudinary public_id of one file, which is also how it is deleted.
     */
    public static function publicIdFor(string $date, string $kind): string
    {
        return self::folderFor($date) . '/' . self::fileName($date, $kind);
    }

    /** PostgREST caps a response at 1000 rows unless told otherwise. */
    private const PAGE_SIZE = 1000;

    /** Rows per INSERT statement: big enough to be quick, small enough to stay readable. */
    private const ROWS_PER_INSERT = 200;

    /** A backup that needs more pages than this is a runaway, not a backup. */
    private const MAX_PAGES = 100000;

    private SupabaseService $supabase;

    private CloudinaryService $cloudinary;

    /** @var array<string, array{columns: list<string>, primary: ?string, primary_type: ?string}>|null */
    private ?array $structure = null;

    public function __construct(?SupabaseService $supabase = null, ?CloudinaryService $cloudinary = null)
    {
        $this->supabase = $supabase ?? new SupabaseService();
        $this->cloudinary = $cloudinary ?? new CloudinaryService();
    }

    /**
     * Take the backup and file it in Cloudinary.
     *
     * @return array{
     *     date: string,
     *     folder: string,
     *     files: array<string, array{url: string, bytes: int}>,
     *     tables: array<string, int>,
     *     rows: int,
     *     seconds: float
     * }
     */
    public function run(?Carbon $at = null): array
    {
        set_time_limit(0);

        $started = microtime(true);
        $at = ($at ?? now())->copy()->setTimezone(self::TIMEZONE);
        $date = $at->format('Y-m-d');
        $folder = self::folderFor($date);

        Log::info('Database backup started', ['date' => $date]);

        $seed = $this->buildSeed($date, $at);
        $schema = $this->schemaFile($date);

        $schemaName = self::fileName($date, 'schema');
        $seedName = self::fileName($date, 'seed');

        $schemaUrl = $this->upload($folder . '/' . $schemaName, $schema, 'text/plain');
        $seedUrl = $this->upload($folder . '/' . $seedName, $seed['sql'], 'text/plain');

        $result = [
            'date' => $date,
            'folder' => $folder,
            'files' => [
                $schemaName => ['url' => $schemaUrl, 'bytes' => strlen($schema)],
                $seedName => ['url' => $seedUrl, 'bytes' => strlen($seed['sql'])],
            ],
            'tables' => $seed['tables'],
            'rows' => $seed['rows'],
            'seconds' => round(microtime(true) - $started, 2),
        ];

        // Both files are safe in Cloudinary at this point, so the log row is
        // written after them and its failure cannot fail the backup.
        $this->record($result);

        Log::info('Database backup finished', [
            'date' => $date,
            'rows' => $seed['rows'],
            'seconds' => $result['seconds'],
            'folder' => $folder,
        ]);

        return $result;
    }

    /**
     * File this run in the backups table the Super Admin's page reads.
     *
     * One row per day: a re-run on the same night updates that day's row rather
     * than leaving two. A bookkeeping failure is logged and swallowed — the
     * files are already off the server, and reporting the backup as failed
     * because a row could not be written would be a lie.
     */
    private function record(array $result): void
    {
        $date = $result['date'];
        $timestamp = now();

        $row = [
            'backup_date' => $date,
            'folder' => $result['folder'],
            'schema_url' => $result['files'][self::fileName($date, 'schema')]['url'] ?? null,
            'schema_bytes' => $result['files'][self::fileName($date, 'schema')]['bytes'] ?? null,
            'seed_url' => $result['files'][self::fileName($date, 'seed')]['url'] ?? null,
            'seed_bytes' => $result['files'][self::fileName($date, 'seed')]['bytes'] ?? null,
            'tables_count' => count($result['tables']),
            'rows_count' => $result['rows'],
            'seconds' => $result['seconds'],
            'status' => 'complete',
            'updated_at' => $timestamp,
        ];

        try {
            $existing = $this->supabase->findOne('backups', ['backup_date' => 'eq.' . $date], 'id');

            if (isset($existing['id'])) {
                $this->supabase->update('backups', $row, ['id' => $existing['id']]);

                return;
            }

            $this->supabase->insert('backups', $row + ['created_at' => $timestamp]);
        } catch (\Throwable $e) {
            Log::warning('Backup was filed in Cloudinary but not recorded in the backups table', [
                'date' => $date,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * The date a backup belongs to right now, e.g. "2026-10-30".
     */
    public function today(): string
    {
        return now()->setTimezone(self::TIMEZONE)->format('Y-m-d');
    }

    /**
     * Build the seed file: every row of every table as INSERT statements.
     *
     * @return array{sql: string, tables: array<string, int>, rows: int}
     */
    public function buildSeed(string $date, ?Carbon $at = null): array
    {
        $at = ($at ?? now())->copy()->setTimezone(self::TIMEZONE);
        $tables = $this->tables();

        $sql = $this->seedHeader($date, $at, count($tables));

        $counts = [];
        $total = 0;

        foreach ($tables as $table => $definition) {
            $rows = $this->fetchRows($table, $definition);
            $counts[$table] = count($rows);
            $total += count($rows);

            $sql .= $this->insertStatements($table, $definition, $rows);
        }

        $sql .= $this->sequenceBlock($tables, $counts);
        $sql .= "\nCOMMIT;\n";

        return ['sql' => $sql, 'tables' => $counts, 'rows' => $total];
    }

    /**
     * The constant structure, copied into the backup folder with a note saying
     * which day's seed it belongs to.
     */
    public function schemaFile(string $date): string
    {
        $path = base_path('SYSTEM_SCHEMA.sql');

        if (! is_file($path)) {
            throw new RuntimeException('SYSTEM_SCHEMA.sql is missing from the project root, so a restore could not be made.');
        }

        $schema = (string) file_get_contents($path);

        return "-- WORLD CHOICE PERFUMES — schema_{$date}.sql\n"
            . "-- The constant database structure. Run this file first, then seed_{$date}.sql,\n"
            . "-- to rebuild the database exactly as it stood on {$date}.\n"
            . "-- Source: SYSTEM_SCHEMA.sql (unchanged).\n\n"
            . ltrim($schema);
    }

    /**
     * Table name => ordered columns and primary key, read from SYSTEM_SCHEMA.sql
     * in the order the CREATE TABLE statements appear (parents first).
     *
     * @return array<string, array{columns: list<string>, primary: ?string, primary_type: ?string}>
     */
    public function tables(): array
    {
        if ($this->structure !== null) {
            return $this->structure;
        }

        $path = base_path('SYSTEM_SCHEMA.sql');

        if (! is_file($path)) {
            throw new RuntimeException('SYSTEM_SCHEMA.sql is missing from the project root.');
        }

        $schema = (string) file_get_contents($path);

        preg_match_all(
            '/CREATE TABLE public\.([a-z0-9_]+)\s*\((.*?)\r?\n\);/s',
            $schema,
            $matches,
            PREG_SET_ORDER
        );

        $tables = [];

        foreach ($matches as $match) {
            $table = $match[1];
            $columns = [];
            $primary = null;
            $primaryType = null;

            foreach ($this->splitTopLevel($match[2]) as $line) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                $first = strtoupper(strtok($line, " \t\n\r(") ?: '');

                // UNIQUE (...), PRIMARY KEY (...), FOREIGN KEY ...: table-level
                // constraints, not columns.
                if (in_array($first, ['UNIQUE', 'PRIMARY', 'FOREIGN', 'CONSTRAINT', 'CHECK', 'EXCLUDE'], true)) {
                    continue;
                }

                if (preg_match('/^([a-z0-9_]+)\s+([A-Za-z].*)$/s', $line, $parts) !== 1) {
                    continue;
                }

                $columns[] = $parts[1];

                if ($primary === null && stripos($parts[2], 'PRIMARY KEY') !== false) {
                    $primary = $parts[1];
                    $primaryType = strtoupper(strtok($parts[2], " \t") ?: '');
                }
            }

            if ($columns !== []) {
                $tables[$table] = ['columns' => $columns, 'primary' => $primary, 'primary_type' => $primaryType];
            }
        }

        if ($tables === []) {
            throw new RuntimeException('No tables could be read from SYSTEM_SCHEMA.sql.');
        }

        return $this->structure = $tables;
    }

    /**
     * Every row of one table, paged so a big table is not silently truncated
     * at the first 1000 rows.
     *
     * @param  array{columns: list<string>, primary: ?string, primary_type: ?string}  $definition
     * @return list<array<string, mixed>>
     */
    private function fetchRows(string $table, array $definition): array
    {
        $rows = [];
        $offset = 0;

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $params = [
                'select' => '*',
                'limit' => self::PAGE_SIZE,
                'offset' => $offset,
            ];

            if ($definition['primary'] !== null) {
                $params['order'] = $definition['primary'] . '.asc';
            }

            $batch = $this->supabase->query($table, $params);

            if (! is_array($batch) || $batch === []) {
                break;
            }

            // A list of rows is the only shape we can use; anything else (an
            // error payload, a count wrapper) means stop rather than write junk.
            if (! array_is_list($batch)) {
                break;
            }

            foreach ($batch as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }

            if (count($batch) < self::PAGE_SIZE) {
                break;
            }

            $offset += self::PAGE_SIZE;
        }

        return $rows;
    }

    /**
     * @param  array{columns: list<string>, primary: ?string, primary_type: ?string}  $definition
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertStatements(string $table, array $definition, array $rows): string
    {
        if ($rows === []) {
            return "-- {$table} (0 rows)\n";
        }

        // The schema's order first, then any column the live table has that the
        // schema file does not. Dropping those would lose data without a word.
        $known = $definition['columns'];
        $extra = [];

        foreach ($rows as $row) {
            foreach (array_keys($row) as $column) {
                if (! in_array($column, $known, true) && ! in_array($column, $extra, true)) {
                    $extra[] = $column;
                }
            }
        }

        $columns = array_merge($known, $extra);
        $columnList = implode(', ', $columns);

        $sql = "-- {$table} (" . count($rows) . " rows)\n";

        foreach (array_chunk($rows, self::ROWS_PER_INSERT) as $chunk) {
            $values = [];

            foreach ($chunk as $row) {
                $cell = [];

                foreach ($columns as $column) {
                    $cell[] = $this->literal($row[$column] ?? null);
                }

                $values[] = '(' . implode(', ', $cell) . ')';
            }

            $sql .= "INSERT INTO public.{$table} ({$columnList}) VALUES\n"
                . implode(",\n", $values)
                . ";\n";
        }

        return $sql . "\n";
    }

    /**
     * After the rows are back, move every id sequence past the highest restored
     * id. Without this the first insert after a restore tries to reuse id 1 and
     * fails on the primary key.
     *
     * @param  array<string, array{columns: list<string>, primary: ?string, primary_type: ?string}>  $tables
     * @param  array<string, int>  $counts
     */
    private function sequenceBlock(array $tables, array $counts): string
    {
        $lines = [];

        foreach ($tables as $table => $definition) {
            // Only real serial columns have a sequence. sessions.id and
            // job_batches.id are VARCHAR primary keys: setval() on those would
            // abort the restore.
            $type = (string) ($definition['primary_type'] ?? '');

            if ($definition['primary'] !== 'id'
                || ! in_array($type, ['BIGSERIAL', 'SERIAL', 'SMALLSERIAL'], true)
                || ($counts[$table] ?? 0) === 0) {
                continue;
            }

            $lines[] = "SELECT setval(pg_get_serial_sequence('public.{$table}', 'id'), "
                . "(SELECT MAX(id) FROM public.{$table}), true);";
        }

        if ($lines === []) {
            return '';
        }

        return "\n-- advance sequences past the restored ids\n" . implode("\n", $lines) . "\n";
    }

    /**
     * One SQL value. Batches of text are always written as escape strings so a
     * backslash or a quote in a name or a body can never end the literal early.
     */
    private function literal(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (is_int($value) || is_float($value)) {
            return is_float($value) && ! is_finite($value) ? 'NULL' : (string) $value;
        }

        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $this->escape((string) $value);
    }

    private function escape(string $value): string
    {
        return "E'" . str_replace(['\\', "'"], ['\\\\', "''"], $value) . "'";
    }

    /**
     * Split a CREATE TABLE body on the commas that separate columns, ignoring
     * the ones inside NUMERIC(12, 2) or a quoted default.
     *
     * @return list<string>
     */
    private function splitTopLevel(string $body): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $inQuote = false;
        $length = strlen($body);

        for ($i = 0; $i < $length; $i++) {
            $char = $body[$i];

            if ($inQuote) {
                $current .= $char;

                if ($char === "'") {
                    $inQuote = false;
                }

                continue;
            }

            if ($char === "'") {
                $inQuote = true;
                $current .= $char;

                continue;
            }

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            }

            if ($char === ',' && $depth === 0) {
                $parts[] = $current;
                $current = '';

                continue;
            }

            $current .= $char;
        }

        if (trim($current) !== '') {
            $parts[] = $current;
        }

        return $parts;
    }

    private function seedHeader(string $date, Carbon $at, int $tableCount): string
    {
        $takenAt = $at->format('Y-m-d H:i:s');
        $tableNames = implode(', ', array_keys($this->tables()));

        return "-- ============================================================================\n"
            . "-- WORLD CHOICE PERFUMES — seed_{$date}.sql\n"
            . "-- Data snapshot taken automatically at {$takenAt} " . self::TIMEZONE . ".\n"
            . "--\n"
            . "-- Restore: run schema_{$date}.sql first (it creates the tables), then this file.\n"
            . "-- Tables pulled ({$tableCount}): {$tableNames}\n"
            . "-- ============================================================================\n\n"
            . "BEGIN;\n\n";
    }

    /**
     * Upload one file as a Cloudinary raw resource and return its URL.
     */
    private function upload(string $publicId, string $contents, string $contentType): string
    {
        $url = $this->cloudinary->uploadRaw($contents, $publicId, $contentType);

        if ($url === null) {
            throw new RuntimeException(
                'Cloudinary rejected ' . basename($publicId) . ': '
                . ($this->cloudinary->lastError() ?? 'no reason given')
            );
        }

        return $url;
    }
}
