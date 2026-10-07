<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\InfoMailService;
use Illuminate\Http\Request;

/**
 * JSON twin of SuperAdmin\InfoMailController — the same info@ mailbox, read
 * through the same shared InfoMailService the website uses (no second
 * mailbox). Boxes, search, paging, reply (with the same file limits), read
 * toggle, star, status and delete are all the website's operations.
 */
class AdminMailController extends Controller
{
    private const MAX_REPLY_FILES = 5;
    private const MAX_REPLY_FILE_KILOBYTES = 20480;
    private const MAX_REPLY_TOTAL_BYTES = 31457280;
    private const REPLY_MIME_EXTENSIONS = 'jpg,jpeg,png,gif,webp,bmp,tif,tiff,'
        .'pdf,txt,'
        .'mp4,webm,ogv,mov,'
        .'mp3,ogg,oga,wav';

    public function __construct(private InfoMailService $mails) {}

    public function index(Request $request)
    {
        $box = (string) $request->query('box', 'all');
        if (! in_array($box, ['all', 'unread', 'starred', 'replied', 'closed'], true)) {
            $box = 'all';
        }

        $page = max(1, (int) $request->query('page', 1));
        [$mails, $shown] = $this->mails->inbox([
            'box' => $box,
            'search' => (string) $request->query('search', ''),
        ], $page);

        return response()->json([
            'mails' => $mails,
            'box' => $box,
            'page' => $page,
            'shown' => $shown,
            'unread_count' => $this->mails->unreadCount(),
        ]);
    }

    public function show($mailId)
    {
        $mail = $this->mails->find((int) $mailId);
        if (! $mail) {
            return response()->json(['message' => 'Mail not found.'], 404);
        }

        $thread = $this->mails->thread($mail);

        if (! ($mail->is_read ?? false)) {
            $this->mails->markRead((int) $mail->id);
            $mail->is_read = true;
        }

        $sessionUser = request()->attributes->get('staff_user');

        return response()->json([
            'mail' => $mail,
            'thread' => collect($thread['mails']),
            'replies' => collect($thread['replies']),
            'attachments' => collect($this->mails->attachmentsFor((int) $mail->id)),
            'info_address' => config('info_mail.address'),
            'signature' => is_array($sessionUser) ? ($sessionUser['name'] ?? 'Staff') : 'Staff',
            'reply_limits' => [
                'files' => self::MAX_REPLY_FILES,
                'file_mb' => (int) (self::MAX_REPLY_FILE_KILOBYTES / 1024),
                'total_mb' => (int) (self::MAX_REPLY_TOTAL_BYTES / 1048576),
            ],
        ]);
    }

    public function reply(Request $request, $mailId)
    {
        $mail = $this->mails->find((int) $mailId);
        if (! $mail) {
            return response()->json(['message' => 'Mail not found.'], 404);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
            'attachments' => ['nullable', 'array', 'max:'.self::MAX_REPLY_FILES],
            'attachments.*' => [
                'file',
                'max:'.self::MAX_REPLY_FILE_KILOBYTES,
                'mimes:'.self::REPLY_MIME_EXTENSIONS,
            ],
        ]);

        $files = $request->file('attachments', []);
        $files = is_array($files) ? array_values(array_filter($files)) : [];

        $total = array_sum(array_map(fn ($f) => $f->getSize(), $files));
        if ($total > self::MAX_REPLY_TOTAL_BYTES) {
            return response()->json([
                'message' => 'Those files add up to more than the '.((int) (self::MAX_REPLY_TOTAL_BYTES / 1048576)).' MB limit for one answer.',
            ], 422);
        }

        $sessionUser = $request->attributes->get('staff_user');
        $staff = (object) (is_array($sessionUser) ? $sessionUser : []);

        $result = $this->mails->reply(
            $mail,
            $validated['body'],
            $staff,
            $staff->name ?? 'Staff',
            $files,
        );

        if (! $result['ok']) {
            return response()->json(['message' => $result['message']], 500);
        }

        return response()->json(['message' => $result['message']]);
    }

    public function toggleRead($mailId)
    {
        $mail = $this->mails->find((int) $mailId);
        if (! $mail) {
            return response()->json(['message' => 'Mail not found.'], 404);
        }

        $makeRead = ! ($mail->is_read ?? false);
        $this->mails->markRead((int) $mail->id, $makeRead);

        return response()->json([
            'message' => $makeRead ? 'Marked as read.' : 'Marked as unread.',
            'is_read' => $makeRead,
        ]);
    }

    public function star($mailId)
    {
        $mail = $this->mails->find((int) $mailId);
        if (! $mail) {
            return response()->json(['message' => 'Mail not found.'], 404);
        }

        $starred = ! ($mail->is_starred ?? false);
        $this->mails->setStarred((int) $mail->id, $starred);

        return response()->json([
            'message' => $starred ? 'Starred.' : 'Star removed.',
            'is_starred' => $starred,
        ]);
    }

    public function status(Request $request, $mailId)
    {
        $mail = $this->mails->find((int) $mailId);
        if (! $mail) {
            return response()->json(['message' => 'Mail not found.'], 404);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:new,replied,closed'],
        ]);

        $this->mails->setStatus((int) $mail->id, $validated['status']);

        return response()->json(['message' => 'Mail moved to '.$validated['status'].'.']);
    }

    public function destroy($mailId)
    {
        $mail = $this->mails->find((int) $mailId);
        if (! $mail) {
            return response()->json(['message' => 'Mail not found.'], 404);
        }

        $this->mails->delete((int) $mail->id);

        return response()->json(['message' => 'Mail deleted.']);
    }
}
