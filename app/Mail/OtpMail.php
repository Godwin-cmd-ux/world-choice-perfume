<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otpCode,
        public string $purpose,
        public string $userName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "World Choice Perfumes - Your {$this->purpose} Code",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#1a1a1a;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#1a1a1a;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#2a2a2a;border-radius:12px;overflow:hidden;border:1px solid #d4a853;">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#1a1a1a;padding:30px;text-align:center;border-bottom:2px solid #d4a853;">
                            <h1 style="color:#d4a853;font-size:24px;margin:0;letter-spacing:2px;">WORLD CHOICE PERFUMES</h1>
                            <p style="color:#999;font-size:12px;margin:5px 0 0 0;letter-spacing:1px;">AUTHENTIC & PREMIUM FRAGRANCES</p>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:40px 30px;text-align:center;">
                            <h2 style="color:#d4a853;font-size:20px;margin:0 0 10px 0;">Hello {$this->userName}</h2>
                            <p style="color:#ccc;font-size:14px;line-height:1.6;margin:0 0 25px 0;">
                                Your <strong style="color:#fff;">{$this->purpose}</strong> verification code is:
                            </p>
                            <div style="background-color:#1a1a1a;border:2px dashed #d4a853;border-radius:8px;padding:20px;margin:0 0 25px 0;">
                                <span style="color:#d4a853;font-size:36px;font-weight:bold;letter-spacing:8px;">{$this->otpCode}</span>
                            </div>
                            <p style="color:#999;font-size:13px;margin:0 0 10px 0;">
                                This code expires in <strong style="color:#d4a853;">10 minutes</strong>.
                            </p>
                            <p style="color:#999;font-size:13px;margin:0;">
                                If you did not request this code, please ignore this email.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#1a1a1a;padding:20px 30px;text-align:center;border-top:1px solid #333;">
                            <p style="color:#666;font-size:11px;margin:0;">&copy; 2026 World Choice Perfumes. All rights reserved.</p>
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
}
