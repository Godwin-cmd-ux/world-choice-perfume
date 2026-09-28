<?php

namespace App\Http\Controllers\CustomerCare;

use App\Http\Controllers\Controller;
use App\Services\InfoMailService;
use Illuminate\Http\Request;

/**
 * Customer Care → Mails: the info@worldchoiceperfume.com mailbox.
 *
 * Head Quarters customer care only (the `customer-care.hq` middleware on the
 * routes), because info@ is a company-wide address rather than a branch one.
 */
class InfoMailController extends Controller
{
    public function __construct(private InfoMailService $mails)
    {
    }

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

        return view('customer-care.mails.index', [
            'mails' => $mails,
            'box' => $box,
            'search' => (string) $request->query('search', ''),
            'page' => $page,
            'shown' => $shown,
            'unreadCount' => $this->mails->unreadCount(),
        ]);
    }

    public function show($mailId)
    {
        $mail = $this->mails->find((int) $mailId);
        if (! $mail) {
            abort(404);
        }

        $thread = $this->mails->thread($mail);

        if (! ($mail->is_read ?? false)) {
            $this->mails->markRead((int) $mail->id);
            $mail->is_read = true;
        }

        return view('customer-care.mails.show', [
            'mail' => $mail,
            'thread' => collect($thread['mails']),
            'replies' => collect($thread['replies']),
            'attachments' => $this->mails->attachmentsFor((int) $mail->id),
            'infoAddress' => config('info_mail.address'),
            'signature' => auth()->user()->name ?? 'Customer Care',
        ]);
    }

    /**
     * Serve one attachment so it can be read in the browser.
     *
     * Only the browsers that can be trusted to render a file inline get to
     * see it that way; anything else is sent as a download, because the bytes
     * came from a stranger and the file name is theirs too.
     */
    public function attachment(Request $request, $mailId, $attachmentId)
    {
        $mail = $this->mails->find((int) $mailId);
        if (! $mail) {
            abort(404);
        }

        $found = $this->mails->attachment((int) $mail->id, (int) $attachmentId);
        if (! $found) {
            abort(404);
        }

        $row = $found['row'];
        $name = (string) $row['file_name'];
        $mime = (string) ($row['mime_type'] ?: 'application/octet-stream');
        $wantsDownload = $request->boolean('download');
        $inline = ! $wantsDownload && $this->canShowInline($mime, $name);

        if (! $inline) {
            $mime = 'application/octet-stream';
        }

        return response($found['contents'], 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string) strlen($found['contents']),
            'Content-Disposition' => ($inline ? 'inline' : 'attachment')
                . '; filename="' . str_replace('"', '', $name) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }

    /**
     * Types a browser can be left to render on its own. SVG and HTML are
     * excluded on purpose: both can run script, so they are only ever
     * downloaded.
     */
    private function canShowInline(string $mime, string $name): bool
    {
        $safe = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/bmp', 'application/pdf', 'text/plain'];

        if (in_array(strtolower($mime), $safe, true)) {
            return true;
        }

        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'pdf', 'txt'], true);
    }

    public function reply(Request $request, $mailId)
    {
        $mail = $this->mails->find((int) $mailId);
        if (! $mail) {
            abort(404);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
        ]);

        $result = $this->mails->reply(
            $mail,
            $validated['body'],
            auth()->user(),
            auth()->user()->name ?? 'Customer Care',
        );

        return redirect()
            ->route('customer-care.mails.show', $mail->id)
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function toggleRead(Request $request, $mailId)
    {
        $mail = $this->mails->find((int) $mailId);
        if (! $mail) {
            abort(404);
        }

        $makeRead = ! ($mail->is_read ?? false);
        $this->mails->markRead((int) $mail->id, $makeRead);

        return back()->with('success', $makeRead ? 'Marked as read.' : 'Marked as unread.');
    }

    public function star(Request $request, $mailId)
    {
        $mail = $this->mails->find((int) $mailId);
        if (! $mail) {
            abort(404);
        }

        $starred = ! ($mail->is_starred ?? false);
        $this->mails->setStarred((int) $mail->id, $starred);

        return back()->with('success', $starred ? 'Starred.' : 'Star removed.');
    }

    public function status(Request $request, $mailId)
    {
        $mail = $this->mails->find((int) $mailId);
        if (! $mail) {
            abort(404);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:new,replied,closed'],
        ]);

        $this->mails->setStatus((int) $mail->id, $validated['status']);

        return back()->with('success', 'Mail moved to ' . $validated['status'] . '.');
    }

    public function destroy($mailId)
    {
        $mail = $this->mails->find((int) $mailId);
        if (! $mail) {
            abort(404);
        }

        $this->mails->delete((int) $mail->id);

        return redirect()
            ->route('customer-care.mails.index')
            ->with('success', 'Mail deleted.');
    }
}
