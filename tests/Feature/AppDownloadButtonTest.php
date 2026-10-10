<?php

namespace Tests\Feature;

use App\Services\SupabaseService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The public navigation offers "Download Our App", in the header bar and in
 * the mobile menu, both pointing at the listing held in one place.
 *
 * The button is rendered twice and the listing is a URL that will change once
 * (from the placeholder to the real store page). Two hard-coded copies is how
 * the phone menu ends up sending a visitor to a different app than the desktop
 * bar, so the link is asserted to come from config — changing the config is
 * asserted to change the rendered href, which is the only thing that proves
 * there is a single source.
 */
class AppDownloadButtonTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $cache = new \ReflectionProperty(SupabaseService::class, 'queryCache');
        $cache->setAccessible(true);
        $cache->setValue(null, []);

        Http::fake(['*' => Http::response([], 200)]);
    }

    public function test_the_home_page_offers_the_app_download(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Download Our App');

        // desktop bar and mobile menu, so a phone visitor is not left out
        $this->assertSame(2, substr_count($response->getContent(), 'Download Our App'));
        $this->assertSame(2, substr_count($response->getContent(), 'data-app-download'));
    }

    public function test_the_button_follows_the_configured_listing(): void
    {
        config(['app_download.url' => 'https://example.test/world-choice-app']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('https://example.test/world-choice-app', false);
    }

    public function test_the_link_is_written_down_once(): void
    {
        $source = file_get_contents(resource_path('views/layouts/public.blade.php'));

        $this->assertSame(2, substr_count($source, "config('app_download.url')"));

        // and the navbar does not carry a store URL of its own
        $this->assertStringNotContainsString('play.google.com', $source);
        $this->assertStringNotContainsString('apps.apple.com', $source);
    }
}
