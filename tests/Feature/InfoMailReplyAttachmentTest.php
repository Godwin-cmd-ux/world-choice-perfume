<?php

namespace Tests\Feature;

use App\Mail\InfoMailReply;
use App\Models\User;
use App\Services\InfoMailService;
use App\Services\RawEmailParser;
use App\Services\SupabaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use ReflectionProperty;
use RuntimeException;
use Tests\TestCase;

/**
 * Files a member attaches to an answer.
 *
 * Two things are worth pinning down. The controller has to refuse what must
 * never leave the building, judged on what a file really is rather than on the
 * name it was uploaded under. And the name has to be kept on the record,
 * because the upload is gone once the answer is sent.
 */
class InfoMailReplyAttachmentTest extends TestCase
{
    /** Rows the stand-in Supabase client recorded. */
    private ?object $supabase = null;

    public function test_a_member_can_send_a_file_with_the_answer(): void
    {
        Mail::fake();

        $result = $this->service()->reply(
            $this->mail(),
            'Here is the receipt you asked for.',
            $this->staff(),
            'Gideon Msuya',
            [UploadedFile::fake()->create('receipt.pdf', 40, 'application/pdf')],
        );

        $this->assertTrue($result['ok']);

        Mail::assertSent(InfoMailReply::class, function (InfoMailReply $mail) {
            $names = $this->attachedNames($mail);

            $this->assertCount(1, $names);
            $this->assertSame('receipt.pdf', $names[0]);

            return true;
        });

        $this->assertSame('receipt.pdf', $this->recordedReply()['attachment_names']);
    }

    public function test_several_files_go_out_under_their_own_names(): void
    {
        Mail::fake();

        $this->service()->reply(
            $this->mail(),
            'Receipt and photos attached.',
            $this->staff(),
            'Gideon Msuya',
            [
                UploadedFile::fake()->create('receipt.pdf', 40, 'application/pdf'),
                UploadedFile::fake()->create('parcel.png', 120, 'image/png'),
            ],
        );

        Mail::assertSent(InfoMailReply::class, function (InfoMailReply $mail) {
            $this->assertSame(['receipt.pdf', 'parcel.png'], $this->attachedNames($mail));

            return true;
        });

        $this->assertSame('receipt.pdf, parcel.png', $this->recordedReply()['attachment_names']);
    }

    public function test_the_answer_names_the_files_so_the_customer_knows_to_look(): void
    {
        $mailable = new InfoMailReply(
            replyToName: 'Amina Yusuf',
            mailSubject: 'Re: Reef 33 price',
            bodyHtml: 'Jambo Amina, receipts attached.',
            originalText: 'Where is the receipt?',
            attachmentNames: ['receipt.pdf', 'parcel.png'],
        );

        $html = $mailable->render();

        $this->assertStringContainsString('Attached (2)', $html);
        $this->assertStringContainsString('receipt.pdf', $html);
        $this->assertStringContainsString('parcel.png', $html);

        $text = (string) view(
            $mailable->content()->text,
            $mailable->content()->with
        )->render();

        $this->assertStringContainsString('Attached:', $text);
        $this->assertStringContainsString('- receipt.pdf', $text);
    }

    public function test_an_answer_with_no_files_shows_no_attachment_list(): void
    {
        $mailable = new InfoMailReply(
            replyToName: 'Amina Yusuf',
            mailSubject: 'Re: Reef 33 price',
            bodyHtml: 'Jambo Amina, it is in stock.',
            originalText: 'Is it in stock?',
        );

        $this->assertStringNotContainsString('Attached', $mailable->render());
        $this->assertStringNotContainsString('Attached:', (string) view(
            $mailable->content()->text,
            $mailable->content()->with
        )->render());
    }

    public function test_a_plain_answer_records_no_file_names(): void
    {
        Mail::fake();

        $this->service()->reply($this->mail(), 'Jambo Amina, it is in stock.', $this->staff());

        Mail::assertSent(InfoMailReply::class);
        $this->assertNull($this->recordedReply()['attachment_names']);
    }

    /**
     * What the member reads back after answering: who it went to, and nothing
     * about how it was sent.
     */
    public function test_a_successful_reply_reports_only_where_it_went(): void
    {
        Mail::fake();

        $result = $this->service()->reply($this->mail(), 'Jambo Amina, it is in stock.', $this->staff());

        $this->assertTrue($result['ok']);
        $this->assertSame('Reply sent to amina@example.co.tz.', $result['message']);
    }

    public function test_a_failed_send_still_records_what_was_attached(): void
    {
        // The record has to say the file went with the answer, otherwise a
        // re-send silently drops the receipt the customer needs.
        $this->refuseEverySend('provider refused');

        $result = $this->service()->reply(
            $this->mail(),
            'Receipt attached.',
            $this->staff(),
            'Gideon Msuya',
            [UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf')],
        );

        $this->assertFalse($result['ok']);

        $recorded = $this->recordedReply();
        $this->assertSame('failed', $recorded['status']);
        $this->assertSame('receipt.pdf', $recorded['attachment_names']);
    }

    /**
     * A browser can be made to send any name at all. A path, or a name holding
     * a second extension, must not reach the customer or a mail header.
     */
    public function test_a_crafted_file_name_is_flattened_before_it_is_sent(): void
    {
        Mail::fake();

        $this->service()->reply(
            $this->mail(),
            'Attached.',
            $this->staff(),
            'Gideon Msuya',
            [
                UploadedFile::fake()->create('../../etc/receipt.pdf', 10, 'application/pdf'),
            ],
        );

        Mail::assertSent(InfoMailReply::class, function (InfoMailReply $mail) {
            $names = $this->attachedNames($mail);

            $this->assertSame('receipt.pdf', $names[0]);
            $this->assertStringNotContainsString('/', $names[0]);
            $this->assertStringNotContainsString('..', $names[0]);

            return true;
        });
    }

    public function test_a_name_that_is_only_an_extension_is_not_sent(): void
    {
        Mail::fake();

        $this->service()->reply(
            $this->mail(),
            'Attached.',
            $this->staff(),
            'Gideon Msuya',
            [UploadedFile::fake()->create('...pdf', 10, 'application/pdf')],
        );

        Mail::assertSent(InfoMailReply::class, function (InfoMailReply $mail) {
            $this->assertSame([], $this->attachedNames($mail));

            return true;
        });
    }

    public function test_the_controller_refuses_a_file_that_is_not_allowed(): void
    {
        $this->bindService();
        $this->loginAsHeadQuartersCustomerCare();

        // A PHP script wearing a document's name. The rule looks at what the
        // file is, so the name does not get it through.
        $response = $this->post('/customer-care/mails/7/reply', [
            'body' => 'Here you go.',
            'attachments' => [
                UploadedFile::fake()->create('invoice.pdf', 10, 'application/x-php'),
            ],
        ]);

        $response->assertSessionHasErrors('attachments.0');
    }

    public function test_the_controller_refuses_a_file_that_can_run(): void
    {
        $this->bindService();
        $this->loginAsHeadQuartersCustomerCare();

        $response = $this->post('/customer-care/mails/7/reply', [
            'body' => 'Here you go.',
            'attachments' => [
                UploadedFile::fake()->create('setup.exe', 10, 'application/x-msdownload'),
            ],
        ]);

        $response->assertSessionHasErrors('attachments.0');
    }

    public function test_the_controller_refuses_more_files_than_the_limit(): void
    {
        $this->bindService();
        $this->loginAsHeadQuartersCustomerCare();

        $response = $this->post('/customer-care/mails/7/reply', [
            'body' => 'Here you go.',
            'attachments' => array_map(
                fn (int $i) => UploadedFile::fake()->create("file{$i}.pdf", 5, 'application/pdf'),
                range(1, 6)
            ),
        ]);

        $response->assertSessionHasErrors('attachments');
    }

    public function test_the_controller_refuses_a_file_over_the_size_limit(): void
    {
        $this->bindService();
        $this->loginAsHeadQuartersCustomerCare();

        $response = $this->post('/customer-care/mails/7/reply', [
            'body' => 'Here you go.',
            'attachments' => [
                // One kilobyte over the 20 MB per file cap.
                UploadedFile::fake()->create('huge.pdf', 20 * 1024 + 2, 'application/pdf'),
            ],
        ]);

        $response->assertSessionHasErrors('attachments.0');
    }

    /**
     * Over the 40M post_max_size in the Dockerfile, PHP throws the whole POST
     * away including the CSRF token, so the page comes back as a bare 419 with
     * nothing to explain it. The total is checked here to turn that into a
     * message a member can act on.
     */
    public function test_the_controller_refuses_files_that_add_up_to_more_than_the_total(): void
    {
        $this->bindService();
        $this->loginAsHeadQuartersCustomerCare();

        $response = $this->post('/customer-care/mails/7/reply', [
            'body' => 'Here you go.',
            'attachments' => array_map(
                fn (int $i) => UploadedFile::fake()->create("part{$i}.pdf", 15 * 1024, 'application/pdf'),
                range(1, 3)
            ),
        ]);

        $response->assertRedirect('/customer-care/mails/7');
        $response->assertSessionHas('error');
    }

    public function test_the_files_reach_the_service_with_their_own_names(): void
    {
        $this->bindService();
        $this->loginAsHeadQuartersCustomerCare();

        $this->post('/customer-care/mails/7/reply', [
            'body' => 'Here you go.',
            'attachments' => [
                UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'),
            ],
        ])->assertRedirect('/customer-care/mails/7');

        $service = $this->app->make(InfoMailService::class);

        $this->assertSame(['receipt.pdf'], $service->calls[0]['files']);
    }

    public function test_the_reply_form_offers_a_file_picker_and_says_the_limits(): void
    {
        $this->bindService();
        $this->loginAsHeadQuartersCustomerCare();

        $response = $this->get('/customer-care/mails/7');

        $response->assertOk();
        $response->assertSee('enctype="multipart/form-data"', false);
        $response->assertSee('name="attachments[]"', false);
        $response->assertSee('20 MB each');
    }

    public function test_a_guest_cannot_post_a_file_to_a_mail(): void
    {
        $response = $this->post('/customer-care/mails/7/reply', [
            'body' => 'Here you go.',
            'attachments' => [UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf')],
        ]);

        $response->assertRedirect('/login');
    }

    /**
     * The names the customer will see on the attachments.
     *
     * Mailable keeps its attachments in a protected property with no accessor,
     * so it is read directly: the point of the test is that the bytes and the
     * name really are on the mailable, not merely listed in the body.
     *
     * @return string[]
     */
    private function attachedNames(InfoMailReply $mail): array
    {
        $property = new ReflectionProperty(Mailable::class, 'attachments');
        $property->setAccessible(true);

        $attachments = $property->getValue($mail) ?: [];

        return array_map(function ($attachment) {
            // A plain path is kept as ['file' => ..., 'options' => [...]];
            // an Attachment object carries the name on itself.
            if (is_array($attachment)) {
                return (string) ($attachment['options']['as'] ?? '');
            }

            return (string) $attachment->as;
        }, $attachments);
    }

    /** A mail that can be answered. */
    private function mail(): object
    {
        return (object) [
            'id' => 7,
            'from_email' => 'amina@example.co.tz',
            'from_name' => 'Amina Yusuf',
            'reply_to' => null,
            'subject' => 'Reef 33 price',
            'body_text' => 'How much is Reef 33?',
            'message_id' => '<abc@mail.example>',
            'reference_ids' => '<abc@mail.example>',
        ];
    }

    private function staff(): object
    {
        return (object) ['id' => 14, 'supabase_id' => 31, 'name' => 'Gideon Msuya'];
    }

    /**
     * A mailer that refuses every send, the way a dead SMTP host does.
     */
    private function refuseEverySend(string $message): void
    {
        Mail::extend('refusing', function () {
            throw new RuntimeException('provider refused');
        });

        config(['mail.default' => 'refusing']);
    }

    /**
     * The real service over a stand-in Supabase client, so what actually gets
     * recorded is checked rather than mocked away.
     */
    private function service(): InfoMailService
    {
        $this->supabase = new class extends SupabaseService
        {
            public array $inserted = [];

            public function __construct() {}

            public function insert(string $table, array|object $data): ?array
            {
                $row = (array) $data;
                $row['id'] = count($this->inserted) + 1;
                $this->inserted[] = $row;

                return $row;
            }

            public function update(string $table, array|object $data, array $conditions): array
            {
                return [];
            }
        };

        return new InfoMailService($this->supabase, new RawEmailParser);
    }

    private function recordedReply(): array
    {
        $this->assertNotNull($this->supabase, 'no reply was recorded');

        return $this->supabase->inserted[0] ?? [];
    }

    /**
     * A service that records what it was handed, so the controller can be
     * checked without a mail provider in the way.
     */
    private function bindService(): void
    {
        $service = new class($this->fakeMail()) extends InfoMailService
        {
            public array $calls = [];

            public function __construct(private object $fixture) {}

            public function inbox(array $filters = [], int $page = 1): array
            {
                return [[$this->fixture], 1];
            }

            public function unreadCount(): int
            {
                return 0;
            }

            public function find(int $id): ?object
            {
                return $id === 7 ? $this->fixture : null;
            }

            public function thread(object $mail): array
            {
                return ['mails' => [$mail], 'replies' => []];
            }

            public function attachmentsFor(int $emailId): array
            {
                return [];
            }

            public function markRead(int $id, bool $isRead = true): void {}

            public function reply(object $mail, string $body, ?object $staff = null, string $staffName = 'Customer Care', array $files = []): array
            {
                $this->calls[] = [
                    'body' => $body,
                    'files' => array_map(fn ($f) => $f->getClientOriginalName(), $files),
                ];

                return ['ok' => true, 'message' => 'Reply sent.', 'reply' => (object) ['id' => 1]];
            }
        };

        $this->app->instance(InfoMailService::class, $service);
    }

    private function fakeMail(): object
    {
        return (object) [
            'id' => 7,
            'from_email' => 'amina@example.co.tz',
            'from_name' => 'Amina Yusuf',
            'reply_to' => null,
            'subject' => 'Reef 33 price',
            'body_text' => 'How much is Reef 33?',
            'body_html' => '<p>How much is Reef 33?</p>',
            'to_email' => 'info@worldchoiceperfume.com',
            'is_read' => true,
            'is_starred' => false,
            'status' => 'new',
            'attachment_names' => null,
            'has_attachments' => false,
            'message_id' => '<abc@mail.example>',
            'reference_ids' => '<abc@mail.example>',
            'received_at' => '2026-09-28T09:14:22+03:00',
            'created_at' => '2026-09-28T09:14:22+03:00',
        ];
    }

    private function loginAsHeadQuartersCustomerCare(): void
    {
        $user = new User;
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
}
