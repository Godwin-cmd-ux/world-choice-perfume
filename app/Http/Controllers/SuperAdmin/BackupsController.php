<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use App\Services\DatabaseBackupService;
use App\Services\SupabaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The Super Admin's Backups page.
 *
 * Two kinds of backup live in Cloudinary and in the backups table:
 *
 *  - automatic: one per night at 00:00, filed as backup_<date>/
 *  - induced:   taken on demand, filed as induced/backup_<date>_<time>/
 *
 * The stamp is the identity — a date for the automatic one, date plus time for
 * an induced one — so clicking "Induce Backup" seconds after midnight writes a
 * separate folder and row instead of replacing the night's snapshot.
 *
 * The page lists them newest first, filters by date, hands either file over as
 * a download, and deletes both files and the row together.
 */
class BackupsController extends Controller
{
    /** The two halves of a backup, and the only names a download may ask for. */
    private const KINDS = ['schema', 'seed'];

    private SupabaseService $supabase;

    private CloudinaryService $cloudinary;

    private DatabaseBackupService $backups;

    public function __construct(
        ?SupabaseService $supabase = null,
        ?CloudinaryService $cloudinary = null
    ) {
        $this->supabase = $supabase ?? new SupabaseService();
        $this->cloudinary = $cloudinary ?? new CloudinaryService();

        // The same stores the page reads, so an induced backup lands exactly
        // where the page will look for it.
        $this->backups = new DatabaseBackupService($this->supabase, $this->cloudinary);
    }

    public function index(Request $request): View
    {
        $import = $this->importFromCloudinary();

        $from = $this->dateOrNull($request->query('date_from'));
        $to = $this->dateOrNull($request->query('date_to'));

        $rows = $this->supabase->queryFresh('backups', [
            'select' => '*',
            'limit' => 500,
        ]);

        $days = [];
        $totalBytes = 0;
        $matchingBytes = 0;

        foreach ($rows as $row) {
            $folder = (string) ($row['folder'] ?? '');
            $kind = (string) ($row['kind'] ?? $this->kindFromFolder($folder));
            $date = substr((string) ($row['backup_date'] ?? ''), 0, 10);
            $stamp = (string) ($row['stamp'] ?? ($kind === 'induced' ? $this->stampFromFolder($folder) : $date));

            if ($date === '' || $stamp === '') {
                continue;
            }

            $schemaBytes = (int) ($row['schema_bytes'] ?? 0);
            $seedBytes = (int) ($row['seed_bytes'] ?? 0);
            $totalBytes += $schemaBytes + $seedBytes;

            if (($from !== null && $date < $from) || ($to !== null && $date > $to)) {
                continue;
            }

            $matchingBytes += $schemaBytes + $seedBytes;

            $days[] = (object) [
                'date' => $date,
                'stamp' => $stamp,
                'kind' => $kind,
                'time' => $kind === 'induced' ? $this->timeFromStamp($stamp) : null,
                'folder' => $folder,
                'schema_bytes' => $schemaBytes,
                'seed_bytes' => $seedBytes,
                'rows_count' => (int) ($row['rows_count'] ?? 0),
                'tables_count' => (int) ($row['tables_count'] ?? 0),
                'seconds' => $row['seconds'] === null ? null : (float) $row['seconds'],
                'status' => (string) ($row['status'] ?? 'complete'),
                'created_at' => $row['created_at'] ?? null,
            ];
        }

        // Newest first: a stamp sorts chronologically, and an induced one on a
        // date sorts after that date's automatic backup.
        usort($days, fn ($a, $b) => strcmp($b->stamp, $a->stamp));

        return view('super-admin.backups.index', [
            'days' => $days,
            'totalStored' => count($rows),
            'totalBytes' => $totalBytes,
            'matchingBytes' => $matchingBytes,
            'latest' => $days[0]->stamp ?? null,
            'imported' => $import['imported'],
            'cloudinaryWarning' => $import['warning'],
            'cloudinaryFolder' => DatabaseBackupService::CLOUD_ROOT_FOLDER,
            'scheduleTimezone' => DatabaseBackupService::TIMEZONE,
            'filters' => ['date_from' => $from, 'date_to' => $to],
            'filtered' => $from !== null || $to !== null,
        ]);
    }

    /**
     * "Induce Backup": take one now, independent of the nightly run.
     *
     * A cache lock keeps two clicks (or a click landing exactly on midnight)
     * from writing at the same time.
     */
    public function induce(): RedirectResponse
    {
        $lock = Cache::lock('database-backup', 600);

        if (! $lock->get()) {
            return redirect()->route('super-admin.backups.index')->with(
                'error',
                'A backup is already running. Give it a moment and refresh the page.'
            );
        }

        try {
            $result = $this->backups->runInduced();
        } catch (\Throwable $e) {
            Log::error('Induced backup failed', ['message' => $e->getMessage()]);

            return redirect()->route('super-admin.backups.index')
                ->with('error', 'The backup failed: ' . $e->getMessage());
        } finally {
            $lock->release();
        }

        return redirect()->route('super-admin.backups.index')->with(
            'success',
            "Induced backup for {$result['date']} at " . substr($result['stamp'], -6, 2) . ':' . substr($result['stamp'], -4, 2)
            . ':' . substr($result['stamp'], -2) . ' created — '
            . number_format($result['rows']) . ' rows from ' . count($result['tables']) . ' tables, filed in Cloudinary.'
        );
    }

    /**
     * Hand one of a backup's two files to the browser as a download.
     *
     * It is proxied rather than linked so the file always arrives as an
     * attachment (Cloudinary serves raw text inline) and so the download stays
     * behind the super admin login.
     */
    public function download(string $stamp, string $file): StreamedResponse|RedirectResponse
    {
        if (! in_array($file, self::KINDS, true)) {
            abort(404);
        }

        $row = $this->findBackup($stamp);

        if ($row === null) {
            return redirect()->route('super-admin.backups.index')
                ->with('error', "There is no backup recorded for {$this->label($stamp)}.");
        }

        $url = (string) ($row[$file . '_url'] ?? '');

        if ($url === '') {
            $url = $this->deliveryUrl(DatabaseBackupService::publicIdFor(
                $stamp,
                $file,
                $this->isInduced($row, $stamp)
            ));
        }

        try {
            $response = Http::timeout(180)->get($url);
        } catch (\Throwable $e) {
            Log::error('Backup download failed', ['stamp' => $stamp, 'file' => $file, 'message' => $e->getMessage()]);

            return redirect()->route('super-admin.backups.index')
                ->with('error', "Could not reach Cloudinary for {$this->label($stamp)}: " . $e->getMessage());
        }

        if (! $response->successful()) {
            return redirect()->route('super-admin.backups.index')->with(
                'error',
                "Cloudinary could not return the {$file} file for {$this->label($stamp)} (HTTP " . $response->status() . ').'
            );
        }

        $body = $response->body();
        $filename = DatabaseBackupService::fileName($stamp, $file);

        return response()->streamDownload(function () use ($body) {
            echo $body;
        }, $filename, ['Content-Type' => 'application/sql']);
    }

    /**
     * Delete one backup: both files, then the row.
     *
     * If Cloudinary refuses a file, nothing is removed from the table and the
     * reason is reported — a half-deleted backup that still looks complete in
     * the list is worse than one that is still there.
     */
    public function destroy(string $stamp): RedirectResponse
    {
        $row = $this->findBackup($stamp);

        if ($row === null) {
            return redirect()->route('super-admin.backups.index')
                ->with('error', "There is no backup recorded for {$this->label($stamp)}.");
        }

        $induced = $this->isInduced($row, $stamp);
        $failed = [];

        foreach (self::KINDS as $kind) {
            if (! $this->cloudinary->deleteRaw(DatabaseBackupService::publicIdFor($stamp, $kind, $induced))) {
                $failed[$kind] = $this->cloudinary->lastError() ?? 'no reason given';
            }
        }

        if ($failed !== []) {
            Log::error('Backup delete refused by Cloudinary', ['stamp' => $stamp, 'failed' => $failed]);

            $details = collect($failed)->map(fn ($reason, $kind) => "{$kind}: {$reason}")->implode('; ');

            return redirect()->route('super-admin.backups.index')
                ->with('error', "Could not delete the backup for {$this->label($stamp)} — {$details}. It is still listed.");
        }

        $folder = (string) ($row['folder'] ?? DatabaseBackupService::folderFor($stamp, $induced));

        if (! $this->supabase->delete('backups', ['folder' => 'eq.' . $folder])) {
            return redirect()->route('super-admin.backups.index')->with(
                'error',
                "The files for {$this->label($stamp)} were deleted from Cloudinary, but the database row could not be removed"
                . ($this->supabase->lastErrorMessage() ? ': ' . $this->supabase->lastErrorMessage() : '.')
            );
        }

        Log::info('Backup deleted', ['stamp' => $stamp, 'kind' => $induced ? 'induced' : 'automatic', 'by' => auth()->id()]);

        return redirect()->route('super-admin.backups.index')->with(
            'success',
            "The backup for {$this->label($stamp)} has been deleted from Cloudinary and the database."
        );
    }

    /**
     * Find one backup by stamp, trying the automatic and induced folders.
     *
     * The folder is the lookup key because it exists in every version of the
     * table, so a page loaded before the kind/stamp migration still answers.
     */
    private function findBackup(string $stamp): ?array
    {
        foreach ([false, true] as $induced) {
            $row = $this->supabase->findOne('backups', [
                'folder' => 'eq.' . DatabaseBackupService::folderFor($stamp, $induced),
            ]);

            if ($row !== null) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Pull any backup folder Cloudinary has that the table does not know about.
     *
     * @return array{imported: int, warning: ?string}
     */
    private function importFromCloudinary(): array
    {
        $files = $this->cloudinary->listRaw(DatabaseBackupService::CLOUD_ROOT_FOLDER . '/');

        if ($files === null) {
            return [
                'imported' => 0,
                'warning' => $this->cloudinary->lastError()
                    ?? 'Cloudinary could not be read, so this list may be missing backups.',
            ];
        }

        $known = [];
        foreach ($this->supabase->query('backups', ['select' => 'folder', 'limit' => 1000]) as $row) {
            $known[(string) ($row['folder'] ?? '')] = true;
        }

        $root = preg_quote(DatabaseBackupService::CLOUD_ROOT_FOLDER, '#');

        $patterns = [
            'automatic' => '#^' . $root . '/backup_(\d{4}-\d{2}-\d{2})/(schema|seed)_(\d{4}-\d{2}-\d{2})\.sql$#',
            'induced' => '#^' . $root . '/induced/backup_(\d{4}-\d{2}-\d{2}_\d{6})/(schema|seed)_(\d{4}-\d{2}-\d{2}_\d{6})\.sql$#',
        ];

        $found = [];

        foreach ($files as $file) {
            foreach ($patterns as $kind => $pattern) {
                if (preg_match($pattern, $file['public_id'], $matches) !== 1) {
                    continue;
                }

                if ($matches[1] !== $matches[3]) {
                    continue; // the file name disagrees with the folder it sits in
                }

                $stamp = $matches[1];
                $folder = DatabaseBackupService::folderFor($stamp, $kind === 'induced');

                $found[$folder]['kind'] = $kind;
                $found[$folder]['stamp'] = $stamp;
                $found[$folder]['date'] = substr($stamp, 0, 10);
                $found[$folder]['parts'][$matches[2]] = $file;

                break;
            }
        }

        $imported = 0;

        foreach ($found as $folder => $backup) {
            if (isset($known[$folder])) {
                continue;
            }

            $this->supabase->insert('backups', [
                'backup_date' => $backup['date'],
                'kind' => $backup['kind'],
                'stamp' => $backup['stamp'],
                'folder' => $folder,
                'schema_url' => $backup['parts']['schema']['url'] ?? null,
                'schema_bytes' => $backup['parts']['schema']['bytes'] ?? null,
                'seed_url' => $backup['parts']['seed']['url'] ?? null,
                'seed_bytes' => $backup['parts']['seed']['bytes'] ?? null,
                'tables_count' => 0,
                'rows_count' => 0,
                'seconds' => null,
                'status' => 'imported',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $imported++;
        }

        return ['imported' => $imported, 'warning' => null];
    }

    private function isInduced(array $row, string $stamp): bool
    {
        $kind = (string) ($row['kind'] ?? '');

        if ($kind !== '') {
            return $kind === 'induced';
        }

        return str_contains((string) ($row['folder'] ?? ''), '/induced/') || str_contains($stamp, '_');
    }

    private function kindFromFolder(string $folder): string
    {
        return str_contains($folder, '/induced/') ? 'induced' : 'automatic';
    }

    private function stampFromFolder(string $folder): string
    {
        $name = basename($folder);

        return str_starts_with($name, 'backup_') ? substr($name, 7) : $name;
    }

    /** "2026-10-29_143501" -> "14:35:01" */
    private function timeFromStamp(string $stamp): ?string
    {
        if (preg_match('/_(\d{2})(\d{2})(\d{2})$/', $stamp, $m) !== 1) {
            return null;
        }

        return "{$m[1]}:{$m[2]}:{$m[3]}";
    }

    private function label(string $stamp): string
    {
        $date = substr($stamp, 0, 10);
        $time = $this->timeFromStamp($stamp);

        return $time !== null ? "{$date} at {$time}" : $date;
    }

    private function dateOrNull(mixed $value): ?string
    {
        $value = (string) $value;

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) !== 1) {
            return null;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $value : null;
    }

    /**
     * The public delivery URL of a raw file, used when a row has no stored URL.
     */
    private function deliveryUrl(string $publicId): string
    {
        $cloud = (string) config('services.cloudinary.cloud_name');

        return "https://res.cloudinary.com/{$cloud}/raw/upload/{$publicId}";
    }
}
