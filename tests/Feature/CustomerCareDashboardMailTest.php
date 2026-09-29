<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\InfoMailService;
use App\Services\SupabaseService;
use Tests\TestCase;

/**
 * The mailbox figures on the Customer Care dashboard.
 *
 * info@ is company-wide, so the panel is for Head Quarters only. Every other
 * customer care member sees the same dashboard without it, and the only thing
 * standing between them and the mailbox is the isHq check — so it is worth a
 * test rather than a comment.
 */
class CustomerCareDashboardMailTest extends TestCase
{
    public function test_head_quarters_sees_the_mailbox_figures_and_the_latest_mail(): void
    {
        $this->fakeData();
        $this->fakeMailbox();
        $this->loginAs(10);

        $response = $this->get('/customer-care/dashboard');

        $response->assertOk();
        $response->assertSee('Email Statistics');
        $response->assertSee('Recent Mails');
        $response->assertSee('Mails Received');
        // The figures themselves.
        $response->assertSee('Awaiting Answer');
        $response->assertSee('Average First Reply');
        $response->assertSee('1 hour 30 min');
        // The week of bars, and the latest mail linking into the mailbox.
        $response->assertSee('Last 7 days received');
        $response->assertSee('Reef 33 price');
        $response->assertSee(route('customer-care.mails.show', 7), false);
    }

    /** Another branch's staff member must not see a company-wide mailbox. */
    public function test_a_branch_customer_care_member_does_not_see_the_mailbox(): void
    {
        $this->fakeData();
        $this->fakeMailbox();
        $this->loginAs(11);

        $response = $this->get('/customer-care/dashboard');

        $response->assertOk();
        $response->assertDontSee('Email Statistics');
        $response->assertDontSee('Recent Mails');
        $response->assertDontSee('Reef 33 price');
        // The rest of the dashboard is untouched.
        $response->assertSee('Total Sales');
    }

    /**
     * Answers the branch lookup, the sales and order reads, and nothing else,
     * so the page under test never reaches the network.
     */
    private function fakeData(): void
    {
        $supabase = new class extends SupabaseService
        {
            public function __construct() {}

            public function query(string $table, array $params = []): array
            {
                return match ($table) {
                    // The branch lookup filters on id, and getting this wrong
                    // would quietly promote every branch to Head Quarters.
                    'branches' => $this->branchesMatching($params),
                    'sales' => [
                        ['id' => 1, 'total' => 250000, 'created_at' => '2026-09-28T10:00:00+03:00', 'customer_id' => null, 'cashier_id' => null, 'branch_id' => 10],
                    ],
                    'orders' => [
                        ['id' => 1, 'total' => 120000, 'status' => 'pending', 'created_at' => '2026-09-28T10:00:00+03:00'],
                    ],
                    'customers' => [
                        ['id' => 1, 'name' => 'Amina Yusuf', 'phone' => '0754', 'created_at' => '2026-09-28T10:00:00+03:00'],
                    ],
                    'inquiries' => [
                        ['id' => 1, 'subject' => 'Do you stock Noir?', 'user_id' => null, 'is_read' => false, 'created_at' => '2026-09-28T10:00:00+03:00'],
                    ],
                    'users' => [],
                    'sale_items', 'order_items' => [],
                    default => [],
                };
            }

            /** Honours the eq./in. filters the branch lookups are made with. */
            private function branchesMatching(array $params): array
            {
                $branches = [
                    ['id' => 10, 'name' => 'Head Quarters-Mikocheni'],
                    ['id' => 11, 'name' => 'Mikocheni'],
                ];

                $filter = $params['id'] ?? null;
                if (! is_string($filter)) {
                    return $branches;
                }

                if (str_starts_with($filter, 'in.(')) {
                    $wanted = array_map('intval', explode(',', rtrim(substr($filter, 4), ')')));

                    return array_values(array_filter($branches, fn ($b) => in_array((int) $b['id'], $wanted, true)));
                }

                if (str_starts_with($filter, 'eq.')) {
                    $wanted = (int) substr($filter, 3);

                    return array_values(array_filter($branches, fn ($b) => (int) $b['id'] === $wanted));
                }

                return $branches;
            }

            public function count(string $table, array $conditions = []): int
            {
                return match ($table) {
                    'inquiries' => 4,
                    'orders' => 1,
                    default => 0,
                };
            }
        };

        $this->app->instance(SupabaseService::class, $supabase);
    }

    private function fakeMailbox(): void
    {
        $mail = (object) [
            'id' => 7,
            'subject' => 'Reef 33 price',
            'from_name' => 'Amina Yusuf',
            'from_email' => 'amina@example.co.tz',
            'is_read' => false,
            'status' => 'new',
            'has_attachments' => true,
            'received_at' => '2026-09-28T09:14:22+03:00',
        ];

        $mailbox = new class($mail) extends InfoMailService
        {
            public function __construct(private object $mail) {}

            public function dashboardStatistics(): array
            {
                return [
                    'totalReceived' => 42,
                    'received' => 9,
                    'awaiting' => 2,
                    'answered' => 6,
                    'withAttachments' => 3,
                    'repliesSent' => 6,
                    'repliesFailed' => 1,
                    'firstReplyMinutes' => 90,
                    'firstReplyLabel' => '1 hour 30 min',
                    'answeredForTiming' => 6,
                    'daily' => [
                        '2026-09-22' => 1,
                        '2026-09-23' => 0,
                        '2026-09-24' => 2,
                        '2026-09-25' => 0,
                        '2026-09-26' => 1,
                        '2026-09-27' => 0,
                        '2026-09-28' => 5,
                    ],
                ];
            }

            public function unreadCount(): int
            {
                return 4;
            }

            public function inbox(array $filters = [], int $page = 1): array
            {
                // A collection, the way the real service hands its page back.
                return [collect([$this->mail]), 1];
            }
        };

        // The real service is only here so the class resolves the way the
        // controller expects; every read it makes is replaced above.
        $this->app->instance(InfoMailService::class, $mailbox);
    }

    private function loginAs(int $branchId): void
    {
        $user = new User;
        $user->forceFill([
            'id' => 14,
            'supabase_id' => 31,
            'name' => 'Gideon Msuya',
            'email' => 'gideonmsuya140@gmail.com',
            'role' => 'customer_care',
            'branch_id' => $branchId,
            'status' => 'active',
        ]);

        $this->be($user);
    }
}
