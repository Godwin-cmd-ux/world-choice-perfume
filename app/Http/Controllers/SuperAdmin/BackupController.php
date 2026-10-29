<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The GET endpoint that takes a backup and files it in Cloudinary.
 *
 * Two callers:
 *  - the scheduler, at 00:00 Africa/Dar_es_Salaam (see routes/console.php);
 *  - anyone who needs one now: a signed-in super admin, or a cron service
 *    holding the BACKUP_TRIGGER_TOKEN, which is the only way it can run on a
 *    host where no scheduler is running.
 *
 * It answers with the folder and file URLs it wrote, so a run can be confirmed
 * from the response instead of being taken on trust.
 */
class BackupController extends Controller
{
    private DatabaseBackupService $backups;

    public function __construct(?DatabaseBackupService $backups = null)
    {
        $this->backups = $backups ?? new DatabaseBackupService();
    }

    public function run(Request $request): JsonResponse
    {
        $this->authorise($request);

        try {
            $result = $this->backups->run();
        } catch (Throwable $e) {
            Log::error('Database backup failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 'ok',
            'date' => $result['date'],
            'folder' => $result['folder'],
            'files' => $result['files'],
            'rows' => $result['rows'],
            'tables' => $result['tables'],
            'seconds' => $result['seconds'],
        ]);
    }

    /**
     * A super admin session, or the trigger token. The token comparison is
     * constant-time and only works when a token is actually configured, so an
     * unset BACKUP_TRIGGER_TOKEN cannot be "matched" by an empty query string.
     */
    private function authorise(Request $request): void
    {
        $user = $request->user();

        if ($user !== null && $user->role === 'super_admin') {
            return;
        }

        $expected = (string) config('services.backup.token');
        $supplied = (string) $request->query('token', '');

        if ($expected !== '' && hash_equals($expected, $supplied)) {
            return;
        }

        abort(403, 'This backup endpoint requires a super admin session or the backup trigger token.');
    }
}
