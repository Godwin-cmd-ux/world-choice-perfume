<?php

namespace Tests\Feature;

use App\Services\SupabaseService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The pre-login pages behave like tabs on a phone: a horizontal swipe moves to
 * the next one (or back), in the order Home → Shop → Track Order → Contact →
 * News → Staff.
 *
 * What is worth pinning is not the gesture loop but the two decisions around
 * it: which page counts as which tab (a wrong matcher sends a swipe to the
 * wrong place, silently), and the cases the gesture must leave alone — an open
 * dialog, a horizontally scrollable strip, a form field. Those are the ways a
 * swipe listener usually becomes a bug: it steals a drag that belonged to the
 * filter strip, or it navigates away while someone is typing in a product
 * search.
 *
 * Rendered pages are used where the page can answer without a live project
 * (the public routes are wrapped in try/catch, and the per-table fake below
 * removes even the attempt); the gesture guards themselves are pinned by
 * reading the partial's source, the same way StorefrontBranchVisibilityTest
 * pins the branch filter.
 */
class PreLoginSwipeNavigationTest extends TestCase
{
    /** The tab order a finger moves through. */
    private const TAB_ORDER = ['Home', 'Shop', 'Track Order', 'Contact', 'News', 'Staff'];

    protected function setUp(): void
    {
        parent::setUp();

        // SupabaseService caches its last answers in a static, which outlives a
        // single test. Clear it so one test's rows cannot answer the next one.
        $cache = new \ReflectionProperty(SupabaseService::class, 'queryCache');
        $cache->setAccessible(true);
        $cache->setValue(null, []);

        Http::fake(['*' => Http::response([], 200)]);
    }

    /** Reads the tab payload the layout renders. */
    private function swipePayload(string $html): array
    {
        preg_match('/data-swipe-index="(-?\d+)"/', $html, $index);
        preg_match("/data-swipe-tabs='([^']*)'/", $html, $tabs);

        $this->assertNotEmpty($tabs, 'the page does not carry the swipe tabs');

        return [
            'index' => (int) ($index[1] ?? -1),
            // the attribute is HTML-escaped in the response, exactly as the
            // browser receives it; decode before reading the JSON back
            'tabs' => json_decode(html_entity_decode($tabs[1], ENT_QUOTES), true),
        ];
    }

    /**
     * @param  int  $index  the tab the page should consider itself to be
     */
    private function assertSwipeState(string $path, int $index): void
    {
        $response = $this->get($path);

        $response->assertOk();

        $payload = $this->swipePayload($response->getContent());

        $this->assertSame($index, $payload['index'], "$path: wrong tab index");
        $this->assertSame(
            self::TAB_ORDER,
            array_column($payload['tabs'], 'label'),
            "$path: the swipe order changed"
        );
    }

    public function test_every_pre_login_page_knows_which_tab_it_is(): void
    {
        $this->assertSwipeState('/', 0);                  // Home
        $this->assertSwipeState('/products', 1);          // Shop
        $this->assertSwipeState('/orders/track', 2);      // Track Order
        $this->assertSwipeState('/news', 4);              // News
        $this->assertSwipeState('/login', 5);             // Staff
    }

    public function test_the_tabs_are_the_six_pre_login_destinations(): void
    {
        $payload = $this->swipePayload($this->get('/login')->getContent());

        $this->assertCount(6, $payload['tabs']);
        $this->assertSame([
            route('home'),
            route('customer.products.index'),
            route('customer.orders.track'),
            // Contact is the footer of the home page, not a page of its own.
            route('home').'#contact',
            route('customer.news'),
            route('login'),
        ], array_column($payload['tabs'], 'url'));
    }

    /** The public layout is the only one the pre-login visitor lands on. */
    public function test_the_public_layout_ships_the_gesture(): void
    {
        $source = file_get_contents(resource_path('views/layouts/public.blade.php'));

        $this->assertStringContainsString("partials.swipe-nav", $source);
        $this->assertStringContainsString('data-swipe-block', $source);
    }

    public function test_the_gesture_keeps_out_of_the_way_of_the_page(): void
    {
        $source = file_get_contents(resource_path('views/partials/swipe-nav.blade.php'));

        // a dialog owns the screen while it is up
        $this->assertStringContainsString("getElementById('staffLoginModal')", $source);
        $this->assertStringContainsString('[data-swipe-block]', $source);

        // a sideways-scrolling strip (or a field, or a selection) keeps the drag
        $this->assertStringContainsString('scrollsSideways', $source);
        $this->assertStringContainsString('[data-no-swipe]', $source);
        $this->assertStringContainsString('getSelection', $source);
        $this->assertStringContainsString('input, textarea, select', $source);

        // and a drag that is really a scroll is not a swipe
        $this->assertStringContainsString('MAX_OFF_AXIS', $source);
        $this->assertStringContainsString('MIN_DISTANCE', $source);
        $this->assertStringContainsString("'touchcancel'", $source);
    }
}
