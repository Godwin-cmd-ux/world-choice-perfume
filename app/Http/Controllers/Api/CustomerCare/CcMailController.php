<?php

namespace App\Http\Controllers\Api\CustomerCare;

use Illuminate\Http\Request;

/**
 * JSON twin of the customer-care Mails screens — the info@worldchoiceperfume.com
 * inbox, Head Quarters customer care only (info@ is company-wide, not per
 * branch). Boxes, search, paging, reply with the website's file limits,
 * read toggle, star, status and delete are all the shared InfoMailService
 * operations the Super Admin mailbox uses too.
 */
class CcMailController extends CcBaseController
{
    /** Files on a single answer. Enough for photos and a document. */
    private const MAX_REPLY_FILES = 5;

    /** Per file, in kilobytes, as the validator wants it. */
    private const MAX_REPLY_FILE_KILOBYTES = 20480;

    /** Per answer, in bytes — under the 40M post_max_size, or PHP drops the POST. */
    private const MAX_REPLY_TOTAL_BYTES = 31457280;

    /** What a member may attach — judged on the real type, never on the name. */
    private const REPLY_MIME_EXTENSIONS = 'jpg,jpeg,png,gif,webp,bmp,tif,tiff,'
        .'pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,rtf,odt,ods,'
        .'zip,mp4,mov,avi,mkv,webm,mp3,wav,m4a,ogg';

    public function index(Request $request)
    {
        $this->assertHq($request);

        $box = (string) $request->query('box', 'all');
        if (! in_array($box, ['all', 'unread', 'starred', 'replied', 'closed'], true)) {
            $box = 'all';
        }

        $page = max(1, (int) $request->query('page', 1));
        [$mails, $shown] = $this->mails()->inbox([
            'box' => $box,
            'search' => (string) $request->query('search', ''),
        ], $page);

        return response()->json([
            'mails' => $mails,
            'box' => $box,
            'page' => $page,
            'shown' => $shown,
            'unread_count' => $this->mails()->unreadCount(),
            'scope' => $this->scopePayload($request),
        ]);
    }

    public function show(Request $request, int $mailId)
    {
        $this->assertHq($request);

        $mail = $this->mails()->find($mailId);
        if (! $mail) {
            abort(404, 'Mail not found.');
        }

        $thread = $this->mails()->thread($mail);

        // Opening a mail marks it read — same as the website.
        if (! ($mail->is_read ?? false)) {
            $this->mails()->markRead((int) $mail->id);
            $mail->is_read = true;
        }

        return response()->json([
            'mail' => $mail,
            'thread' => collect($thread['mails']),
            'replies' => collect($thread['replies']),
            'attachments' => collect($this->mails()->attachmentsFor((int) $mail->id)),
            'info_address' => config('info_mail.address'),
            'signature' => $this->userName($request),
            'reply_limits' => [
                'files' => self::MAX_REPLY_FILES,
                'file_mb' => (int) (self::MAX_REPLY_FILE_KILOBYTES / 1024),
                'total_mb' => (int) (self::MAX_REPLY_TOTAL_BYTES / 1048576),
            ],
        ]);
    }

    public function reply(Request $request, int $mailId)
    {
        $this->assertHq($request);

        $mail = $this->mails()->find($mailId);
        if (! $mail) {
            abort(404, 'Mail not found.');
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

        // The total must stay under post_max_size: over it PHP discards the
        // whole POST and the request arrives as an unexplained 419 instead.
        $total = array_sum(array_map(fn ($f) => $f->getSize(), $files));
        if ($total > self::MAX_REPLY_TOTAL_BYTES) {
            $this->fail(['attachments' => 'Those files add up to more than '.((int) (self::MAX_REPLY_TOTAL_BYTES / 1048576)).' MB limit for one answer.']);
        }

        $sessionUser = $request->attributes->get('staff_user');
        $staff = (object) (is_array($sessionUser) ? $sessionUser : []);

        $result = $this->mails()->reply(
            $mail,
            $validated['body'],
            $staff,
            $staff->name ?? 'Customer Care',
            $files,
        );

        if (! $result['ok']) {
            $this->fail(['error' => $result['message']]);
        }

        return response()->json(['message' => $result['message']]);
    }

    public function toggleRead(Request $request, int $mailId)
    {
        $this->assertHq($request);

        $mail = $this->mails()->find($mailId);
        if (! $mail) {
            abort(404, 'Mail not found.');
        }

        $makeRead = ! ($mail->is_read ?? false);
        $this->mails()->markRead($mailId, $makeRead);

        return response()->json(['message' => $makeRead ? 'Marked as read.' : 'Marked as unread.']);
    }

    public function star(Request $request, int $mailId)
    {
        $this->assertHq($request);

        $mail = $this->mails()->find($mailId);
        if (! $mail) {
            abort(404, 'Mail not found.');
        }

        $starred = ! ($mail->is_starred ?? false);
        $this->mails()->setStarred($mailId, $starred);

        return response()->json(['message' => $starred ? 'Starred.' : 'Star removed.']);
    }

    public function status(Request $request, int $mailId)
    {
        $this->assertHq($request);

        $validated = $request->validate([
            'status' => ['required', 'in:new,replied,closed'],
        ]);

        $mail = $this->mails()->find($mailId);
        if (! $mail) {
            abort(404, 'Mail not found.');
        }

        $this->mails()->setStatus($mailId, $validated['status']);

        return response()->json(['message' => 'Mail moved to '.$validated['status'].'.']);
    }

    public function destroy(Request $request, int $mailId)
    {
        $this->assertHq($request);

        $mail = $this->mails()->find($mailId);
        if (! $mail) {
            abort(404, 'Mail not found.');
        }

        $this->mails()->delete($mailId);

        return response()->json(['message' => 'Mail deleted.']);
    }
}
