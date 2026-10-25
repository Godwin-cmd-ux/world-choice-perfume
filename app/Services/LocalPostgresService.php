<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Local Postgres implementation of the data-access API the app was written
 * against (Supabase's PostgREST).
 *
 * Every call site in the app speaks the PostgREST dialect: `'select' =>
 * '*, items:sale_items(quantity,total)'`, filters such as `'id' => 'in.(1,2)'`
 * or `'created_at' => 'gte.2026-09-01'`, and `'order' => 'created_at.desc'`.
 * Rather than rewrite 80+ files, this class translates that dialect into SQL
 * and runs it against the configured Laravel database connection.
 *
 * Rows come back shaped the way `$response->json()` used to shape them:
 * integers and booleans keep their types, json/jsonb columns are decoded to
 * arrays, timestamps are ISO 8601 strings, and embedded relations are nested
 * under the alias given in the select (`customer` => row, `items` => rows).
 *
 * Storage methods (the info@ mailbox attachments) map onto the local disk at
 * storage/app/supabase-storage/{bucket}/{path}.
 */
class LocalPostgresService
{
    /** Request-level cache, mirroring the old service's behaviour. */
    private static array $queryCache = [];

    /** table => [column => ['type' => ..., 'udt' => ...]] */
    private static array $tableColumns = [];

    /** "a|b" => foreign keys linking the two tables, in both directions. */
    private static array $foreignKeys = [];

    /** Query parameters that are not column filters. */
    private const RESERVED = [
        'select', 'order', 'limit', 'offset', 'range', 'group',
        'columns', 'count', 'on_conflict',
    ];

    private const STORAGE_ROOT = 'supabase-storage';

    private ?string $lastErrorMessage = null;

    public function lastErrorMessage(): ?string
    {
        return $this->lastErrorMessage;
    }

    public static function clearCache(): void
    {
        self::$queryCache = [];
    }

    // -----------------------------------------------------------------
    // Reads
    // -----------------------------------------------------------------

    /**
     * Query with PostgREST filters — cached within the same request.
     */
    public function query(string $table, array $params = []): array
    {
        $key = $this->cacheKey($table, $params);

        if (isset(self::$queryCache[$key])) {
            return self::$queryCache[$key];
        }

        try {
            $result = $this->fetch($table, $params);
        } catch (\Throwable $e) {
            $this->lastErrorMessage = $e->getMessage();
            Log::error("Local Postgres query failed for table {$table}: " . $e->getMessage());

            return [];
        }

        self::$queryCache[$key] = $result;

        return $result;
    }

    /**
     * Query without caching (for write-after-read patterns).
     */
    public function queryFresh(string $table, array $params = []): array
    {
        try {
            return $this->fetch($table, $params);
        } catch (\Throwable $e) {
            $this->lastErrorMessage = $e->getMessage();
            Log::error("Local Postgres queryFresh failed for table {$table}: " . $e->getMessage());

            return [];
        }
    }

    /**
     * Data plus the total number of rows the filters match, ignoring paging.
     */
    public function queryWithCount(string $table, array $params = []): array
    {
        $countParams = $params;
        unset($countParams['limit'], $countParams['offset'], $countParams['order']);

        return [
            'data' => $this->query($table, $params),
            'count' => $this->count($table, $countParams),
        ];
    }

    public function find(string $table, int|string $id, string $select = '*'): ?array
    {
        $results = $this->query($table, [
            'select' => $select,
            'id' => "eq.{$id}",
            'limit' => 1,
        ]);

        return $results[0] ?? null;
    }

    public function findOne(string $table, array $conditions, string $select = '*'): ?array
    {
        $params = ['select' => $select, 'limit' => 1];

        foreach ($conditions as $key => $value) {
            $params[$key] = $value;
        }

        $results = $this->query($table, $params);

        return $results[0] ?? null;
    }

    public function count(string $table, array $conditions = []): int
    {
        $key = $this->cacheKey($table, array_merge($conditions, ['_count' => true]));

        if (isset(self::$queryCache[$key])) {
            return self::$queryCache[$key];
        }

        [$where, $bindings] = $this->buildWhere($conditions);

        try {
            $rows = $this->run(
                'SELECT count(*) AS aggregate FROM ' . $this->ident($table)
                . ($where !== '' ? ' WHERE ' . $where : ''),
                $bindings
            );
        } catch (\Throwable $e) {
            $this->lastErrorMessage = $e->getMessage();
            Log::error("Local Postgres count failed for table {$table}: " . $e->getMessage());

            return 0;
        }

        $count = (int) ($rows[0]['aggregate'] ?? 0);
        self::$queryCache[$key] = $count;

        return $count;
    }

    /**
     * Probe whether a column exists. Used by the app to tolerate schema drift.
     */
    public function tableHasColumn(string $table, string $column): bool
    {
        return isset($this->columns($table)[$column]);
    }

    public function rpc(string $functionName, array $params = []): mixed
    {
        $args = [];
        $bindings = [];

        foreach ($params as $name => $value) {
            $args[] = $this->ident($name) . ' => ?';
            $bindings[] = $value;
        }

        try {
            return $this->run(
                'SELECT * FROM ' . $this->ident($functionName) . '(' . implode(', ', $args) . ')',
                $bindings
            );
        } catch (\Throwable $e) {
            $this->lastErrorMessage = $e->getMessage();
            Log::error("Local Postgres rpc failed for {$functionName}: " . $e->getMessage());

            return null;
        }
    }

    /**
     * Sequential on purpose, matching the previous implementation.
     */
    public function parallel(array $callbacks): array
    {
        $results = [];
        foreach ($callbacks as $key => $callback) {
            $results[$key] = $callback();
        }

        return $results;
    }

    // -----------------------------------------------------------------
    // Writes
    // -----------------------------------------------------------------

    public function insert(string $table, array|object $data): ?array
    {
        $rows = $this->insertRows($table, [$data]);

        return $rows[0] ?? null;
    }

    public function insertOrFail(string $table, array|object $data): array
    {
        $result = $this->insert($table, $data);

        if ($result === null) {
            throw new \RuntimeException("Failed to insert into {$table}");
        }

        return $result;
    }

    public function insertMany(string $table, array $records): ?array
    {
        if ($records === []) {
            return [];
        }

        return $this->insertRows($table, $records);
    }

    public function insertManyOrFail(string $table, array $records): array
    {
        $result = $this->insertMany($table, $records);

        if ($result === null) {
            throw new \RuntimeException("Failed to insert into {$table}");
        }

        return $result;
    }

    public function update(string $table, array|object $data, array $conditions): array
    {
        $data = $this->prepareWriteData($table, $data);

        if ($data === []) {
            return [];
        }

        [$where, $bindings] = $this->buildWhere($conditions);

        if ($where === '') {
            // The old PostgREST call would have applied the change to every
            // row; refuse instead of truncating a table by accident.
            $this->lastErrorMessage = 'Refusing to update without a condition.';
            Log::warning("Refusing to update {$table} without a condition.");

            return [];
        }

        $sets = [];
        $setBindings = [];
        foreach ($data as $column => $value) {
            $sets[] = $this->ident($column) . ' = ?';
            $setBindings[] = $this->toDb($table, $column, $value);
        }

        try {
            $rows = $this->run(
                'UPDATE ' . $this->ident($table) . ' SET ' . implode(', ', $sets)
                . ' WHERE ' . $where . ' RETURNING *',
                array_merge($setBindings, $bindings)
            );
        } catch (\Throwable $e) {
            $this->lastErrorMessage = $e->getMessage();
            Log::error("Local Postgres update failed for table {$table}: " . $e->getMessage());

            return [];
        }

        $this->invalidateCache($table);

        return array_map(fn ($row) => $this->cast($table, $row), $rows);
    }

    public function updateOrFail(string $table, array|object $data, array $conditions): array
    {
        $result = $this->update($table, $data, $conditions);

        if (empty($result)) {
            throw new \RuntimeException("Failed to update {$table}");
        }

        return $result;
    }

    public function delete(string $table, array $conditions): bool
    {
        [$where, $bindings] = $this->buildWhere($conditions);

        if ($where === '') {
            $this->lastErrorMessage = 'Refusing to delete without a condition.';
            Log::warning("Refusing to delete from {$table} without a condition.");

            return false;
        }

        try {
            $this->run(
                'DELETE FROM ' . $this->ident($table) . ' WHERE ' . $where,
                $bindings
            );
        } catch (\Throwable $e) {
            $this->lastErrorMessage = $e->getMessage();
            Log::error("Local Postgres delete failed for table {$table}: " . $e->getMessage());

            return false;
        }

        $this->invalidateCache($table);

        return true;
    }

    // -----------------------------------------------------------------
    // Storage (email attachments)
    // -----------------------------------------------------------------

    public function ensureStorageBucket(string $bucket): bool
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory($this->storageRoot($bucket));

        return $disk->directoryExists($this->storageRoot($bucket));
    }

    public function storagePut(string $bucket, string $path, string $contents, string $contentType): bool
    {
        return Storage::disk('local')->put($this->storageObjectPath($bucket, $path), $contents);
    }

    public function storageGet(string $bucket, string $path): ?string
    {
        $disk = Storage::disk('local');
        $object = $this->storageObjectPath($bucket, $path);

        if (! $disk->exists($object)) {
            return null;
        }

        $contents = $disk->get($object);

        return $contents === null ? null : (string) $contents;
    }

    public function storageDownloadTo(string $bucket, string $path, string $destination): bool
    {
        $disk = Storage::disk('local');
        $object = $this->storageObjectPath($bucket, $path);

        if (! $disk->exists($object)) {
            return false;
        }

        $stream = $disk->readStream($object);

        if ($stream === null) {
            return false;
        }

        $target = fopen($destination, 'wb');

        if ($target === false) {
            fclose($stream);

            return false;
        }

        stream_copy_to_stream($stream, $target);
        fclose($stream);
        fclose($target);

        return true;
    }

    public function storageDelete(string $bucket, string $path): bool
    {
        return Storage::disk('local')->delete($this->storageObjectPath($bucket, $path));
    }

    private function storageRoot(string $bucket): string
    {
        return self::STORAGE_ROOT . '/' . $bucket;
    }

    private function storageObjectPath(string $bucket, string $path): string
    {
        return $this->storageRoot($bucket) . '/' . ltrim($path, '/');
    }

    // -----------------------------------------------------------------
    // Select parsing
    // -----------------------------------------------------------------

    /**
     * Select the given rows and attach the relations requested in the select.
     */
    private function fetch(string $table, array $params): array
    {
        [$columns, $relations] = $this->parseSelect((string) ($params['select'] ?? '*'));

        return $this->selectRows($table, $columns, $relations, $params);
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $relations
     * @param  array<string, mixed>  $params  filters/order/limit/offset
     * @param  array<string, mixed>  $forced  extra filters (relation joins)
     * @param  array<int, string>  $extraColumns  columns fetched only to join
     */
    private function selectRows(string $table, array $columns, array $relations, array $params, array $forced = [], array $extraColumns = []): array
    {
        // Columns a caller needs only to resolve a relation (for example the
        // foreign key of the rows it is about to group) are fetched but never
        // returned, unless they were asked for. $extraColumns belong to the
        // caller and are stripped by it once the rows are grouped.
        $joinColumns = $this->joinColumnsFor($table, $relations);
        $hidden = array_values(array_unique(array_merge($joinColumns, $extraColumns)));
        $selectList = $this->selectList($columns, $hidden);

        [$where, $bindings] = $this->buildWhere(array_merge($params, $forced));

        $sql = 'SELECT ' . $selectList . ' FROM ' . $this->ident($table);

        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }

        $sql .= $this->orderClause($params);
        $sql .= $this->limitClause($params);

        $rows = array_map(fn ($row) => $this->cast($table, $row), $this->run($sql, $bindings));

        if ($relations !== [] && $rows !== []) {
            $this->attach($table, $rows, $relations);
        }

        // Join columns were added only so relations could be resolved; they
        // must not appear in the result unless they were asked for.
        if (! in_array('*', $columns, true) && $joinColumns !== []) {
            foreach ($rows as &$row) {
                foreach ($joinColumns as $column) {
                    if (! in_array($column, $columns, true)) {
                        unset($row[$column]);
                    }
                }
            }
            unset($row);
        }

        return $rows;
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, array<string, mixed>>}
     */
    private function parseSelect(string $select): array
    {
        $columns = [];
        $relations = [];

        foreach ($this->splitTopLevel($select) as $item) {
            if ($item === '') {
                continue;
            }

            if (str_contains($item, '(')) {
                $relation = $this->parseRelation($item);

                if ($relation !== null) {
                    $relations[] = $relation;
                }

                continue;
            }

            $columns[] = $item;
        }

        return [$columns === [] ? ['*'] : $columns, $relations];
    }

    /**
     * Parse `alias:table!hint(columns, nested:table(...))`.
     *
     * @return array<string, mixed>|null
     */
    private function parseRelation(string $item): ?array
    {
        $open = strpos($item, '(');
        $close = strrpos($item, ')');

        if ($open === false || $close === false || $close < $open) {
            return null;
        }

        $head = trim(substr($item, 0, $open));
        $inner = substr($item, $open + 1, $close - $open - 1);

        $alias = null;
        if (preg_match('/^([A-Za-z0-9_]+):(.+)$/', $head, $m)) {
            $alias = $m[1];
            $head = $m[2];
        }

        $hint = null;
        if (str_contains($head, '!')) {
            [$head, $hint] = explode('!', $head, 2);
        }

        [$columns, $relations] = $this->parseSelect($inner);

        return [
            'alias' => $alias ?? $head,
            'table' => $head,
            'hint' => $hint,
            'columns' => $columns,
            'relations' => $relations,
        ];
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $extra
     */
    private function selectList(array $columns, array $extra): string
    {
        if (in_array('*', $columns, true)) {
            return '*';
        }

        $wanted = array_values(array_unique(array_merge($columns, $extra)));

        return implode(', ', array_map(fn ($column) => $this->ident($column), $wanted));
    }

    /**
     * Local columns a relation needs in order to be joined.
     *
     * @param  array<int, array<string, mixed>>  $relations
     * @return array<int, string>
     */
    private function joinColumnsFor(string $table, array $relations): array
    {
        $columns = [];

        foreach ($relations as $relation) {
            $link = $this->resolveLink($table, (string) $relation['table'], $relation['hint']);

            if ($link === null) {
                continue;
            }

            $columns[] = $link['child_table'] === $relation['table']
                ? $link['parent_col']   // our row holds the id the children point at
                : $link['child_col'];   // our row holds this table's foreign key
        }

        return array_values(array_unique($columns));
    }

    // -----------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------

    /**
     * Nest related rows under their alias, the way PostgREST returns them:
     * an array when the related table holds the foreign key, a single row
     * (or null) when this table does.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array<string, mixed>>  $relations
     */
    private function attach(string $table, array &$rows, array $relations): void
    {
        foreach ($relations as $relation) {
            $related = (string) $relation['table'];
            $alias = (string) $relation['alias'];
            $link = $this->resolveLink($table, $related, $relation['hint']);

            if ($link === null) {
                foreach ($rows as &$row) {
                    $row[$alias] = [];
                }
                unset($row);

                continue;
            }

            $requested = $relation['columns'];
            $keepJoinColumn = in_array('*', $requested, true) || in_array(
                $link['child_table'] === $related ? $link['child_col'] : $link['parent_col'],
                $requested,
                true
            );

            if ($link['child_table'] === $related) {
                // One parent row owns many related rows.
                $localColumn = $link['parent_col'];
                $foreignColumn = $link['child_col'];

                $keys = $this->keyValues($rows, $localColumn);
                $grouped = [];

                if ($keys !== []) {
                    $children = $this->selectRows(
                        $related,
                        $requested,
                        $relation['relations'],
                        [],
                        [$foreignColumn => 'in.(' . implode(',', $keys) . ')'],
                        [$foreignColumn]
                    );

                    foreach ($children as $child) {
                        $grouped[(string) ($child[$foreignColumn] ?? '')][] = $child;
                    }
                }

                foreach ($rows as &$row) {
                    $key = (string) ($row[$localColumn] ?? '');
                    $matches = $grouped[$key] ?? [];

                    if (! $keepJoinColumn) {
                        foreach ($matches as &$match) {
                            unset($match[$foreignColumn]);
                        }
                        unset($match);
                    }

                    $row[$alias] = $matches;
                }
                unset($row);

                continue;
            }

            // This table holds the foreign key, so there is at most one match.
            $localColumn = $link['child_col'];
            $relatedColumn = $link['parent_col'];

            $keys = $this->keyValues($rows, $localColumn);
            $matches = [];

            if ($keys !== []) {
                $children = $this->selectRows(
                    $related,
                    $requested,
                    $relation['relations'],
                    [],
                    [$relatedColumn => 'in.(' . implode(',', $keys) . ')'],
                    [$relatedColumn]
                );

                foreach ($children as $child) {
                    $key = (string) ($child[$relatedColumn] ?? '');
                    $matches[$key] ??= $child;
                }
            }

            foreach ($rows as &$row) {
                $key = (string) ($row[$localColumn] ?? '');
                $match = $matches[$key] ?? null;

                if ($match !== null && ! $keepJoinColumn) {
                    unset($match[$relatedColumn]);
                }

                $row[$alias] = $match;
            }
            unset($row);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, int|string>
     */
    private function keyValues(array $rows, string $column): array
    {
        $keys = [];

        foreach ($rows as $row) {
            $value = $row[$column] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $keys[(string) $value] = $value;
        }

        return array_values($keys);
    }

    /**
     * Find the foreign key linking two tables, in either direction.
     *
     * @return array<string, string>|null
     */
    private function resolveLink(string $table, string $related, ?string $hint): ?array
    {
        $links = $this->foreignKeys($table, $related);

        if ($links === []) {
            return null;
        }

        if ($hint === null || $hint === '') {
            return $links[0];
        }

        foreach ($links as $link) {
            if ($link['constraint'] === $hint
                || $link['child_col'] === $hint
                || $link['parent_col'] === $hint) {
                return $link;
            }
        }

        return $links[0];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function foreignKeys(string $a, string $b): array
    {
        $key = $a . '|' . $b;

        if (isset(self::$foreignKeys[$key])) {
            return self::$foreignKeys[$key];
        }

        try {
            $rows = $this->run(
                "SELECT tc.constraint_name AS constraint,
                        tc.table_name AS child_table,
                        kcu.column_name AS child_col,
                        ccu.table_name AS parent_table,
                        ccu.column_name AS parent_col
                 FROM information_schema.table_constraints tc
                 JOIN information_schema.key_column_usage kcu
                   ON tc.constraint_name = kcu.constraint_name
                  AND tc.table_schema = kcu.table_schema
                 JOIN information_schema.constraint_column_usage ccu
                   ON ccu.constraint_name = tc.constraint_name
                  AND ccu.table_schema = tc.table_schema
                 WHERE tc.constraint_type = 'FOREIGN KEY'
                   AND tc.table_schema = 'public'
                   AND ((tc.table_name = ? AND ccu.table_name = ?)
                     OR (tc.table_name = ? AND ccu.table_name = ?))",
                [$a, $b, $b, $a]
            );
        } catch (\Throwable $e) {
            Log::warning("Could not read foreign keys for {$a}/{$b}: " . $e->getMessage());

            return [];
        }

        return self::$foreignKeys[$key] = $rows;
    }

    // -----------------------------------------------------------------
    // WHERE / ORDER / LIMIT
    // -----------------------------------------------------------------

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function buildWhere(array $params): array
    {
        $fragments = [];
        $bindings = [];

        foreach ($params as $key => $value) {
            if (! is_string($key) || in_array($key, self::RESERVED, true)) {
                continue;
            }

            if (! preg_match('/^[A-Za-z0-9_]+$/', $key)) {
                continue;
            }

            $parts = [$value];

            if (is_string($value) && str_contains($value, ',')) {
                $candidate = $this->splitTopLevel($value);
                $allOperators = $candidate !== [];

                foreach ($candidate as $part) {
                    if (! preg_match('/^(not\.)?(eq|neq|gt|gte|lt|lte|like|ilike|in|is)\./s', $part)) {
                        $allOperators = false;
                        break;
                    }
                }

                if ($allOperators) {
                    $parts = $candidate;
                }
            }

            foreach ($parts as $part) {
                [$sql, $bound] = $this->condition($key, $part);
                $fragments[] = $sql;
                $bindings = array_merge($bindings, $bound);
            }
        }

        return [implode(' AND ', $fragments), $bindings];
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function condition(string $column, mixed $value): array
    {
        $col = $this->ident($column);

        if (! is_string($value)) {
            if ($value === null) {
                return ["{$col} IS NULL", []];
            }

            if (is_bool($value)) {
                return ["{$col} = ?", [$value ? 'true' : 'false']];
            }

            return ["{$col} = ?", [$value]];
        }

        $raw = trim($value);

        if (str_starts_with($raw, 'not.')) {
            $inner = substr($raw, 4);

            if ($inner === 'is.null') {
                return ["{$col} IS NOT NULL", []];
            }

            [$sql, $bound] = $this->condition($column, $inner);

            return ['NOT (' . $sql . ')', $bound];
        }

        if (preg_match('/^(eq|neq|gt|gte|lt|lte|like|ilike|in|is)\.(.*)$/s', $raw, $m)) {
            $operator = $m[1];
            $operand = $m[2];

            switch ($operator) {
                case 'eq':
                    return ["{$col} = ?", [$operand === 'null' ? null : $operand]];
                case 'neq':
                    return ["{$col} <> ?", [$operand]];
                case 'gt':
                    return ["{$col} > ?", [$operand]];
                case 'gte':
                    return ["{$col} >= ?", [$operand]];
                case 'lt':
                    return ["{$col} < ?", [$operand]];
                case 'lte':
                    return ["{$col} <= ?", [$operand]];
                case 'like':
                    return ["{$col} LIKE ?", [$this->likePattern($operand)]];
                case 'ilike':
                    return ["{$col} ILIKE ?", [$this->likePattern($operand)]];
                case 'is':
                    $flag = strtolower(trim($operand));

                    return match ($flag) {
                        'null' => ["{$col} IS NULL", []],
                        'true' => ["{$col} IS TRUE", []],
                        'false' => ["{$col} IS FALSE", []],
                        default => ["{$col} IS NULL", []],
                    };
                case 'in':
                    $values = $this->parseInList($operand);

                    if ($values === []) {
                        return ['1 = 0', []];
                    }

                    $placeholders = implode(', ', array_fill(0, count($values), '?'));

                    return ["{$col} IN ({$placeholders})", $values];
            }
        }

        return ["{$col} = ?", [$raw]];
    }

    private function likePattern(string $operand): string
    {
        return str_replace('*', '%', urldecode($operand));
    }

    /**
     * @return array<int, string>
     */
    private function parseInList(string $operand): array
    {
        $operand = trim($operand);

        if (str_starts_with($operand, '(') && str_ends_with($operand, ')')) {
            $operand = substr($operand, 1, -1);
        }

        if (trim($operand) === '') {
            return [];
        }

        $values = [];

        foreach ($this->splitTopLevel($operand) as $item) {
            $item = trim($item);

            if ($item === '') {
                continue;
            }

            if (str_starts_with($item, '"') && str_ends_with($item, '"')) {
                $item = substr($item, 1, -1);
            }

            $values[] = $item;
        }

        return $values;
    }

    private function orderClause(array $params): string
    {
        $order = $params['order'] ?? null;

        if (! is_string($order) || trim($order) === '') {
            return '';
        }

        $parts = [];

        foreach ($this->splitTopLevel($order) as $spec) {
            $chunks = explode('.', $spec);
            $column = trim($chunks[0]);

            if ($column === '') {
                continue;
            }

            $direction = strtolower(trim($chunks[1] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
            $nulls = strtolower(trim($chunks[2] ?? ''));

            $sql = $this->ident($column) . ' ' . $direction;

            if ($nulls === 'nullsfirst') {
                $sql .= ' NULLS FIRST';
            } elseif ($nulls === 'nullslast') {
                $sql .= ' NULLS LAST';
            }

            $parts[] = $sql;
        }

        return $parts === [] ? '' : ' ORDER BY ' . implode(', ', $parts);
    }

    private function limitClause(array $params): string
    {
        $sql = '';

        if (isset($params['limit']) && $params['limit'] !== '' && $params['limit'] !== null) {
            $sql .= ' LIMIT ' . max(0, (int) $params['limit']);
        }

        if (isset($params['offset']) && $params['offset'] !== '' && $params['offset'] !== null) {
            $sql .= ' OFFSET ' . max(0, (int) $params['offset']);
        }

        return $sql;
    }

    // -----------------------------------------------------------------
    // Writes in detail
    // -----------------------------------------------------------------

    /**
     * @param  array<int, array|object>  $records
     * @return array<int, array<string, mixed>>
     */
    private function insertRows(string $table, array $records): array
    {
        $prepared = [];
        foreach ($records as $record) {
            $prepared[] = $this->prepareWriteData($table, $record);
        }

        // Group by column set so each group is one multi-row INSERT.
        $groups = [];
        foreach ($prepared as $row) {
            $groups[implode(',', array_keys($row))][] = $row;
        }

        $inserted = [];

        try {
            foreach ($groups as $columns => $rows) {
                $columnList = $columns === '' ? [] : explode(',', $columns);

                if ($columnList === []) {
                    foreach ($rows as $ignored) {
                        $fetched = $this->run(
                            'INSERT INTO ' . $this->ident($table) . ' DEFAULT VALUES RETURNING *',
                            []
                        );

                        foreach ($fetched as $row) {
                            $inserted[] = $this->cast($table, $row);
                        }
                    }

                    continue;
                }

                $values = [];
                $bindings = [];

                foreach ($rows as $row) {
                    $values[] = '(' . implode(', ', array_fill(0, count($columnList), '?')) . ')';

                    foreach ($columnList as $column) {
                        $bindings[] = $this->toDb($table, $column, $row[$column] ?? null);
                    }
                }

                $fetched = $this->run(
                    'INSERT INTO ' . $this->ident($table)
                    . ' (' . implode(', ', array_map(fn ($column) => $this->ident($column), $columnList)) . ')'
                    . ' VALUES ' . implode(', ', $values)
                    . ' RETURNING *',
                    $bindings
                );

                foreach ($fetched as $row) {
                    $inserted[] = $this->cast($table, $row);
                }
            }
        } catch (\Throwable $e) {
            $this->lastErrorMessage = $e->getMessage();
            Log::error("Local Postgres insert failed for table {$table}: " . $e->getMessage());

            return [];
        }

        $this->invalidateCache($table);

        return $inserted;
    }

    /**
     * Drop columns the table does not have and normalise values for binding.
     *
     * @return array<string, mixed>
     */
    private function prepareWriteData(string $table, array|object $data): array
    {
        $data = is_object($data) ? (array) $data : $data;
        $columns = $this->columns($table);
        $clean = [];

        foreach ($data as $column => $value) {
            if (! is_string($column) || ! array_key_exists($column, $columns)) {
                Log::debug("stripUnknownColumns({$table}): dropping '{$column}'");

                continue;
            }

            $clean[$column] = $value;
        }

        return $clean;
    }

    private function toDb(string $table, string $column, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $type = $this->columns($table)[$column]['type'] ?? null;

        if (in_array($type, ['json', 'jsonb'], true)) {
            return is_string($value) ? $value : json_encode($value);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return $value;
    }

    // -----------------------------------------------------------------
    // Schema metadata / value casting
    // -----------------------------------------------------------------

    /**
     * @return array<string, array{type: string, udt: string}>
     */
    private function columns(string $table): array
    {
        if (isset(self::$tableColumns[$table])) {
            return self::$tableColumns[$table];
        }

        try {
            $rows = $this->run(
                "SELECT column_name, data_type, udt_name
                 FROM information_schema.columns
                 WHERE table_schema = 'public' AND table_name = ?",
                [$table]
            );
        } catch (\Throwable $e) {
            Log::warning("Could not read columns for {$table}: " . $e->getMessage());

            return [];
        }

        $columns = [];
        foreach ($rows as $row) {
            $columns[$row['column_name']] = [
                'type' => (string) $row['data_type'],
                'udt' => (string) $row['udt_name'],
            ];
        }

        return self::$tableColumns[$table] = $columns;
    }

    /**
     * Give a row the types json_decode() used to give it.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function cast(string $table, array $row): array
    {
        $columns = $this->columns($table);

        foreach ($row as $column => $value) {
            if ($value === null) {
                continue;
            }

            $type = $columns[$column]['type'] ?? null;

            if ($type === null) {
                continue;
            }

            $row[$column] = match (true) {
                in_array($type, ['integer', 'bigint', 'smallint'], true) => (int) $value,
                $type === 'boolean' => $value === true || $value === 't' || $value === 'true' || $value === 1 || $value === '1',
                in_array($type, ['numeric', 'real', 'double precision'], true) => (float) $value,
                in_array($type, ['json', 'jsonb'], true) => is_string($value) ? json_decode($value, true) : $value,
                $type === 'date' => substr((string) $value, 0, 10),
                // PostgREST renders timestamptz in UTC; keep that so dates
                // display the same way they did before the switch.
                $type === 'timestamp with time zone' => \Carbon\Carbon::parse($value)->utc()->toIso8601String(),
                $type === 'timestamp without time zone' => \Carbon\Carbon::parse($value)->format('Y-m-d\TH:i:s'),
                default => $value,
            };
        }

        return $row;
    }

    // -----------------------------------------------------------------
    // Plumbing
    // -----------------------------------------------------------------

    /**
     * @param  array<int, mixed>  $bindings
     * @return array<int, array<string, mixed>>
     */
    private function run(string $sql, array $bindings): array
    {
        $statement = DB::connection()->getPdo()->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function ident(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    private function cacheKey(string $table, array $params): string
    {
        return 'sb:' . $table . ':' . md5((string) json_encode($params));
    }

    private function invalidateCache(string $table): void
    {
        $prefix = 'sb:' . $table . ':';

        foreach (array_keys(self::$queryCache) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset(self::$queryCache[$key]);
            }
        }
    }

    /**
     * Split on commas that are not inside parentheses or quotes.
     *
     * @return array<int, string>
     */
    private function splitTopLevel(string $value): array
    {
        $parts = [];
        $buffer = '';
        $depth = 0;
        $quoted = false;
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $character = $value[$i];

            if ($character === '"') {
                $quoted = ! $quoted;
                $buffer .= $character;

                continue;
            }

            if (! $quoted) {
                if ($character === '(') {
                    $depth++;
                } elseif ($character === ')') {
                    $depth--;
                } elseif ($character === ',' && $depth === 0) {
                    $parts[] = trim($buffer);
                    $buffer = '';

                    continue;
                }
            }

            $buffer .= $character;
        }

        $parts[] = trim($buffer);

        return array_values(array_filter($parts, fn ($part) => $part !== ''));
    }
}
