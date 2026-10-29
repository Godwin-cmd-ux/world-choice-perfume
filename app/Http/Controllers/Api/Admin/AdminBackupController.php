<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupManager;
use App\Services\CloudinaryService;
use App\Services\DatabaseBackupService;
use App\Services\SupabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON twin of SuperAdmin\BackupsController — the mobile app's Backups screen.
 *
 * Same catalogue, same rules, same manager: list the nightly and induced
 * backups by date, take one now, open either file, delete both files and the
 * row. The app can never grant itself anything here: EnsureStaffSessionApi has
 * already re-read the user row and refused anything not super_admin.
 */
class AdminBackupController extends Controller
{
    private BackupManager $manager;

    public function __construct(?SupabaseService $supabase = null, ?CloudinaryService $cloudinary = null)
    {
        $this->manager = new BackupManager($supabase, $cloudinary);
    }

    /**
     * The catalogue, newest first, optionally narrowed to a date range.
     *
     * `system` carries what the screen needs to explain itself: the Cloudinary
     * folder, the schedule's timezone, and whether Cloudinary could be read at
     * all — a warning means the list may be missing a backup that was filed but
     * never recorded.
     */
    public function index(Request $request): JsonResponse
    {
        $import = $this->manager->import();

        $from = BackupManager::dateOrNull($request->query('date_from'));
        $to = BackupManager::dateOrNull($request->query('date_to'));

        $listing = $this->manager->list($from, $to);

        return response()->json([
            'backups' => array_map(fn ($day) => (array) $day, $listing['days']),
            'total_stored' => $listing['total_stored'],
            'total_bytes' => $listing['total_bytes'],
            'matching_bytes' => $listing['matching_bytes'],
            'filters' => ['date_from' => $from, 'date_to' => $to],
            'imported' => $import['imported'],
            'cloudinary_warning' => $import['warning'],
            'system' => [
                'folder' => DatabaseBackupService::CLOUD_ROOT_FOLDER,
                'timezone' => DatabaseBackupService::TIMEZONE,
            ],
        ]);
    }

    /** "Induce Backup": take one now, independent of the nightly run. */
    public function induce(): JsonResponse
    {
        $result = $this->manager->induce();

        if (! $result['ok']) {
            return response()->json(
                ['message' => $result['message']],
                $result['code'] === 'busy' ? 409 : 502
            );
        }

        $run = $result['result'];

        return response()->json([
            'message' => $result['message'],
            'backup' => [
                'date' => $run['date'],
                'stamp' => $run['stamp'],
                'kind' => $run['kind'],
                'folder' => $run['folder'],
                'rows_count' => $run['rows'],
                'tables_count' => count($run['tables']),
                'seconds' => $run['seconds'],
            ],
        ]);
    }

    /** Delete one backup: both files from Cloudinary, then the row. */
    public function destroy(string $stamp): JsonResponse
    {
        $result = $this->manager->delete($stamp);

        if (! $result['ok']) {
            $status = match ($result['code'] ?? '') {
                'not_found' => 404,
                'cloudinary', 'database' => 502,
                default => 500,
            };

            return response()->json(['message' => $result['message']], $status);
        }

        return response()->json(['message' => $result['message']]);
    }
}
