<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Parser for a raw RFC 822 email.
 *
 * Cloudflare Email Routing hands us the untouched message, so the fields the
 * mail page shows (sender name, subject, plain text, HTML, attachment names)
 * are read out of the MIME structure here instead of being trusted from a
 * JSON payload. That also means the same endpoint works if the mail is later
 * delivered by another provider — anything that can POST the raw source.
 */
class RawEmailParser
{
    /** How deep to walk multipart/nested alternatives before giving up. */
    private const MAX_DEPTH = 6;

    /** Per-file and total attachment ceilings, so one mail cannot fill storage. */
    private const MAX_ATTACHMENT_BYTES = 20 * 1024 * 1024;
    private const MAX_ATTACHMENTS = 25;
    private const MAX_TOTAL_ATTACHMENT_BYTES = 40 * 1024 * 1024;

    /**
     * @return array{headers: array<string, string>, message_id: ?string, in_reply_to: ?string,
     *               references: ?string, from_email: ?string, from_name: ?string,
     *               to_email: ?string, cc: ?string, bcc: ?string, reply_to: ?string,
     *               subject: ?string, date: ?string, body_text: ?string, body_html: ?string,
     *               attachments: array<int, array{filename: string, mime: string, content: string}>,
     *               attachment_names: string[], spf_result: ?string, dkim_result: ?string}
     */
    public function parse(string $raw): array
    {
        [$headerBlock, $bodyBlock] = $this->split($raw);
        $headers = $this->parseHeaders($headerBlock);

        $text = null;
        $html = null;
        $attachments = [];
        $this->walkPart($headers, $bodyBlock, 0, $text, $html, $attachments);

        if ($text === null && $html !== null) {
            // HTML-only mail: keep a readable plain version for the list.
            $text = $this->htmlToText($html);
        }

        $from = $this->firstAddress($headers['from'] ?? null);

        return [
            'headers' => $headers,
            'message_id' => $this->angleId($headers['message-id'] ?? null),
            'in_reply_to' => $this->angleId($headers['in-reply-to'] ?? null),
            'references' => isset($headers['references']) ? mb_substr($headers['references'], 0, 2000) : null,
            'from_email' => $from['email'],
            'from_name' => $from['name'],
            'to_email' => $this->firstAddress($headers['to'] ?? null)['email'],
            'cc' => $this->allAddresses($headers['cc'] ?? null),
            'bcc' => $this->allAddresses($headers['bcc'] ?? null),
            'reply_to' => $this->firstAddress($headers['reply-to'] ?? null)['email'],
            'subject' => $headers['subject'] ?? null,
            'date' => $headers['date'] ?? null,
            'body_text' => $text,
            'body_html' => $html,
            'attachments' => $attachments,
            'attachment_names' => array_map(fn ($a) => $a['filename'], $attachments),
            'spf_result' => $this->authResult($headers['authentication-results'] ?? null, 'spf'),
            'dkim_result' => $this->authResult($headers['authentication-results'] ?? null, 'dkim')
                ?? (isset($headers['dkim-signature']) ? 'signed' : null),
        ];
    }

    /**
     * Split a message into its header block and its body.
     *
     * @return array{0: string, 1: string}
     */
    private function split(string $raw): array
    {
        $raw = str_replace("\r\n", "\n", $raw);
        // Some senders pad the first lines; a leading blank line is not the
        // header/body separator, so look for the first *empty* line.
        $position = strpos($raw, "\n\n");

        if ($position === false) {
            return [rtrim($raw), ''];
        }

        return [substr($raw, 0, $position), substr($raw, $position + 2)];
    }

    /**
     * @return array<string, string> lower-cased header name => unfolded value
     */
    private function parseHeaders(string $block): array
    {
        $headers = [];

        foreach (explode("\n", $block) as $line) {
            if ($line === '') {
                continue;
            }
            // A line starting with a space continues the previous header.
            if (($line[0] === ' ' || $line[0] === "\t") && $headers !== []) {
                $last = array_key_last($headers);
                $headers[$last] .= ' ' . trim($line);
                continue;
            }
            if (! str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }

        foreach ($headers as $name => $value) {
            // Subjects and display names arrive as =?UTF-8?B?...?= words.
            $headers[$name] = $this->decodeWords($value);
        }

        return $headers;
    }

    /**
     * Read one MIME part, recursing into multipart containers.
     *
     * @param  array<string, string>  $headers
     * @param  string[]  $attachments
     */
    private function walkPart(array $headers, string $body, int $depth, ?string &$text, ?string &$html, array &$attachments): void
    {
        if ($depth > self::MAX_DEPTH || $body === '') {
            return;
        }

        $type = strtolower(trim(explode(';', $headers['content-type'] ?? 'text/plain')[0]));

        if (str_starts_with($type, 'multipart/')) {
            $boundary = $this->boundary($headers['content-type'] ?? '');
            if ($boundary === null) {
                return;
            }
            foreach ($this->splitParts($body, $boundary) as $part) {
                [$partHeaders, $partBody] = $this->split($part);
                $this->walkPart($this->parseHeaders($partHeaders), $partBody, $depth + 1, $text, $html, $attachments);
            }

            return;
        }

        $filename = $this->filename($headers);
        if ($filename !== null) {
            $this->collectAttachment($headers, $body, $filename, $type, $attachments);

            return; // an attachment is never also the message body
        }

        $decoded = $this->decodeBody($body, $headers['content-transfer-encoding'] ?? '', $headers['content-type'] ?? '');

        if ($type === 'text/html') {
            // Keep the first HTML alternative; another one is usually a
            // plain-text twin of the same message.
            $html ??= $decoded;
        } elseif ($type === 'text/plain' || $type === '') {
            $text ??= $decoded;
        }
    }

    /**
     * Keep one decoded attachment, skipping it if it breaks the size or count
     * ceilings. A mail over the limit still arrives; only the big files are
     * left out, and the reason is noted so it is visible rather than silent.
     *
     * @param  array<int, array{filename: string, mime: string, content: string}>  $attachments
     */
    private function collectAttachment(
        array $headers,
        string $body,
        string $filename,
        string $type,
        array &$attachments
    ): void {
        if (count($attachments) >= self::MAX_ATTACHMENTS) {
            Log::info("RawEmailParser: stopped at {$filename} — over the " . self::MAX_ATTACHMENTS . ' attachment limit');

            return;
        }

        $content = $this->decodeBody($body, $headers['content-transfer-encoding'] ?? '', $headers['content-type'] ?? '');

        if (strlen($content) > self::MAX_ATTACHMENT_BYTES) {
            Log::info("RawEmailParser: skipped {$filename} — " . strlen($content) . ' bytes is over the per-file limit');

            return;
        }

        $total = self::MAX_TOTAL_ATTACHMENT_BYTES;
        foreach ($attachments as $existing) {
            $total -= strlen($existing['content']);
        }
        if (strlen($content) > $total) {
            Log::info("RawEmailParser: skipped {$filename} — it would pass the total attachment limit");

            return;
        }

        $attachments[] = [
            'filename' => $this->safeFilename($filename),
            'mime' => $this->attachmentMime($type, $filename),
            'content' => $content,
        ];
    }

    /**
     * A sender-supplied name ends up in a path and in a download header, so
     * strip the directory parts, control characters and separators from it.
     */
    private function safeFilename(string $filename): string
    {
        $name = basename(str_replace('\\', '/', $filename));
        $name = preg_replace('/[\x00-\x1F\x7F"\\/:*?<>|]+/u', '_', $name) ?? '';
        $name = trim($name);

        if ($name === '' || $name === '.' || $name === '..') {
            $name = 'attachment';
        }

        return mb_substr($name, 0, 180);
    }

    /**
     * The declared type, falling back to one guessed from the extension when
     * the sender wrote no Content-Type or wrote a useless one.
     */
    private function attachmentMime(string $type, string $filename): string
    {
        $type = trim(explode(';', $type)[0]);

        if ($type !== '' && $type !== 'application/octet-stream' && preg_match('#^[a-z0-9.+-]+/[a-z0-9.+-]+$#i', $type)) {
            return strtolower($type);
        }

        return match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'zip' => 'application/zip',
            'json' => 'application/json',
            default => 'application/octet-stream',
        };
    }

    /**
     * @return string[]
     */
    private function splitParts(string $body, string $boundary): array
    {
        // The leading newline makes sure a body that opens with the boundary
        // still splits; the text before the first boundary is the preamble
        // and the text after the closing one is the epilogue — neither is a
        // part, so both ends are dropped.
        $parts = preg_split('/\n--' . preg_quote($boundary, '/') . '(--)?[ \t]*\n?/', "\n" . $body);

        if (! is_array($parts) || count($parts) < 3) {
            return [];
        }

        array_shift($parts);
        array_pop($parts);

        return array_values($parts);
    }

    private function boundary(string $contentType): ?string
    {
        if (preg_match('/boundary\s*=\s*"?([^";]+)"?/i', $contentType, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    /**
     * The file name of a part, whether it is named in Content-Disposition
     * (normal attachment) or in a Content-Type name= (most inline files).
     */
    private function filename(array $headers): ?string
    {
        $disposition = strtolower($headers['content-disposition'] ?? '');

        if (str_starts_with($disposition, 'attachment')) {
            return $this->decodeWords($this->parameter($headers['content-disposition'], 'filename'))
                ?? 'attachment';
        }

        if ($disposition === '' && ! isset($headers['content-disposition'])) {
            $name = $this->parameter($headers['content-type'] ?? '', 'name');
            if ($name !== null && $name !== 'inline') {
                return $this->decodeWords($name);
            }
        }

        return null;
    }

    /**
     * Pull a `key="value"` / `key=value` parameter out of a header.
     */
    private function parameter(string $header, string $key): ?string
    {
        if (preg_match('/' . preg_quote($key, '/') . '\s*=\s*"([^"]*)"/i', $header, $m)) {
            return $m[1];
        }
        if (preg_match('/' . preg_quote($key, '/') . '\s*=\s*([^;\s]+)/i', $header, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Undo the transfer encoding and charset of a part.
     */
    private function decodeBody(string $body, string $encoding, string $contentType): string
    {
        $encoding = strtolower(trim($encoding));

        $body = match (true) {
            str_contains($encoding, 'base64') => (string) base64_decode(preg_replace('/\s+/', '', $body)),
            str_contains($encoding, 'quoted-printable') => quoted_printable_decode($body),
            default => $body,
        };

        $charset = $this->parameter($contentType, 'charset') ?: 'utf-8';
        $charset = strtoupper(trim($charset, " \"'"));

        if ($charset !== 'UTF-8' && function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($body, 'UTF-8', $charset);
            if (is_string($converted) && $converted !== '') {
                $body = $converted;
            }
        }

        return trim($body);
    }

    /**
     * Decode RFC 2047 encoded words (=?UTF-8?B?...?=).
     *
     * iconv_mime_decode is tried first but some builds hand back the encoded
     * word untouched (only losing its last character), so the result is
     * checked and the manual decoder below is used whenever anything is still
     * encoded.
     */
    private function decodeWords(string $value): string
    {
        if (! str_contains($value, '=?')) {
            return $value;
        }

        if (function_exists('iconv_mime_decode')) {
            $decoded = iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if (is_string($decoded) && $decoded !== '' && ! str_contains($decoded, '=?')) {
                return $decoded;
            }
        }

        $manual = preg_replace_callback('/=\?([^?]+)\?([BbQq])\?([^?]*)\?=/', function ($m) {
            $text = strtoupper($m[2]) === 'B'
                ? (string) base64_decode($m[3], false)
                : quoted_printable_decode(str_replace('_', ' ', $m[3]));

            return function_exists('mb_convert_encoding')
                ? (string) @mb_convert_encoding($text, 'UTF-8', $m[1])
                : $text;
        }, $value);

        return is_string($manual) && $manual !== '' ? $manual : $value;
    }

    /**
     * The address part of `Name <someone@example.com>`.
     *
     * @return array{email: ?string, name: ?string}
     */
    private function firstAddress(?string $value): array
    {
        $list = $this->allAddresses($value);
        $first = $list[0] ?? null;

        if ($first === null) {
            return ['email' => null, 'name' => null];
        }

        $email = $first['email'];
        $name = $first['name'] ?: ($email ? explode('@', $email)[0] : null);

        return ['email' => $email, 'name' => $name];
    }

    /**
     * Every mailbox in a To/Cc/Bcc header, as `Name <address>` strings.
     *
     * @return array<int, array{email: ?string, name: ?string}>
     */
    private function allAddresses(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $addresses = [];
        foreach (preg_split('/,(?![^<]*>)/', $value) ?: [] as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }

            if (preg_match('/^(.*)<([^>]+)>\s*$/s', $chunk, $m)) {
                $name = trim(trim($m[1]), '"');
                $email = trim($m[2]);
            } else {
                $name = '';
                $email = $chunk;
            }

            // Drop a group label like "undisclosed-recipients:;".
            if (str_contains($email, ':')) {
                continue;
            }

            $addresses[] = [
                'email' => $this->cleanAddress($email),
                'name' => $this->decodeWords($name) ?: null,
            ];
        }

        return $addresses;
    }

    private function cleanAddress(string $email): ?string
    {
        $email = trim($email, " \t<>");

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Normalise a Message-ID to the bracketed form used in headers.
     */
    private function angleId(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $first = trim(explode(' ', trim($value))[0] ?? '');
        $first = trim($first, '<>');

        return $first === '' ? null : "<{$first}>";
    }

    /**
     * The SPF verdict from an Authentication-Results header, e.g. "pass".
     */
    private function authResult(?string $header, string $mechanism): ?string
    {
        if ($header === null) {
            return null;
        }

        if (preg_match('/\b' . $mechanism . '\s*=\s*([a-z]+)/i', $header, $m)) {
            return strtolower($m[1]);
        }

        return null;
    }

    /**
     * A rough plain-text version of an HTML body for previews and search.
     */
    private function htmlToText(string $html): string
    {
        $text = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $text = preg_replace('#<br\s*/?>|</p>|</div>|</tr>|</h[1-6]>#i', "\n", $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }
}
