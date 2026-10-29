<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\BackupManager;
use App\Services\CloudinaryService;
use App\Services\DatabaseBackupService;
use App\Services\SupabaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The Super Admin's Backups page.
 *
 * Two kinds of backup live in Cloudinary and in the backups table: the nightly
 * one, filed as backup_<date>/, and an induced one, filed as
 * induced/backup_<date>_<time>/. The reading, importing, inducing and deleting
 * all happen in BackupManager — the mobile API exposes the same operations, so
 * this controller only decides how to present them (a Blade page, a flash
 * message, a streamed file).
 */
class BackupsController extends Controller
{
    private BackupManager $manager;

    public function __construct(?SupabaseService $supabase = null, ?CloudinaryService $cloudinary = null)
    {
        // The services are the constructor arguments so the container (and the
        // tests) can supply them; the manager is built from those same stores,
        // which is what the mobile API does too.
        $this->manager = new BackupManager($supabase, $cloudinary);
    }

    public function index(Request $request): View
    {
        $import = $this->manager->import();

        $from = BackupManager::dateOrNull($request->query('date_from'));
        $to = BackupManager::dateOrNull($request->query('date_to'));

        $listing = $this->manager->list($from, $to);

        return view('super-admin.backups.index', [
            'days' => $listing['days'],
            'totalStored' => $listing['total_stored'],
            'totalBytes' => $listing['total_bytes'],
            'matchingBytes' => $listing['matching_bytes'],
            'latest' => $listing['days'][0]->stamp ?? null,
            'imported' => $import['imported'],
            'cloudinaryWarning' => $import['warning'],
            'cloudinaryFolder' => DatabaseBackupService::CLOUD_ROOT_FOLDER,
            'scheduleTimezone' => DatabaseBackupService::TIMEZONE,
            'filters' => ['date_from' => $from, 'date_to' => $to],
            'filtered' => $from !== null || $to !== null,
        ]);
    }

    /** "Induce Backup": take one now, independent of the nightly run. */
    public function induce(): RedirectResponse
    {
        $result = $this->manager->induce();

        return redirect()->route('super-admin.backups.index')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
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
        if (! in_array($file, BackupManager::KINDS, true)) {
            abort(404);
        }

        $row = $this->manager->find($stamp);

        if ($row === null) {
            return redirect()->route('super-admin.backups.index')
                ->with('error', "There is no backup recorded for {$this->manager->label($stamp)}.");
        }

        try {
            $response = Http::timeout(180)->get($this->manager->fileUrl($row, $stamp, $file));
        } catch (\Throwable $e) {
            Log::error('Backup download failed', ['stamp' => $stamp, 'file' => $file, 'message' => $e->getMessage()]);

            return redirect()->route('super-admin.backups.index')
                ->with('error', "Could not reach Cloudinary for {$this->manager->label($stamp)}: " . $e->getMessage());
        }

        if (! $response->successful()) {
            return redirect()->route('super-admin.backups.index')->with(
                'error',
                "Cloudinary could not return the {$file} file for {$this->manager->label($stamp)} (HTTP " . $response->status() . ').'
            );
        }

        $body = $response->body();
        $filename = DatabaseBackupService::fileName($stamp, $file);

        return response()->streamDownload(function () use ($body) {
            echo $body;
        }, $filename, ['Content-Type' => 'application/sql']);
    }

    /** Delete one backup: both files, then the row. */
    public function destroy(string $stamp): RedirectResponse
    {
        $result = $this->manager->delete($stamp);

        return redirect()->route('super-admin.backups.index')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
