<?php

namespace Tests\Unit;

use App\Services\RawEmailParser;
use PHPUnit\Framework\TestCase;

class RawEmailParserTest extends TestCase
{
    private RawEmailParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new RawEmailParser();
    }

    public function test_it_reads_a_plain_text_mail(): void
    {
        $parsed = $this->parser->parse(implode("\r\n", [
            'From: Amina Yusuf <amina@example.co.tz>',
            'To: info@worldchoiceperfume.com',
            'Subject: Reef 33 price',
            'Date: Mon, 28 Sep 2026 09:14:22 +0300',
            'Message-ID: <plain-1@example.co.tz>',
            'Content-Type: text/plain; charset=UTF-8',
            '',
            'Jambo,',
            'How much is Reef 33?',
            'Asante, Amina',
            '',
        ]));

        $this->assertSame('amina@example.co.tz', $parsed['from_email']);
        $this->assertSame('Amina Yusuf', $parsed['from_name']);
        $this->assertSame('info@worldchoiceperfume.com', $parsed['to_email']);
        $this->assertSame('Reef 33 price', $parsed['subject']);
        $this->assertSame('<plain-1@example.co.tz>', $parsed['message_id']);
        $this->assertStringContainsString('How much is Reef 33?', $parsed['body_text']);
        $this->assertSame([], $parsed['attachment_names']);
    }

    public function test_it_decodes_encoded_words_in_the_subject(): void
    {
        $encoded = '=?UTF-8?B?' . base64_encode('Sauti ya Picked Up 50ml') . '?=';

        $parsed = $this->parser->parse("From: a@example.com\r\nTo: info@worldchoiceperfume.com\r\nSubject: {$encoded}\r\n\r\nbody\r\n");

        $this->assertSame('Sauti ya Picked Up 50ml', $parsed['subject']);
    }

    public function test_it_reads_a_nested_multipart_mail_with_attachments(): void
    {
        $raw = $this->multipartMail();

        $parsed = $this->parser->parse($raw);

        $this->assertStringStartsWith('multipart/mixed', $parsed['headers']['content-type'] ?? '');
        $this->assertStringContainsString('2 bottles of Reef 33', $parsed['body_text']);
        $this->assertStringContainsString('<b>2 bottles</b>', $parsed['body_html']);
        $this->assertSame(['order.pdf'], $parsed['attachment_names']);
        $this->assertSame(['sales@worldchoiceperfume.com'], array_column($parsed['cc'], 'email'));
    }

    public function test_it_picks_up_thread_headers_and_authentication_results(): void
    {
        $parsed = $this->parser->parse($this->multipartMail());

        $this->assertSame('<older-1@example.co.tz>', $parsed['in_reply_to']);
        $this->assertSame('<older-1@example.co.tz> <another-1@example.co.tz>', $parsed['references']);
        $this->assertSame('pass', $parsed['spf_result']);
        $this->assertSame('pass', $parsed['dkim_result']);
    }

    public function test_a_mail_without_a_sender_is_still_parsed(): void
    {
        $parsed = $this->parser->parse("To: info@worldchoiceperfume.com\r\nSubject: No sender\r\n\r\nbody\r\n");

        $this->assertNull($parsed['from_email']);
        $this->assertSame('No sender', $parsed['subject']);
    }

    /**
     * text/plain (quoted-printable) + text/html (base64) as alternatives of a
     * multipart/mixed mail that also carries a PDF attachment.
     */
    private function multipartMail(): string
    {
        return implode("\r\n", [
            'From: Amina Yusuf <amina@example.co.tz>',
            'To: info@worldchoiceperfume.com',
            'Cc: sales@worldchoiceperfume.com',
            'Subject: Order request',
            'Date: Mon, 28 Sep 2026 09:14:22 +0300',
            'Message-ID: <orig-1@example.co.tz>',
            'In-Reply-To: <older-1@example.co.tz>',
            'References: <older-1@example.co.tz> <another-1@example.co.tz>',
            'Authentication-Results: mx.cloudflare.com; spf=pass smtp.mailfrom=example.co.tz; dkim=pass header.i=@example.co.tz',
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="OUTER"',
            '',
            '--OUTER',
            'Content-Type: multipart/alternative; boundary="INNER"',
            '',
            '--INNER',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            '',
            'Jambo! I would like to order 2 bottles of Reef 33 50ml.=0D=0AAsante, Amina',
            '--INNER',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            '',
            base64_encode('<p>Jambo! I would like to order <b>2 bottles</b> of Reef 33 50ml.</p>'),
            '--INNER--',
            '--OUTER',
            'Content-Type: application/pdf; name="order.pdf"',
            'Content-Disposition: attachment; filename="order.pdf"',
            'Content-Transfer-Encoding: base64',
            '',
            base64_encode('%PDF-1.4 fake'),
            '--OUTER--',
            '',
        ]);
    }
}
