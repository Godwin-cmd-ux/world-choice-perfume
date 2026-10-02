<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\InfoMailService;
use Tests\TestCase;

/**
 * The Super Admin mailbox.
 *
 * Super Admin reads the same info@worldchoiceperfume.com mailbox as Customer
 * Care, through the same service and the same templates. These tests pin that
 * down from the Super Admin side: the pages render, the forms post back to the
 * super-admin routes (not the customer-care ones), a reply goes to the original
 * sender, and the section is closed to anyone without the role.
 */
class SuperAdminMailTest extends TestCase
{
    private function fakeMail(): object
    {
        return (object) [
            'id' => 7,
            'message_id' => '<orig-1@example.co.tz>',
            'from_email' => 'amina@example.co.tz',
            'from_name' => 'Amina Yusuf',
            'to_email' => 'info@worldchoiceperfume.com',
            'cc' => null,
            'subject' => 'Reef 33 price',
            'body_text' => "Jambo,\n\nHow much is Reef 33 50ml?\n\nAsante, Amina",
            'attachment_names' => null,
            'has_attachments' => false,
            'spf_result' => 'pass',
            'dkim_result' => 'pass',
            'is_read' => false,
            'is_starred' => false,
            'status' => 'new',
            'thread_key' => '<orig-1@example.co.tz>',
            'received_at' => '2026-09-28T09:14:22+03:00',
            'created_at' => '2026-09-28T09:14:22+03:00',
        ];
    }

    private function bindService(): InfoMailService
    {
        $service = new class($this->fakeMail()) extends InfoMailService
        {
            public array $repliesSent = [];

            public function __construct(private object $fixture)
            {
            }

            public function inbox(array $filters = [], int $page = 1): array
            {
                return [[$this->fixture], 1];
            }

            public function unreadCount(): int
            {
                return 1;
            }

            public function find(int $id): ?object
            {
                return $id === 7 ? $this->fixture : null;
            }

            public function thread(object $mail): array
            {
                return ['mails' => [$mail], 'replies' => []];
            }

            public function markRead(int $id, bool $isRead = true): void
            {
            }

            public function attachmentsFor(int $emailId): array
            {
                return [];
            }

            public function reply(object $mail, string $body, ?object $staff = null, string $staffName = 'Customer Care', array $files = []): array
            {
                $this->repliesSent[] = [
                    'to' => $mail->from_email,
                    'body' => $body,
                    'staff' => $staffName,
                ];

                return ['ok' => true, 'message' => 'Reply sent to '.$mail->from_email.'.', 'reply' => null];
            }
        };

        $this->app->instance(InfoMailService::class, $service);

        return $service;
    }

    private function superAdmin(): User
    {
        $user = new User;
        $user->forceFill([
            'id' => 1,
            'supabase_id' => 1,
            'name' => 'Kessy Mohamed',
            'email' => 'kessy@worldchoiceperfume.com',
            'role' => 'super_admin',
            'branch_id' => 10,
            'status' => 'active',
        ]);

        return $user;
    }

    public function test_a_super_admin_can_open_the_mailbox(): void
    {
        $this->bindService();
        $this->be($this->superAdmin());

        $response = $this->get('/super-admin/emails');

        $response->assertOk();
        $response->assertSee('Reef 33 price');
        $response->assertSee('Amina Yusuf');
    }

    public function test_a_super_admin_can_read_a_mail_without_technical_metadata(): void
    {
        $this->bindService();
        $this->be($this->superAdmin());

        $response = $this->get('/super-admin/emails/7');

        $response->assertOk();
        $response->assertSee('How much is Reef 33 50ml?');
        // Same clean view as Customer Care — no SPF/DKIM verdicts or raw source.
        $response->assertDontSee('SPF');
        $response->assertDontSee('DKIM');
        $response->assertDontSee('Message-ID');
    }

    public function test_the_mail_actions_post_to_the_super_admin_routes(): void
    {
        $this->bindService();
        $this->be($this->superAdmin());

        $response = $this->get('/super-admin/emails/7');

        $response->assertSee(route('super-admin.emails.reply', 7), false);
        $response->assertSee(route('super-admin.emails.read', 7), false);
        $response->assertSee(route('super-admin.emails.star', 7), false);
        $response->assertSee(route('super-admin.emails.status', 7), false);
        $response->assertSee(route('super-admin.emails.destroy', 7), false);
        // The shared template must not send the browser to the Customer Care routes.
        $response->assertDontSee(route('customer-care.mails.reply', 7), false);
    }

    public function test_a_super_admin_reply_goes_to_the_original_sender(): void
    {
        $service = $this->bindService();
        $this->be($this->superAdmin());

        $response = $this->post('/super-admin/emails/7/reply', [
            'body' => 'Jambo Amina, yes it is in stock.',
        ]);

        $response->assertRedirect(route('super-admin.emails.show', 7));
        $response->assertSessionHas('success');

        $this->assertCount(1, $service->repliesSent);
        $this->assertSame('amina@example.co.tz', $service->repliesSent[0]['to']);
        $this->assertSame('Kessy Mohamed', $service->repliesSent[0]['staff']);
    }

    public function test_a_guest_cannot_reach_the_super_admin_mailbox(): void
    {
        $this->bindService();

        $this->get('/super-admin/emails')->assertRedirect(route('login'));
        $this->get('/super-admin/emails/7')->assertRedirect(route('login'));
    }

    public function test_customer_care_cannot_reach_the_super_admin_mailbox(): void
    {
        $this->bindService();

        $user = new User;
        $user->forceFill([
            'id' => 14,
            'name' => 'Gideon Msuya',
            'email' => 'gideonmsuya140@gmail.com',
            'role' => 'customer_care',
            'branch_id' => 10,
            'status' => 'active',
        ]);
        $this->be($user);

        $this->get('/super-admin/emails')->assertForbidden();
        $this->post('/super-admin/emails/7/reply', ['body' => 'nope'])->assertForbidden();
    }
}
