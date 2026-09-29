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
            'attachments' => collect($this->mails->attachmentsFor((int) $mail->id)),
            'infoAddress' => config('info_mail.address'),
            'signature' => auth()->user()->name ?? 'Customer Care',
            'transport' => $this->transportStatus(),
        ]);
    }

    /**
     * Describe how this server would actually send, so a reply that fails
     * can be read against the configuration instead of guessed at.
     *
     * Only the mailer name, host and whether credentials exist are shown.
     * No key, password or token is ever passed to the view.
     */
    private function transportStatus(): array
    {
        $mailer = (string) config('mail.default');
        $label = $mailer;

        if ($mailer === 'smtp') {
            $host = (string) config('mail.mailers.smtp.host');
            $port = config('mail.mailers.smtp.port');
            $label = $host.':'.$port;
        }

        $credentialsPresent = $mailer === 'log' || (bool) match ($mailer) {
            'resend' => config('mail.mailers.resend.key'),
            'smtp' => config('mail.mailers.smtp.password'),
            default => false,
        };

        // Resend's SMTP host on 465 is not reachable from the app server:
        // the container times out before authenticating. Sending has to go
        // through the HTTPS API instead, so this pairing is called out.
        $smtpToResend = $mailer === 'smtp'
            && str_contains((string) config('mail.mailers.smtp.host'), 'resend');

        return [
            'mailer' => $mailer,
            'label' => $label,
            'credentialsPresent' => $credentialsPresent,
            'warning' => match (true) {
                $mailer === 'log' => 'Mail is set to log, so nothing is actually sent.',
                $smtpToResend => 'SMTP to Resend times out on this server. Set MAIL_MAILER=resend and RESEND_API_KEY.',
                ! $credentialsPresent => 'This mailer has no credentials, so sending will fail.',
                default => null,
            },
        ];
    }

    /**
     * Send one attachment so it can be read in the browser.
     *
     * The file is streamed from disk rather than held in memory, and it is
     * deleted afterwards. Only types a browser can be trusted to render are
     * shown inline; anything else, including SVG and HTML, is sent as a
     * download, because the bytes came from a stranger and so did the type.
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
        $mime = strtolower(trim((string) ($row['mime_type'] ?: 'application/octet-stream')));
        $name = $this->displayFileName((string) $row['file_name'], $mime);
        $inline = ! $request->boolean('download') && $this->canShowInline($mime, (string) $row['file_name']);

        return response()
            ->file($found['file'], [
                'Content-Type' => $inline ? $mime : 'application/octet-stream',
                'Content-Length' => (string) $found['size'],
                'Content-Disposition' => ($inline ? 'inline' : 'attachment')
                    .'; filename="'.str_replace('"', '', $name).'"',
                'X-Content-Type-Options' => 'nosniff',
                // Even when the sender's type is trusted enough to show, the
                // file gets no scripts, no plugins and no same-origin reach.
                'Content-Security-Policy' => "default-src 'none'; img-src 'self'; media-src 'self'; "
                    ."style-src 'unsafe-inline'; sandbox; frame-ancestors 'self'",
                'Cache-Control' => 'private, max-age=0, no-store',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * The name to offer the file under.
     *
     * A sender can name a file with no extension at all, which leaves a
     * download the receiver cannot open. When the type is known, the matching
     * extension is added so the file keeps the format it arrived in.
     */
    private function displayFileName(string $name, string $mime): string
    {
        if (pathinfo($name, PATHINFO_EXTENSION) !== '') {
            return $name;
        }

        $extension = match ($mime) {
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/ogg' => 'ogv',
            'video/quicktime' => 'mov',
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/wav', 'audio/x-wav' => 'wav',
            'audio/webm' => 'weba',
            'image/jpeg' => 'jpg',
            'image/svg+xml' => 'svg',
            'text/plain' => 'txt',
            'application/pdf' => 'pdf',
            'application/zip' => 'zip',
            default => '',
        };

        return $extension === '' ? $name : $name.'.'.$extension;
    }

    /**
     * Whether the browser may be left to render this itself.
     *
     * Chosen from the declared type first, so a video sent without an
     * extension still plays, and from the extension when the sender gave no
     * usable type. SVG and HTML are absent on purpose: both can run script.
     */
    private function canShowInline(string $mime, string $name): bool
    {
        $safe = [
            'image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/bmp',
            'application/pdf', 'text/plain',
            'video/mp4', 'video/webm', 'video/ogg',
            'audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/x-wav', 'audio/webm', 'audio/mp4',
        ];

        if (in_array($mime, $safe, true)) {
            return true;
        }

        // A declared type that is missing or a generic stand-in tells us
        // nothing, so fall back to what the name suggests.
        if ($mime !== '' && $mime !== 'application/octet-stream' && str_contains($mime, '/')) {
            return false;
        }

        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return in_array($extension, [
            'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp',
            'pdf', 'txt',
            'mp4', 'webm', 'ogv', 'mov',
            'mp3', 'ogg', 'oga', 'wav',
        ], true);
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

        return back()->with('success', 'Mail moved to '.$validated['status'].'.');
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
