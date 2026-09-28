<?php

namespace App\Services;

use App\Mail\InfoMailReply;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The info@worldchoiceperfume.com mailbox.
 *
 * Inbound mail is written by the webhook (Api\InfoMailWebhookController) and
 * read/answered from Customer Care → Mails. Replies are recorded with the
 * Message-ID that was sent, which is how a customer's next email is matched
 * back to the same conversation.
 */
class InfoMailService
{
    public const TABLE = 'info_emails';

    public const REPLIES_TABLE = 'info_email_replies';

    public function __construct(
        private SupabaseService $supabase,
        private RawEmailParser $parser,
    ) {}

    /**
     * Store a raw email. Returns the stored row, or null when the mail could
     * not be read or written. A re-delivered Message-ID is ignored.
     */
    public function storeRawEmail(string $raw, array $reported = []): ?array
    {
        $parsed = $this->parser->parse($raw);

        $messageId = $parsed['message_id'];
        if ($messageId !== null && $this->findByMessageId($messageId) !== null) {
            return null; // already in the mailbox
        }

        $fromEmail = $parsed['from_email'] ?? ($reported['from'] ?? null);
        if (! $fromEmail) {
            // Nothing to answer without a sender address.
            return null;
        }

        $receivedAt = $this->receivedAt($parsed['date'] ?? null);
        $thread = $this->resolveThread($messageId, $parsed['in_reply_to']);

        $row = [
            'message_id' => $messageId,
            'in_reply_to' => $parsed['in_reply_to'],
            'reference_ids' => $parsed['references'],
            'from_email' => $fromEmail,
            'from_name' => $parsed['from_name'],
            'to_email' => $parsed['to_email'] ?? ($reported['to'] ?? config('info_mail.address')),
            'cc' => $parsed['cc'] !== [] ? implode(', ', array_column($parsed['cc'], 'email')) : null,
            'bcc' => $parsed['bcc'] !== [] ? implode(', ', array_column($parsed['bcc'], 'email')) : null,
            'reply_to' => $parsed['reply_to'],
            'subject' => $parsed['subject'],
            'body_text' => $parsed['body_text'],
            'body_html' => $parsed['body_html'],
            'raw_email' => $this->cap($raw),
            'headers' => $parsed['headers'] ?: new \stdClass,
            'attachment_names' => $parsed['attachment_names'] !== [] ? implode(', ', $parsed['attachment_names']) : null,
            'has_attachments' => $parsed['attachment_names'] !== [],
            'spf_result' => $parsed['spf_result'],
            'dkim_result' => $parsed['dkim_result'],
            'is_read' => false,
            'is_starred' => false,
            'status' => 'new',
            'thread_key' => $thread['thread_key'],
            'parent_id' => $thread['parent_id'],
            'received_at' => $receivedAt,
            'created_at' => $receivedAt,
            'updated_at' => now()->toIso8601String(),
        ];

        return $this->supabase->insertOrFail(self::TABLE, $row);
    }

    public function findByMessageId(string $messageId): ?array
    {
        $rows = $this->supabase->query(self::TABLE, [
            'select' => 'id,message_id,thread_key,status',
            'message_id' => "eq.{$messageId}",
            'limit' => 1,
        ]);

        return $rows[0] ?? null;
    }

    /**
     * Mailbox listing with the Customer Care filters applied.
     *
     * @return array{0: array<int, object>, 1: int} [mails, count on this page]
     */
    public function inbox(array $filters = [], int $page = 1): array
    {
        $perPage = (int) config('info_mail.per_page', 30);
        $params = [
            'select' => 'id,message_id,from_email,from_name,subject,body_text,received_at,created_at,is_read,is_starred,status,has_attachments,attachment_names,thread_key,replied_at',
            'order' => 'received_at.desc',
            'limit' => $perPage,
            'offset' => max(0, $page - 1) * $perPage,
        ];

        if (($filters['box'] ?? '') === 'unread') {
            $params['is_read'] = 'eq.false';
        } elseif (($filters['box'] ?? '') === 'starred') {
            $params['is_starred'] = 'eq.true';
        } elseif (($filters['box'] ?? '') === 'replied') {
            $params['status'] = 'eq.replied';
        } elseif (($filters['box'] ?? '') === 'closed') {
            $params['status'] = 'eq.closed';
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $params['or'] = '(from_email.ilike.*' . $this->clean($search) . '*,from_name.ilike.*' . $this->clean($search) . '*,subject.ilike.*' . $this->clean($search) . '*,body_text.ilike.*' . $this->clean($search) . '*)';
        }

        $mails = collect($this->supabase->query(self::TABLE, $params))->map(fn ($m) => (object) $m);

        return [$mails->values(), $mails->count()];
    }

    public function find(int $id): ?object
    {
        $row = $this->supabase->find(self::TABLE, $id);

        return $row ? (object) $row : null;
    }

    /**
     * The whole conversation: the mail itself, anything it replied to, and
     * every answer we sent inside the thread.
     *
     * @return array{mails: array<int, object>, replies: array<int, object>}
     */
    public function thread(object $mail): array
    {
        $key = $mail->thread_key ?: $mail->message_id;

        $mails = $key
            ? collect($this->supabase->query(self::TABLE, [
                'select' => 'id,message_id,in_reply_to,from_email,from_name,subject,body_text,received_at,created_at,is_read,status,has_attachments,attachment_names,thread_key,parent_id',
                'thread_key' => "eq.{$key}",
                'order' => 'received_at.asc',
            ]))
            : collect();

        $hasCurrent = $mails->contains(fn ($m) => (int) $m->id === (int) $mail->id);
        if ($mails->isEmpty() || ! $hasCurrent) {
            $mails = collect([(array) $mail]);
        }

        $ids = $mails->pluck('id')->map(fn ($id) => (int) $id)->all();

        $replies = $ids === [] ? [] : collect($this->supabase->query(self::REPLIES_TABLE, [
            'select' => '*',
            'info_email_id' => 'in.(' . implode(',', $ids) . ')',
            'order' => 'created_at.asc',
        ]))->map(fn ($r) => (object) $r);

        return ['mails' => $mails->values(), 'replies' => $replies->values()];
    }

    public function repliesFor(int $emailId): array
    {
        return $this->supabase->query(self::REPLIES_TABLE, [
            'select' => '*',
            'info_email_id' => "eq.{$emailId}",
            'order' => 'created_at.asc',
        ]);
    }

    public function markRead(int $id, bool $isRead = true): void
    {
        $this->supabase->update(self::TABLE, [
            'is_read' => $isRead,
            'read_at' => $isRead ? now()->toIso8601String() : null,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $id]);
    }

    public function setStarred(int $id, bool $starred): void
    {
        $this->supabase->update(self::TABLE, [
            'is_starred' => $starred,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $id]);
    }

    public function setStatus(int $id, string $status): void
    {
        $this->supabase->update(self::TABLE, [
            'status' => $status,
            'replied_at' => $status === 'replied' ? now()->toIso8601String() : null,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->supabase->delete(self::TABLE, ['id' => $id]);
    }

    public function unreadCount(): int
    {
        try {
            return $this->supabase->count(self::TABLE, ['is_read' => 'eq.false']);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Send an answer as info@worldchoiceperfume.com and record it.
     *
     * @return array{ok: bool, message: string, reply: ?object}
     */
    public function reply(object $mail, string $body, ?object $staff = null, string $staffName = 'Customer Care'): array
    {
        // The customer's Reply-To wins over the From address when it is set,
        // which is what every other mail client does when you press reply.
        $to = trim((string) ($mail->reply_to ?? '')) ?: trim((string) ($mail->from_email ?? ''));
        if ($to === '') {
            return ['ok' => false, 'message' => 'This mail has no sender address to reply to.', 'reply' => null];
        }

        $subject = $this->replySubject((string) ($mail->subject ?? ''));
        $fromAddress = (string) config('info_mail.address');
        $fromName = (string) config('info_mail.name');
        $quoted = mb_substr(trim((string) ($mail->body_text ?? '')), 0, 2000);

        try {
            $mailable = (new InfoMailReply(
                replyToName: ($mail->from_name ?? null) ?: $to,
                mailSubject: $subject,
                bodyHtml: $body,
                originalText: $quoted,
            ))
                ->from($fromAddress, $fromName)
                ->to($to, $mail->from_name ?: null)
                // If the customer answers our answer, it must land back in this
                // mailbox, not in the reply-to address of the person who wrote.
                ->replyTo($fromAddress, $fromName)
                ->withSymfonyMessage(function ($message) use ($mail) {
                    $headers = $message->getHeaders();
                    // Threading: the answer points back at the customer's
                    // Message-ID so mail clients keep it in the conversation.
                    if (! empty($mail->message_id)) {
                        $headers->addTextHeader('In-Reply-To', $mail->message_id);
                        // References has to repeat the ids the customer already
                        // quoted, otherwise the thread starts over halfway down.
                        $references = trim((string) ($mail->reference_ids ?? $mail->message_id));
                        $headers->addTextHeader('References', $references !== '' ? $references : (string) $mail->message_id);
                    }
                });

            $sent = Mail::send($mailable);
        } catch (\Throwable $e) {
            Log::error('info@ reply failed: ' . $e->getMessage());

            $this->supabase->insert(self::REPLIES_TABLE, [
                'info_email_id' => (int) $mail->id,
                'from_email' => $fromAddress,
                'to_email' => $to,
                'subject' => $subject,
                'body' => $body,
                'in_reply_to' => $mail->message_id ?? null,
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 1000),
                'sent_by' => $this->staffId($staff),
                'sent_by_name' => $staffName,
                'created_at' => now()->toIso8601String(),
            ]);

            return [
                'ok' => false,
                'message' => 'The mail provider refused the reply: ' . $e->getMessage(),
                'reply' => null,
            ];
        }

        $messageId = $this->sentMessageId($sent);

        $reply = $this->supabase->insert(self::REPLIES_TABLE, [
            'info_email_id' => (int) $mail->id,
            'from_email' => $fromAddress,
            'to_email' => $to,
            'subject' => $subject,
            'body' => $body,
            'message_id' => $messageId,
            'in_reply_to' => $mail->message_id ?? null,
            'status' => 'sent',
            'sent_by' => $this->staffId($staff),
            'sent_by_name' => $staffName,
            'sent_at' => now()->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ]);

        $this->supabase->update(self::TABLE, [
            'status' => 'replied',
            'replied_at' => now()->toIso8601String(),
            'is_read' => true,
            'read_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ], ['id' => (int) $mail->id]);

        return [
            'ok' => true,
            'message' => 'Reply sent to ' . $to . ' as ' . $fromAddress . '.',
            'reply' => $reply ? (object) $reply : null,
        ];
    }

    /**
     * Keep "Re:" out of "Re: Re: Re:" chains.
     */
    private function replySubject(string $subject): string
    {
        $subject = trim($subject);
        $subject = preg_replace('/^(re:\s*)+/i', '', $subject) ?? $subject;

        return 'Re: ' . ($subject !== '' ? $subject : 'Your message');
    }

    /**
     * The Message-ID the provider actually used, so the customer's answer can
     * be matched back to this reply.
     */
    private function sentMessageId($sent): ?string
    {
        try {
            $id = $sent->getMessageId();
        } catch (\Throwable $e) {
            $id = null;
        }

        $id = is_string($id) ? trim($id) : '';
        if ($id === '') {
            return null;
        }

        return str_starts_with($id, '<') ? $id : "<{$id}>";
    }

    /**
     * The signed-in staff member, stored as the id of their row in the
     * Supabase `users` table (the app's own user table keeps that link in
     * `supabase_id`).
     */
    private function staffId(?object $staff): ?int
    {
        $id = $staff?->supabase_id ?? $staff?->id ?? null;

        return $id === null || $id === '' ? null : (int) $id;
    }

    /**
     * Work out which conversation a new mail belongs to: our own reply ids
     * are matched first (that is the common case), then any earlier mail that
     * quoted it, and otherwise the mail starts its own thread.
     *
     * @return array{thread_key: ?string, parent_id: ?int}
     */
    private function resolveThread(?string $messageId, ?string $inReplyTo): array
    {
        $ownThread = null;
        $parentId = null;

        foreach (array_filter([$inReplyTo]) as $reference) {
            $replied = $this->supabase->query(self::REPLIES_TABLE, [
                'select' => 'id,info_email_id,message_id',
                'message_id' => "eq.{$reference}",
                'limit' => 1,
            ]);

            if (! empty($replied[0])) {
                $original = $this->find((int) $replied[0]['info_email_id']);
                $ownThread = $original->thread_key ?: $original->message_id ?: $ownThread;
                $parentId = (int) $original->id;

                break;
            }
        }

        if ($ownThread === null && $inReplyTo !== null) {
            $parent = $this->findByMessageId($inReplyTo);
            if ($parent !== null) {
                $ownThread = $parent['thread_key'] ?: $parent['message_id'];
                $parentId = (int) $parent['id'];
            }
        }

        return [
            'thread_key' => $ownThread ?: $messageId,
            'parent_id' => $parentId,
        ];
    }

    /**
     * The Date header decides when a mail is considered to have arrived, so a
     * backlog is not filed under today.
     */
    private function receivedAt(?string $dateHeader): string
    {
        if ($dateHeader) {
            try {
                return Carbon::parse($dateHeader)->toIso8601String();
            } catch (\Throwable $e) {
                // Unparseable Date header — fall through to now.
            }
        }

        return now()->toIso8601String();
    }

    /**
     * Raw sources can be large; keep what fits and note the rest.
     */
    private function cap(string $raw, int $limit = 200000): string
    {
        if (strlen($raw) <= $limit) {
            return $raw;
        }

        return substr($raw, 0, $limit) . "\n\n[truncated by the app at " . strlen($raw) . " bytes]";
    }

    /**
     * Strip the PostgREST filter characters a search term may contain.
     */
    private function clean(string $value): string
    {
        return trim(preg_replace('/[(),%*]/', ' ', $value) ?? $value);
    }
}
