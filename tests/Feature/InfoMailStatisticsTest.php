<?php

namespace Tests\Feature;

use App\Services\InfoMailService;
use App\Services\RawEmailParser;
use App\Services\SupabaseService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The figures behind the dashboard's mailbox panel.
 *
 * These drive the real service against a double that returns rows in the shape
 * the Supabase client does, and that honours the gte.<iso> window the service
 * applies, so the counting and the arithmetic are actually exercised.
 */
class InfoMailStatisticsTest extends TestCase
{
    public function test_it_counts_the_mailbox_over_the_last_thirty_days(): void
    {
        $stats = $this->statistics(
            mails: [
                ['id' => 1, 'received_at' => $this->daysAgo(1, 9), 'status' => 'new', 'has_attachments' => false],
                ['id' => 2, 'received_at' => $this->daysAgo(2, 9), 'status' => 'replied', 'has_attachments' => true],
                ['id' => 3, 'received_at' => $this->daysAgo(3, 9), 'status' => 'closed', 'has_attachments' => false],
                ['id' => 4, 'received_at' => $this->daysAgo(1, 14), 'status' => 'new', 'has_attachments' => false],
            ],
        );

        $this->assertSame(4, $stats['totalReceived']);
        $this->assertSame(4, $stats['received']);
        // new is "nobody has answered it yet", which is not the same as unread.
        $this->assertSame(2, $stats['awaiting']);
        $this->assertSame(1, $stats['answered']);
        $this->assertSame(1, $stats['withAttachments']);
    }

    /**
     * The headline is every mail ever received; the panel beside it is a month.
     * A year-old mail still has to be in the total, or the number a person
     * reads first would quietly be wrong.
     */
    public function test_the_total_covers_every_mail_but_the_breakdown_covers_one_month(): void
    {
        $stats = $this->statistics(
            mails: [
                ['id' => 1, 'received_at' => $this->daysAgo(40, 9), 'status' => 'new', 'has_attachments' => false],
                ['id' => 2, 'received_at' => $this->daysAgo(2, 9), 'status' => 'new', 'has_attachments' => false],
            ],
        );

        $this->assertSame(2, $stats['totalReceived']);
        $this->assertSame(1, $stats['received']);
        $this->assertSame(1, $stats['awaiting']);
    }

    public function test_the_week_of_bars_covers_the_arrivals_on_each_day(): void
    {
        $stats = $this->statistics(
            mails: [
                ['id' => 1, 'received_at' => $this->daysAgo(1, 9), 'status' => 'new', 'has_attachments' => false],
                ['id' => 2, 'received_at' => $this->daysAgo(1, 16), 'status' => 'new', 'has_attachments' => false],
                ['id' => 3, 'received_at' => $this->daysAgo(2, 9), 'status' => 'new', 'has_attachments' => false],
            ],
        );

        $daily = $stats['daily'];
        $this->assertCount(7, $daily, 'a week of bars is seven days, including the quiet ones');

        $yesterday = Carbon::now('Africa/Dar_es_Salaam')->subDays(1)->format('Y-m-d');
        $this->assertSame(2, $daily[$yesterday]);
        $this->assertSame(3, array_sum($daily));
    }

    /**
     * A customer waits for the first answer. A second answer to the same mail
     * says nothing about how long they waited, so only the earliest counts.
     */
    public function test_only_the_first_reply_to_a_mail_sets_the_response_time(): void
    {
        $received = Carbon::now('Africa/Dar_es_Salaam')->subDays(2)->setTime(9, 0);

        $stats = $this->statistics(
            mails: [['id' => 2, 'received_at' => $received->toIso8601String(), 'status' => 'replied', 'has_attachments' => false]],
            replies: [
                ['info_email_id' => 2, 'sent_at' => $received->copy()->addMinutes(30)->toIso8601String(), 'created_at' => $received->copy()->addMinutes(30)->toIso8601String(), 'status' => 'sent'],
                ['info_email_id' => 2, 'sent_at' => $received->copy()->addHours(6)->toIso8601String(), 'created_at' => $received->copy()->addHours(6)->toIso8601String(), 'status' => 'sent'],
            ],
        );

        $this->assertSame(2, $stats['repliesSent'], 'both answers were really sent');
        $this->assertSame(30, $stats['firstReplyMinutes'], 'but the wait is the first one');
        $this->assertSame(1, $stats['answeredForTiming']);
        $this->assertSame('30 min', $stats['firstReplyLabel']);
    }

    /**
     * A refused send is a number the person reading the dashboard needs to see,
     * and it is not a reply: a failed send never ends a wait.
     */
    public function test_a_failed_send_is_counted_separately_and_does_not_end_a_wait(): void
    {
        $received = Carbon::now('Africa/Dar_es_Salaam')->subDays(1)->setTime(9, 0);

        $stats = $this->statistics(
            mails: [['id' => 1, 'received_at' => $received->toIso8601String(), 'status' => 'new', 'has_attachments' => false]],
            replies: [
                ['info_email_id' => 1, 'sent_at' => null, 'created_at' => $received->copy()->addHour()->toIso8601String(), 'status' => 'failed'],
            ],
        );

        $this->assertSame(0, $stats['repliesSent']);
        $this->assertSame(1, $stats['repliesFailed']);
        $this->assertNull($stats['firstReplyMinutes']);
        $this->assertNull($stats['firstReplyLabel']);
    }

    /** A mail nobody has answered is simply not part of an average. */
    public function test_a_mail_with_no_answer_is_left_out_of_the_average(): void
    {
        $received = Carbon::now('Africa/Dar_es_Salaam')->subDays(1)->setTime(9, 0);

        $stats = $this->statistics(
            mails: [
                ['id' => 1, 'received_at' => $received->toIso8601String(), 'status' => 'replied', 'has_attachments' => false],
                ['id' => 2, 'received_at' => $received->toIso8601String(), 'status' => 'new', 'has_attachments' => false],
            ],
            replies: [
                ['info_email_id' => 1, 'sent_at' => $received->copy()->addMinutes(20)->toIso8601String(), 'created_at' => $received->toIso8601String(), 'status' => 'sent'],
            ],
        );

        $this->assertSame(20, $stats['firstReplyMinutes']);
        $this->assertSame(1, $stats['answeredForTiming']);
    }

    /** Over a day, a wait reads in days, because "1500 min" helps nobody. */
    public function test_a_wait_of_more_than_a_day_is_read_in_days(): void
    {
        $received = Carbon::now('Africa/Dar_es_Salaam')->subDays(3)->setTime(9, 0);

        $stats = $this->statistics(
            mails: [['id' => 1, 'received_at' => $received->toIso8601String(), 'status' => 'replied', 'has_attachments' => false]],
            replies: [
                ['info_email_id' => 1, 'sent_at' => $received->copy()->addHours(26)->toIso8601String(), 'created_at' => $received->toIso8601String(), 'status' => 'sent'],
            ],
        );

        $this->assertSame(1560, $stats['firstReplyMinutes']);
        $this->assertSame('1 day 2h', $stats['firstReplyLabel']);
    }

    /**
     * The wording of a wait. Every one of these is a real answer, and none of
     * them may be rounded down to "0" or truncated on the way to the page.
     */
    public function test_a_wait_is_written_out_rather_than_truncated(): void
    {
        $service = new InfoMailService(
            new class extends SupabaseService
            {
                public function __construct() {}
            },
            new RawEmailParser
        );

        $label = new \ReflectionMethod($service, 'waitLabel');

        $this->assertSame('0 min', $label->invoke($service, 0));
        $this->assertSame('45 min', $label->invoke($service, 45));
        $this->assertSame('1 hour', $label->invoke($service, 60));
        $this->assertSame('1 hour 30 min', $label->invoke($service, 90));
        $this->assertSame('2 hours 30 min', $label->invoke($service, 150));
        $this->assertSame('2 hours', $label->invoke($service, 120));
        $this->assertSame('1 day', $label->invoke($service, 1440));
        $this->assertSame('3 days 4h', $label->invoke($service, 4560));
        $this->assertNull($label->invoke($service, null));
    }

    /**
     * The dashboard is the page everyone lands on. A mailbox that cannot be
     * read has to leave the rest of the page standing.
     */
    public function test_a_broken_mailbox_leaves_the_figures_at_zero(): void
    {
        $down = new class extends SupabaseService
        {
            public function __construct() {}

            public function query(string $table, array $params = []): array
            {
                throw new \RuntimeException('Supabase is down');
            }

            public function count(string $table, array $conditions = []): int
            {
                throw new \RuntimeException('Supabase is down');
            }
        };

        $stats = (new InfoMailService($down, new RawEmailParser))->dashboardStatistics();

        $this->assertSame(0, $stats['totalReceived']);
        $this->assertSame(0, $stats['received']);
        $this->assertNull($stats['firstReplyMinutes']);
        // The bars are still drawn, so an unreachable mailbox looks empty
        // rather than broken.
        $this->assertCount(7, $stats['daily']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $mails
     * @param  array<int, array<string, mixed>>  $replies
     */
    private function statistics(array $mails, array $replies = []): array
    {
        $supabase = new class($mails, $replies) extends SupabaseService
        {
            public function __construct(private array $mails, private array $replies) {}

            public function query(string $table, array $params = []): array
            {
                $rows = $table === InfoMailService::REPLIES_TABLE ? $this->replies : $this->mails;

                foreach (['received_at', 'created_at'] as $column) {
                    $filter = $params[$column] ?? null;
                    if (is_string($filter) && str_starts_with($filter, 'gte.')) {
                        $from = substr($filter, 4);
                        $rows = array_values(array_filter(
                            $rows,
                            fn ($r) => (string) ($r[$column] ?? '') >= $from
                        ));
                    }
                }

                return $rows;
            }

            public function count(string $table, array $conditions = []): int
            {
                return $table === InfoMailService::REPLIES_TABLE ? count($this->replies) : count($this->mails);
            }
        };

        return (new InfoMailService($supabase, new RawEmailParser))->dashboardStatistics();
    }

    private function daysAgo(int $days, int $hour): string
    {
        return Carbon::now('Africa/Dar_es_Salaam')
            ->subDays($days)
            ->setTime($hour, 0)
            ->toIso8601String();
    }
}
