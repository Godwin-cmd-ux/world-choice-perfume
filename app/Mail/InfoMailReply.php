<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The answer a Customer Care member writes back to a mail sent to
 * info@worldchoiceperfume.com. Sent as info@ so the customer keeps talking to
 * the same address they wrote to, and threaded onto the original mail so the
 * conversation stays in one place in their mail client.
 */
class InfoMailReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $replyToName,
        public string $mailSubject,
        public string $bodyHtml,
        public string $originalText,
        public array $attachmentNames = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
            text: 'emails.info-reply-text',
            with: ['bodyText' => $this->buildText()],
        );
    }

    /**
     * Plain-text twin of the HTML mail. Some mail clients and phone data
     * savers only show this one, and it keeps the quoted original readable.
     */
    private function buildText(): string
    {
        $text = e(trim($this->bodyHtml));
        $original = e(trim($this->originalText));

        if ($this->attachmentNames) {
            $text .= "\n\nAttached:";
            foreach ($this->attachmentNames as $name) {
                $text .= "\n- ".e((string) $name);
            }
        }

        if ($original === '') {
            return $text."\n\n--\nWorld Choice Perfume - info@worldchoiceperfume.com";
        }

        $quoted = '';
        foreach (explode("\n", $original) as $line) {
            $quoted .= '> '.$line."\n";
        }

        return $text."\n\nOn ".$this->replyToName." wrote:\n".$quoted
            ."\n--\nWorld Choice Perfume - info@worldchoiceperfume.com";
    }

    private function buildHtml(): string
    {
        $body = nl2br(e(trim($this->bodyHtml)));
        $original = trim($this->originalText);
        $files = $this->attachmentNames ? $this->buildAttachmentList() : '';

        // The customer's own words underneath, so whoever answers a forwarded
        // or printed thread can see what they are replying to.
        $quoted = $original === '' ? '' : '<tr><td style="padding:0 26px 26px 26px;">'
            .'<div style="margin:0;padding:14px 16px;border-left:3px solid #d4a853;background:#faf7f0;color:#666;font-size:12px;line-height:1.6;">'
            .'<p style="margin:0 0 6px 0;color:#b08d3f;text-transform:uppercase;letter-spacing:1px;font-size:10px;">On '.e($this->replyToName).' wrote</p>'
            .'<div style="margin:0;">'.nl2br(e($original)).'</div>'
            .'</div></td></tr>';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f6f6f6;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f6f6f6;padding:30px 16px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e2e2;">
                    <tr>
                        <td style="background-color:#1a1a1a;padding:24px;text-align:center;border-bottom:2px solid #d4a853;">
                            <h1 style="color:#d4a853;font-size:20px;margin:0;letter-spacing:2px;">WORLD CHOICE PERFUMES</h1>
                            <p style="color:#999;font-size:11px;margin:5px 0 0 0;letter-spacing:1px;">BE SMART, NUKIA KIJANJA</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 26px;color:#333;font-size:14px;line-height:1.7;">
                            {$body}
                        </td>
                    </tr>
                    {$files}
                    {$quoted}
                    <tr>
                        <td style="padding:18px 26px;border-top:1px solid #eee;color:#999;font-size:11px;">
                            <p style="margin:0 0 4px 0;">World Choice Perfume &middot; info@worldchoiceperfume.com</p>
                            <p style="margin:0;color:#bbb;">You are receiving this because you wrote to us.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * A plain list of what is attached, so a client that hides the attachment
     * panel still tells the customer a file is waiting.
     */
    private function buildAttachmentList(): string
    {
        $items = '';
        foreach ($this->attachmentNames as $name) {
            $items .= '<li style="margin:0 0 4px 0;color:#666;font-size:12px;">'
                .e((string) $name).'</li>';
        }

        return '<tr><td style="padding:0 26px 26px 26px;">'
            .'<div style="padding:12px 16px;border:1px solid #eee;border-radius:8px;background:#fbfbfb;">'
            .'<p style="margin:0 0 8px 0;color:#b08d3f;text-transform:uppercase;letter-spacing:1px;font-size:10px;">'
            .'Attached ('.count($this->attachmentNames).')</p>'
            .'<ul style="margin:0;padding-left:18px;">'.$items.'</ul>'
            .'</div></td></tr>';
    }
}
