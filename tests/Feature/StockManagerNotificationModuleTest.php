<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StockNotificationService;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Http;
use ReflectionProperty;
use Tests\TestCase;

/**
 * The stock manager's notification module.
 *
 * Alerts are kept in the shared admin_notifications feed, so the thing being
 * pinned here is the boundary: a manager only sees and marks read the stock
 * alerts of the branch his pages are scoped to, and his bell counts those same
 * unread rows.
 */
class StockManagerNotificationModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SupabaseService::clearCache();
        $guard = new ReflectionProperty(StockNotificationService::class, 'syncedThisRequest');
        $guard->setValue(null, false);
    }

    public function test_the_page_lists_only_this_branchs_stock_alerts(): void
    {
        $notifications = $this->notifications();
        $this->fakeSupabase($notifications);
        $this->actingAsStockManager();

        $response = $this->get(route('stock-manager.notifications.index'));

        $response->assertOk()
            ->assertSee('Reef 33 is at 2 units', false)
            ->assertSee('Blue Ocean bottles are below 100', false)
            ->assertDontSee('Dune is at 1 units in another branch', false)
            ->assertDontSee('Audit noise from this branch', false)
            ->assertSee(route('stock-manager.notifications.index'), false)
            ->assertSee('data-nav-count="notifications"', false);

        $listQuery = collect($this->notificationQueries)
            ->first(fn ($query) => ($query['select'] ?? null) === '*');

        $this->assertNotNull($listQuery);
        $this->assertSame('eq.' . StockNotificationService::TYPE, $listQuery['type'] ?? null);
        $this->assertSame('eq.8', $listQuery['branch_id'] ?? null);
    }

    public function test_marking_an_alert_read_is_branch_scoped_and_counts_down(): void
    {
        $notifications = $this->notifications();
        $this->fakeSupabase($notifications);
        $this->actingAsStockManager();

        $this->get(route('stock-manager.notifications.index'))
            ->assertOk()
            ->assertSee('"notifications":2', false);

        $response = $this->post(route('stock-manager.notifications.mark-read', 101));

        $response->assertRedirect();
        $this->assertSame([
            'type' => 'eq.stock_alert',
            'branch_id' => 'eq.8',
            'id' => 'eq.101',
        ], $this->patchConditions);
        $this->assertTrue((bool) $notifications[0]['is_read']);

        $this->get(route('stock-manager.notifications.index'))
            ->assertOk()
            ->assertSee('"notifications":1', false);
    }

    public function test_a_manager_cannot_mark_another_branchs_alert_read(): void
    {
        $notifications = $this->notifications();
        $this->fakeSupabase($notifications);
        $this->actingAsStockManager();

        $this->post(route('stock-manager.notifications.mark-read', 103))->assertRedirect();

        $this->assertFalse((bool) $notifications[2]['is_read'], 'Branch 9 must stay unread.');
        $this->assertSame('eq.8', $this->patchConditions['branch_id'] ?? null);
    }

    private function notifications(): array
    {
        return [
            [
                'id' => 101,
                'type' => 'stock_alert',
                'branch_id' => 8,
                'title' => 'Brand Product Low Stock',
                'message' => 'Reef 33 is at 2 units at Dodoma branch (alert threshold: 2).',
                'data' => '{"alert_key":"brand:8:1"}',
                'is_read' => false,
                'created_at' => now()->toIso8601String(),
            ],
            [
                'id' => 102,
                'type' => 'stock_alert',
                'branch_id' => 8,
                'title' => 'Bottle Stock Low',
                'message' => 'Blue Ocean bottles are below 100 at Dodoma branch — currently 80.',
                'data' => '{"alert_key":"bottle:8:50:plain"}',
                'is_read' => false,
                'created_at' => now()->toIso8601String(),
            ],
            [
                'id' => 103,
                'type' => 'stock_alert',
                'branch_id' => 9,
                'title' => 'Brand Product Low Stock',
                'message' => 'Dune is at 1 units in another branch.',
                'data' => '{"alert_key":"brand:9:2"}',
                'is_read' => false,
                'created_at' => now()->toIso8601String(),
            ],
            [
                'id' => 104,
                'type' => 'stock_adjusted',
                'branch_id' => 8,
                'title' => 'Stock Adjusted',
                'message' => 'Audit noise from this branch.',
                'data' => '{}',
                'is_read' => false,
                'created_at' => now()->toIso8601String(),
            ],
        ];
    }

    private function fakeSupabase(array &$notifications): void
    {
        $this->notificationQueries = [];
        $this->patchConditions = [];

        Http::fake(function ($request) use (&$notifications) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            $method = $request->method();
            $query = $this->queryParams($request);

            if (str_contains($path, '/rest/v1/admin_notifications')) {
                if ($method === 'HEAD') {
                    $unread = count(array_filter($notifications, fn ($n) => $this->matchesFilters($n, $query)));

                    return Http::response('', 200, ['Content-Range' => "0-0/{$unread}"]);
                }

                if ($method === 'GET') {
                    $this->notificationQueries[] = $query;

                    return Http::response(array_values(array_filter(
                        $notifications,
                        fn ($n) => $this->matchesFilters($n, $query)
                    )), 200);
                }

                if ($method === 'PATCH') {
                    $this->patchConditions = $query;
                    foreach ($notifications as &$notification) {
                        if ($this->matchesFilters($notification, $query)) {
                            $notification['is_read'] = true;
                            break;
                        }
                    }
                    unset($notification);

                    return Http::response([['id' => 1]], 200);
                }
            }

            return Http::response([], 200);
        });
    }

    private function queryParams($request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $params);

        return $params;
    }

    private function matchesFilters(array $notification, array $query): bool
    {
        foreach (['id', 'type', 'branch_id', 'is_read'] as $field) {
            if (!isset($query[$field]) || !str_starts_with((string) $query[$field], 'eq.')) {
                continue;
            }

            $expected = substr((string) $query[$field], 3);
            $actual = $notification[$field] ?? null;
            if ($field === 'is_read') {
                $actual = $actual ? 'true' : 'false';
            }

            if ((string) $actual !== $expected) {
                return false;
            }
        }

        return true;
    }

    private function actingAsStockManager(): static
    {
        $user = new User;
        $user->forceFill([
            'id' => 700,
            'supabase_id' => 700,
            'name' => 'Stock Manager',
            'email' => 'stock.manager@example.co.tz',
            'role' => 'stock_manager',
            'branch_id' => 8,
            'status' => 'active',
        ]);

        return $this->be($user);
    }
}
