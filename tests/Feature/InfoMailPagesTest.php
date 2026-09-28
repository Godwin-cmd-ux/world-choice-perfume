<?php

namespace Tests\Feature;

use App\Services\InfoMailService;
use Tests\TestCase;

class InfoMailPagesTest extends TestCase
{
    private function fakeMail(): object
    {
        return (object) [
            'id' => 7,
            'message_id' => '<orig-1@example.co.tz>',
            'in_reply_to' => null,
            'reference_ids' => null,
            'from_email' => 'amina@example.co.tz',
            'from_name' => 'Amina Yusuf',
            'to_email' => 'info@worldchoiceperfume.com',
            'cc' => null,
            'bcc' => null,
            'reply_to' => null,
            'subject' => 'Reef 33 price',
            'body_text' => "Jambo,\n\nHow much is Reef 33 50ml?\n\nAsante, Amina",
            'body_html' => '<p>Jambo,</p><p>How much is <b>Reef 33 50ml</b>?</p>',
            'raw_email' => "From: Amina <amina@example.co.tz>\r\nSubject: Reef 33 price\r\n\r\nbody",
            'headers' => ['from' => 'Amina Yusuf <amina@example.co.tz>'],
            'attachment_names' => 'order.pdf',
            'has_attachments' => true,
            'spf_result' => 'pass',
            'dkim_result' => 'pass',
            'is_read' => false,
            'is_starred' => true,
            'status' => 'new',
            'thread_key' => '<orig-1@example.co.tz>',
            'parent_id' => null,
            'received_at' => '2026-09-28T09:14:22+03:00',
            'read_at' => null,
            'replied_at' => null,
            'created_at' => '2026-09-28T09:14:22+03:00',
            'updated_at' => '2026-09-28T09:14:22+03:00',
        ];
    }

    private function bindService(): void
    {
        $service = new class($this->fakeMail()) extends InfoMailService {
            public array $calls = [];

            public function __construct(private object $fixture)
            {
            }

            public function inbox(array $filters = [], int $page = 1): array
            {
                $this->calls[] = ['inbox', $filters, $page];

                return [[$this->fixture], 1];
            }

            public function unreadCount(): int
            {
                return 3;
            }

            public function find(int $id): ?object
            {
                return $id === 7 ? $this->fixture : null;
            }

            public function thread(object $mail): array
            {
                return [
                    'mails' => [$mail],
                    'replies' => [(object) [
                        'id' => 1,
                        'from_email' => 'info@worldchoiceperfume.com',
                        'to_email' => 'amina@example.co.tz',
                        'subject' => 'Re: Reef 33 price',
                        'body' => 'Jambo Amina, yes it is in stock.',
                        'status' => 'sent',
                        'sent_by_name' => 'Gideon Msuya',
                        'sent_at' => '2026-09-28T10:00:00+03:00',
                        'error' => null,
                    ]],
                ];
            }

            public function markRead(int $id, bool $isRead = true): void
            {
                $this->calls[] = ['markRead', $id, $isRead];
            }

            public function attachmentsFor(int $emailId): array
            {
                return [
                    (object) [
                        'id' => 3,
                        'info_email_id' => 7,
                        'file_name' => 'order.pdf',
                        'mime_type' => 'application/pdf',
                        'size_bytes' => 204800,
                        'storage_path' => 'mail-7/0-order.pdf',
                        'created_at' => '2026-09-28T09:14:22+03:00',
                    ],
                    (object) [
                        'id' => 4,
                        'info_email_id' => 7,
                        'file_name' => 'photo.png',
                        'mime_type' => 'image/png',
                        'size_bytes' => 51200,
                        'storage_path' => 'mail-7/1-photo.png',
                        'created_at' => '2026-09-28T09:14:22+03:00',
                    ],
                ];
            }

            public function attachment(int $emailId, int $attachmentId): ?array
            {
                $all = $this->attachmentsFor(7);
                foreach ($all as $file) {
                    if ($file->id === $attachmentId) {
                        return ['row' => (array) $file, 'contents' => 'bytes'];
                    }
                }

                return null;
            }
        };

        $this->app->instance(InfoMailService::class, $service);
    }

    private function loginAsHeadQuartersCustomerCare(): void
    {
        $user = new \App\Models\User();
        $user->forceFill([
            'id' => 14,
            'supabase_id' => 31,
            'name' => 'Gideon Msuya',
            'email' => 'gideonmsuya140@gmail.com',
            'role' => 'customer_care',
            'branch_id' => 10,
            'status' => 'active',
        ]);

        $this->be($user);
    }

    public function test_the_inbox_page_renders_the_mailbox(): void
    {
        $this->bindService();
        $this->loginAsHeadQuartersCustomerCare();

        $response = $this->get('/customer-care/mails');

        $response->assertOk();
        $response->assertSee('Reef 33 price');
        $response->assertSee('Amina Yusuf');
        $response->assertSee('info@worldchoiceperfume.com');
    }

    public function test_the_inbox_page_filters_are_whitelisted(): void
    {
        $this->bindService();
        $this->loginAsHeadQuartersCustomerCare();

        $this->get('/customer-care/mails?box=not-a-box')->assertOk();
    }

    public function test_a_mail_page_renders_the_body_thread_and_reply_box(): void
    {
        $this->bindService();
        $this->loginAsHeadQuartersCustomerCare();

        $response = $this->get('/customer-care/mails/7');

        $response->assertOk();
        $response->assertSee('Reef 33 price');
        $response->assertSee('amina@example.co.tz');
        // The reply box, the sent answer and the info@ badge.
        $response->assertSee(route('customer-care.mails.reply', 7), false);
        $response->assertSee('Re: Reef 33 price');
        $response->assertSee('info@worldchoiceperfume.com');
        // Auth verdicts from Cloudflare.
        $response->assertSee('SPF');
        $response->assertSee('DKIM');
        $response->assertSee('pass');
    }

    public function test_an_unknown_mail_is_a_404(): void
    {
        $this->bindService();
        $this->loginAsHeadQuartersCustomerCare();

        $this->get('/customer-care/mails/9999')->assertNotFound();
    }

    public function test_the_mail_page_offers_each_attachment_to_open_and_download(): void
    {
        $this->bindService();
        $this->loginAsHeadQuartersCustomerCare();

        $response = $this->get('/customer-care/mails/7');

        $response->assertOk();
        $response->assertSee('order.pdf');
        $response->assertSee('photo.png');
        $response->assertSee(route('customer-care.mails.attachment', [7, 3]), false);
        $response->assertSee('View');
        $response->assertSee('Download');
        // A picture is previewed in place.
        $response->assertSee('<img', false);
    }

    public function test_an_attachment_is_served_inline_for_viewable_types(): void
    {
        $this->bindService();
        $this->loginAsHeadQuartersCustomerCare();

        $response = $this->get('/customer-care/mails/7/attachments/3');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename="order.pdf"');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_an_attachment_is_forced_to_download_when_asked(): void
    {
        $this->bindService();
        $this->loginAsHeadQuartersCustomerCare();

        $response = $this->get('/customer-care/mails/7/attachments/3?download=1');

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename="order.pdf"');
    }

    public function test_an_unknown_attachment_is_a_404(): void
    {
        $this->bindService();
        $this->loginAsHeadQuartersCustomerCare();

        $this->get('/customer-care/mails/7/attachments/999')->assertNotFound();
    }

    public function test_a_guest_cannot_reach_a_mail_or_its_attachments(): void
    {
        $this->bindService();

        $this->get('/customer-care/mails/7')->assertRedirect();
        $this->get('/customer-care/mails/7/attachments/3')->assertRedirect();
    }
}
