<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\NavigationCountsService;
use App\Services\StockNotificationService;
use App\Services\SupabaseService;
use Tests\TestCase;

/**
 * The Super Admin badge is the unread admin_notifications count.
 *
 * Marking an alert read flips is_read, so the very next count poll must show a
 * smaller number. The stock-alert feed stays deduplicated on that same poll.
 */
class NotificationCountDownTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $property = new \ReflectionProperty(StockNotificationService::class, 'syncedThisRequest');
        $property->setValue(null, false);
    }

    public function test_the_notification_badge_counts_down_when_an_alert_is_read(): void
    {
        $supabase = $this->fakeSupabase();
        $service = new NavigationCountsService($supabase);
        $admin = new User;
        $admin->forceFill([
            'id' => 1,
            'name' => 'Super Admin',
            'role' => 'super_admin',
            'status' => 'approved',
        ]);

        $this->assertSame(2, $service->forUser($admin)['notifications']);

        // This is the write NotificationController@markRead performs.
        $supabase->notifications[0]['is_read'] = true;

        $this->assertSame(1, $service->forUser($admin)['notifications']);
    }

    private function fakeSupabase(): SupabaseService
    {
        return new class extends SupabaseService
        {
            /** @var array<int, array> */
            public array $notifications = [
                ['id' => 1, 'type' => 'stock_alert', 'data' => '{"alert_key":"bottle:8:50:plain"}', 'is_read' => false],
                ['id' => 2, 'type' => 'stock_alert', 'data' => '{"alert_key":"oil:8:5"}', 'is_read' => false],
            ];

            public function __construct() {}

            public function query(string $table, array $params = []): array
            {
                return $table === 'admin_notifications' ? $this->notifications : [];
            }

            public function queryFresh(string $table, array $params = []): array
            {
                return [];
            }

            public function count(string $table, array $conditions = []): int
            {
                if ($table === 'admin_notifications' && ($conditions['is_read'] ?? null) === 'eq.false') {
                    return count(array_filter($this->notifications, fn ($n) => empty($n['is_read'])));
                }

                return 0;
            }

            public function tableHasColumn(string $table, string $column): bool
            {
                return false;
            }
        };
    }
}
