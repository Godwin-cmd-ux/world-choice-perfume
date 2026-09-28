<?php

namespace Tests\Feature;

use App\Services\InfoMailService;
use App\Services\RawEmailParser;
use App\Services\SupabaseService;
use Tests\TestCase;

/**
 * The mail page used to return a 500 because thread() read ->id off a row that
 * Supabase had handed back as an array. The page tests stub the whole service
 * out, so nothing caught it; these tests drive the real service against a
 * double that returns rows in the same array shape the real client does.
 */
class InfoMailServiceTest extends TestCase
{
    /** A stand-in for the real Supabase client: arrays in, arrays out. */
    private function supabaseWith(array $rows): SupabaseService
    {
        return new class($rows) extends SupabaseService {
            public array $storage = [];

            public array $inserted = [];

            private int $nextId = 1;

            public function __construct(private array $rows)
            {
            }

            public function query(string $table, array $params = []): array
            {
                $filter = $params['thread_key'] ?? $params['info_email_id'] ?? null;
                if ($filter !== null) {
                    $key = str_starts_with((string) $filter, 'eq.') ? substr((string) $filter, 3) : (string) $filter;

                    return array_values(array_filter(
                        $this->rows,
                        fn ($r) => ($r['thread_key'] ?? null) === $key
                            || (string) ($r['info_email_id'] ?? '') === $key
                    ));
                }

                return array_values($this->rows);
            }

            public function find(string $table, int|string $id, string $select = '*'): ?array
            {
                foreach ($this->rows as $row) {
                    if ((int) ($row['id'] ?? 0) === (int) $id) {
                        return $row;
                    }
                }

                return null;
            }

            public function insertOrFail(string $table, array|object $data): array
            {
                $row = (array) $data;
                $row['id'] = 100 + $this->nextId++;
                $this->rows[] = $row;
                $this->inserted[] = $row;

                return $row;
            }

            public function insertMany(string $table, array $records): ?array
            {
                $out = [];
                foreach ($records as $record) {
                    $row = (array) $record;
                    $row['id'] = $this->nextId++;
                    $this->rows[] = $row;
                    $out[] = $row;
                }

                return $out;
            }

            public function ensureStorageBucket(string $bucket): bool
            {
                return true;
            }

            public function storagePut(string $bucket, string $path, string $contents, string $contentType): bool
            {
                $this->storage[$path] = $contents;

                return true;
            }

            public function storageDownloadTo(string $bucket, string $path, string $destination): bool
            {
                if (! isset($this->storage[$path])) {
                    return false;
                }

                file_put_contents($destination, $this->storage[$path]);

                return true;
            }

            public function storageGet(string $bucket, string $path): ?string
            {
                return $this->storage[$path] ?? null;
            }

            public function storageDelete(string $bucket, string $path): bool
            {
                unset($this->storage[$path]);

                return true;
            }

            public function update(string $table, array|object $data, array $conditions): array
            {
                foreach ($this->rows as $i => $row) {
                    if ((int) ($row['id'] ?? 0) === (int) ($conditions['id'] ?? 0)) {
                        $this->rows[$i] = array_merge($row, (array) $data);
                    }
                }

                return $this->rows;
            }

            public function delete(string $table, array $conditions): bool
            {
                $id = (int) str_replace('eq.', '', (string) ($conditions['id'] ?? $conditions['info_email_id'] ?? 0));
                $this->rows = array_values(array_filter(
                    $this->rows,
                    fn ($r) => (int) ($r['id'] ?? 0) !== $id && (int) ($r['info_email_id'] ?? 0) !== $id
                ));

                return true;
            }
        };
    }

    private function threadRow(int $id, string $messageId, string $threadKey): array
    {
        return [
            'id' => $id,
            'message_id' => $messageId,
            'in_reply_to' => null,
            'from_email' => 'amina@example.co.tz',
            'from_name' => 'Amina Yusuf',
            'subject' => 'Reef 33 price',
            'body_text' => 'How much?',
            'received_at' => '2026-09-28T09:14:22+03:00',
            'created_at' => '2026-09-28T09:14:22+03:00',
            'is_read' => true,
            'status' => 'new',
            'has_attachments' => false,
            'attachment_names' => null,
            'thread_key' => $threadKey,
            'parent_id' => null,
        ];
    }

    public function test_thread_returns_objects_not_arrays(): void
    {
        $supabase = $this->supabaseWith([
            $this->threadRow(1, '<a@example>', '<a@example>'),
            $this->threadRow(2, '<b@example>', '<a@example>'),
        ]);

        $service = new InfoMailService($supabase, new RawEmailParser);
        $mail = $service->find(2);

        $thread = $service->thread($mail);

        $this->assertCount(2, $thread['mails'], 'Both messages belong to one thread.');
        $this->assertIsObject($thread['mails'][0], 'Rows must be objects so the view can read ->id.');
        $this->assertSame(
            [1, 2],
            $thread['mails']->map(fn ($m) => $m->id)->all(),
            'Every row in the thread has to be usable through ->id.'
        );
    }

    public function test_thread_falls_back_to_the_mail_itself_when_the_key_matches_nothing(): void
    {
        $supabase = $this->supabaseWith([]);
        $service = new InfoMailService($supabase, new RawEmailParser);

        $mail = (object) $this->threadRow(5, '<solo@example>', '<solo@example>');
        $thread = $service->thread($mail);

        $this->assertCount(1, $thread['mails']);
        $this->assertSame(5, $thread['mails'][0]->id);
        $this->assertIsObject($thread['mails'][0]);
    }

    public function test_replies_for_returns_objects(): void
    {
        $supabase = $this->supabaseWith([]);
        $service = new InfoMailService($supabase, new RawEmailParser);

        $this->assertSame([], $service->repliesFor(7));
    }

    public function test_attachments_are_stored_and_can_be_read_back(): void
    {
        $raw = "From: Amina <amina@example.co.tz>\r\n"
            . "To: info@worldchoiceperfume.com\r\n"
            . "Subject: Invoice please\r\n"
            . "Message-ID: <inv-1@example>\r\n"
            . "Content-Type: multipart/mixed; boundary=\"MIX\"\r\n"
            . "\r\n"
            . "--MIX\r\n"
            . "Content-Type: text/plain; charset=utf-8\r\n"
            . "\r\n"
            . "See the invoice attached.\r\n"
            . "--MIX\r\n"
            . "Content-Type: application/pdf; name=\"invoice.pdf\"\r\n"
            . "Content-Disposition: attachment; filename=\"invoice.pdf\"\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "\r\n"
            . base64_encode('%PDF-1.4 pretend this is an invoice') . "\r\n"
            . "--MIX--\r\n";

        $supabase = $this->supabaseWith([]);
        $service = new InfoMailService($supabase, new RawEmailParser);

        $stored = $service->storeRawEmail($raw);
        $this->assertNotNull($stored, 'The mail itself must be stored.');
        $this->assertSame('invoice.pdf', $stored['attachment_names']);
        $this->assertTrue($stored['has_attachments']);

        $files = $service->attachmentsFor((int) $stored['id']);
        $this->assertCount(1, $files);
        $this->assertSame('invoice.pdf', $files[0]->file_name);
        $this->assertSame('application/pdf', $files[0]->mime_type);

        $found = $service->attachment((int) $stored['id'], 1);
        $this->assertNotNull($found, 'The bytes must be readable again.');
        $this->assertSame(
            '%PDF-1.4 pretend this is an invoice',
            file_get_contents($found['file']),
            'The file that comes back has to be the file that went in.'
        );
        $this->assertSame(35, $found['size']);
        unlink($found['file']);
    }

    public function test_an_attachment_from_another_mail_cannot_be_read(): void
    {
        $raw = "From: Amina <amina@example.co.tz>\r\n"
            . "To: info@worldchoiceperfume.com\r\n"
            . "Subject: Invoice please\r\n"
            . "Message-ID: <inv-2@example>\r\n"
            . "Content-Type: multipart/mixed; boundary=\"MIX\"\r\n"
            . "\r\n"
            . "--MIX\r\n"
            . "Content-Type: text/plain\r\n"
            . "\r\n"
            . "See attached.\r\n"
            . "--MIX\r\n"
            . "Content-Type: application/pdf; name=\"invoice.pdf\"\r\n"
            . "Content-Disposition: attachment; filename=\"invoice.pdf\"\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "\r\n"
            . base64_encode('%PDF-1.4 secret') . "\r\n"
            . "--MIX--\r\n";

        $supabase = $this->supabaseWith([]);
        $service = new InfoMailService($supabase, new RawEmailParser);
        $stored = $service->storeRawEmail($raw);

        // Attachment id 1 does exist, but it belongs to a different mail.
        $this->assertNull(
            $service->attachment(999999, 1),
            'A file must not be reachable through a mail id that does not own it.'
        );
        $this->assertNotNull($service->attachment((int) $stored['id'], 1));
    }

    public function test_a_hash_in_the_filename_does_not_corrupt_the_storage_key(): void
    {
        // Supabase Storage rejects '#' in a key and silently truncates the
        // rest, so the file would end up stored under a different name than
        // the record says.
        $raw = "From: Amina <amina@example.co.tz>\r\n"
            . "To: info@worldchoiceperfume.com\r\n"
            . "Subject: Quote\r\n"
            . "Message-ID: <inv-4@example>\r\n"
            . "Content-Type: multipart/mixed; boundary=\"MIX\"\r\n"
            . "\r\n"
            . "--MIX\r\n"
            . "Content-Type: text/plain\r\n"
            . "\r\n"
            . "See attached.\r\n"
            . "--MIX\r\n"
            . "Content-Type: application/pdf; name=\"final #2 revised.pdf\"\r\n"
            . "Content-Disposition: attachment; filename=\"final #2 revised.pdf\"\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "\r\n"
            . base64_encode('%PDF-1.4 quote') . "\r\n"
            . "--MIX--\r\n";

        $supabase = $this->supabaseWith([]);
        $service = new InfoMailService($supabase, new RawEmailParser);
        $stored = $service->storeRawEmail($raw);

        $file = $service->attachmentsFor((int) $stored['id'])[0];

        $this->assertStringNotContainsString('#', $file->storage_path, 'A # would be rejected or truncate the key.');
        $this->assertStringNotContainsString(' ', $file->storage_path);
        $this->assertSame('final #2 revised.pdf', $file->file_name, 'The name shown to staff keeps the sender wording.');
        $this->assertStringEndsWith('.pdf', $file->storage_path, 'The extension is kept.');
        $this->assertNotNull(
            $service->attachment((int) $stored['id'], 1),
            'The file must still be readable at the key that was recorded.'
        );
    }

    public function test_a_traversal_filename_is_flattened_before_it_is_used_as_a_path(): void    {
        $raw = "From: Amina <amina@example.co.tz>\r\n"
            . "To: info@worldchoiceperfume.com\r\n"
            . "Subject: Tricky name\r\n"
            . "Message-ID: <inv-3@example>\r\n"
            . "Content-Type: multipart/mixed; boundary=\"MIX\"\r\n"
            . "\r\n"
            . "--MIX\r\n"
            . "Content-Type: text/plain\r\n"
            . "\r\n"
            . "See attached.\r\n"
            . "--MIX\r\n"
            . "Content-Type: text/plain; name=\"../../etc/passwd\"\r\n"
            . "Content-Disposition: attachment; filename=\"../../etc/passwd\"\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "\r\n"
            . base64_encode('nope') . "\r\n"
            . "--MIX--\r\n";

        $supabase = $this->supabaseWith([]);
        $service = new InfoMailService($supabase, new RawEmailParser);
        $stored = $service->storeRawEmail($raw);

        $files = $service->attachmentsFor((int) $stored['id']);
        $name = $files[0]->file_name;

        $this->assertStringNotContainsString('/', $name, 'A sender cannot steer the path with ..');
        $this->assertStringNotContainsString('..', $name);
        $this->assertStringStartsWith('mail-', $files[0]->storage_path);
    }
}
