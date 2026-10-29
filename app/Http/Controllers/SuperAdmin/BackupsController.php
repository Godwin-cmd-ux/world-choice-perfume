<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use App\Services\DatabaseBackupService;
use App\Services\SupabaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The Super Admin's Backups page.
 *
 * Every nightly backup is two files in Cloudinary and one row in the backups
 * table. The page lists those rows by date, hands the files over as downloads,
 * and deletes a backup from both stores — Cloudinary first, the row only after
 * Cloudinary confirmed it, so the list never claims a file is gone when it is
 * still there.
 *
 * Before listing, Cloudinary is read once and any backup folder that the table
 * does not know about is imported. Without that, a backup taken before this
 * page existed (or one whose bookkeeping row failed to write) would be
 * invisible and therefore impossible to delete from here.
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
        // where the nightly one would.
        $this->backups = new DatabaseBackupService($this->supabase, $this->cloudinary);
    }

    /**
     * "Induce Backup": take one now, on demand.
     *
     * It is the same run the scheduler performs at midnight, so a backup is
     * dated today and — if one already exists for today — replaces that day's
     * two files and its row rather than leaving two for the same date.
     *
     * A cache lock keeps two clicks (or a click landing exactly on midnight)
     * from writing the same Cloudinary files at once.
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
            $result = $this->backups->run();
        } catch (\Throwable $e) {
            Log::error('Induced backup failed', ['message' => $e->getMessage()]);

            return redirect()->route('super-admin.backups.index')
                ->with('error', 'The backup failed: ' . $e->getMessage());
        } finally {
            $lock->release();
        }

        return redirect()->route('super-admin.backups.index')->with(
            'success',
            "Backup for {$result['date']} created — " . number_format($result['rows']) . ' rows from '
            . count($result['tables']) . ' tables, filed in Cloudinary.'
        );
    }

    public function index(): View
    {
        $import = $this->importFromCloudinary();

        $rows = $this->supabase->queryFresh('backups', [
            'select' => '*',
            'order' => 'backup_date.desc',
            'limit' => 365,
        ]);

        $days = [];
        $totalBytes = 0;

        foreach ($rows as $row) {
            $date = (string) ($row['backup_date'] ?? '');
            $schemaBytes = (int) ($row['schema_bytes'] ?? 0);
            $seedBytes = (int) ($row['seed_bytes'] ?? 0);
            $totalBytes += $schemaBytes + $seedBytes;

            $days[] = (object) [
                'date' => $date,
                'folder' => (string) ($row['folder'] ?? ''),
                'schema_bytes' => $schemaBytes,
                'seed_bytes' => $seedBytes,
                'rows_count' => (int) ($row['rows_count'] ?? 0),
                'tables_count' => (int) ($row['tables_count'] ?? 0),
                'seconds' => $row['seconds'] === null ? null : (float) $row['seconds'],
                'status' => (string) ($row['status'] ?? 'complete'),
                'created_at' => $row['created_at'] ?? null,
            ];
        }

        return view('super-admin.backups.index', [
            'days' => $days,
            'totalBytes' => $totalBytes,
            'latest' => $days[0]->date ?? null,
            'imported' => $import['imported'],
            'cloudinaryWarning' => $import['warning'],
            'cloudinaryFolder' => DatabaseBackupService::CLOUD_ROOT_FOLDER,
            'scheduleTimezone' => DatabaseBackupService::TIMEZONE,
        ]);
    }

    /**
     * Hand one of a backup's two files to the browser as a download.
     *
     * It is proxied rather than linked so the file always arrives as an
     * attachment (Cloudinary serves raw text inline) and so the download stays
     * behind the super admin login.
     */
    public function download(string $date, string $file): StreamedResponse|RedirectResponse
    {
        if (! in_array($file, self::KINDS, true)) {
            abort(404);
        }

        $row = $this->supabase->findOne('backups', ['backup_date' => 'eq.' . $date]);

        if ($row === null) {
            return redirect()->route('super-admin.backups.index')
                ->with('error', "There is no backup recorded for {$date}.");
        }

        $url = (string) ($row[$file . '_url'] ?? '');

        if ($url === '') {
            $url = $this->deliveryUrl(DatabaseBackupService::publicIdFor($date, $file));
        }

        try {
            $response = Http::timeout(180)->get($url);
        } catch (\Throwable $e) {
            Log::error('Backup download failed', ['date' => $date, 'file' => $file, 'message' => $e->getMessage()]);

            return redirect()->route('super-admin.backups.index')
                ->with('error', "Could not reach Cloudinary for {$date}: " . $e->getMessage());
        }

        if (! $response->successful()) {
            return redirect()->route('super-admin.backups.index')
                ->with('error', "Cloudinary could not return the {$file} file for {$date} (HTTP " . $response->status() . ').');
        }

        $body = $response->body();
        $filename = DatabaseBackupService::fileName($date, $file);

        return response()->streamDownload(function () use ($body) {
            echo $body;
        }, $filename, ['Content-Type' => 'application/sql']);
    }

    /**
     * Delete one day's backup: both files, then the row.
     *
     * If Cloudinary refuses a file, nothing is removed from the table and the
     * reason is reported — a half-deleted backup that still looks complete in
     * the list is worse than one that is still there.
     */
    public function destroy(string $date): RedirectResponse
    {
        $row = $this->supabase->findOne('backups', ['backup_date' => 'eq.' . $date]);

        if ($row === null) {
            return redirect()->route('super-admin.backups.index')
                ->with('error', "There is no backup recorded for {$date}.");
        }

        $failed = [];

        foreach (self::KINDS as $kind) {
            if (! $this->cloudinary->deleteRaw(DatabaseBackupService::publicIdFor($date, $kind))) {
                $failed[$kind] = $this->cloudinary->lastError() ?? 'no reason given';
            }
        }

        if ($failed !== []) {
            Log::error('Backup delete refused by Cloudinary', ['date' => $date, 'failed' => $failed]);

            $details = collect($failed)->map(fn ($reason, $kind) => "{$kind}: {$reason}")->implode('; ');

            return redirect()->route('super-admin.backups.index')
                ->with('error', "Could not delete the backup for {$date} — {$details}. It is still listed.");
        }

        if (! $this->supabase->delete('backups', ['backup_date' => 'eq.' . $date])) {
            return redirect()->route('super-admin.backups.index')->with(
                'error',
                "The files for {$date} were deleted from Cloudinary, but the database row could not be removed"
                . ($this->supabase->lastErrorMessage() ? ': ' . $this->supabase->lastErrorMessage() : '.')
            );
        }

        Log::info('Backup deleted', ['date' => $date, 'by' => auth()->id()]);

        return redirect()->route('super-admin.backups.index')->with(
            'success',
            "The backup for {$date} has been deleted from Cloudinary and the database."
        );
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
        foreach ($this->supabase->query('backups', ['select' => 'backup_date', 'limit' => 1000]) as $row) {
            $known[(string) ($row['backup_date'] ?? '')] = true;
        }

        $found = [];
        $pattern = '#^' . preg_quote(DatabaseBackupService::CLOUD_ROOT_FOLDER, '#')
            . '/backup_(\d{4}-\d{2}-\d{2})/(schema|seed)_(\d{4}-\d{2}-\d{2})\.sql$#';

        foreach ($files as $file) {
            if (preg_match($pattern, $file['public_id'], $matches) !== 1) {
                continue; // not a backup file: leave anything else in the folder alone
            }

            if ($matches[1] !== $matches[3]) {
                continue; // the file name disagrees with the folder it sits in
            }

            $found[$matches[1]][$matches[2]] = $file;
        }

        $imported = 0;

        foreach ($found as $date => $parts) {
            if (isset($known[$date])) {
                continue;
            }

            $this->supabase->insert('backups', [
                'backup_date' => $date,
                'folder' => DatabaseBackupService::folderFor($date),
                'schema_url' => $parts['schema']['url'] ?? null,
                'schema_bytes' => $parts['schema']['bytes'] ?? null,
                'seed_url' => $parts['seed']['url'] ?? null,
                'seed_bytes' => $parts['seed']['bytes'] ?? null,
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

    /**
     * The public delivery URL of a raw file, used when a row has no stored URL.
     */
    private function deliveryUrl(string $publicId): string
    {
        $cloud = (string) config('services.cloudinary.cloud_name');

        return "https://res.cloudinary.com/{$cloud}/raw/upload/{$publicId}";
    }
}
