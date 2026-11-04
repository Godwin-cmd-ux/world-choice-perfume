<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The shop header clip is managed by the graphics designer.
 *
 * It used to be a file name written into the shop blade, so only a developer
 * could change it. The page now plays whatever the designer uploaded
 * (company_settings.shop_hero_video_url), falls back to the bundled clip when
 * nothing has been chosen, and plays nothing when the designer deleted it.
 * These assertions pin the wiring on both sides and the bundled file the
 * fallback resolves to.
 */
class ShopHeaderVideoTest extends TestCase
{
    public function test_the_shop_page_plays_the_managed_clip_and_can_hide_it(): void
    {
        $source = file_get_contents(resource_path('views/customer/products/index.blade.php'));

        $this->assertStringContainsString('@if($heroVideoUrl)', $source);
        $this->assertStringContainsString('{{ $heroVideoUrl }}', $source);
        $this->assertStringContainsString('{{ $heroVideoType }}', $source);

        // Nothing in the markup may name the file directly any more — that is
        // what made the clip impossible for the designer to change.
        $this->assertStringNotContainsString("asset('videos/reef_33.mp4')", $source);
    }

    public function test_the_shop_controller_reads_the_designers_setting(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Customer/ProductController.php'));

        $this->assertStringContainsString("CompanySettingService::get('shop_hero_video_url')", $source);
        $this->assertStringContainsString("asset('videos/reef_33.mp4')", $source);
    }

    public function test_the_graphic_designer_can_manage_the_clip(): void
    {
        $this->assertTrue(Route::has('graphic-designer.shop-video.index'));
        $this->assertTrue(Route::has('graphic-designer.shop-video.store'));
        $this->assertTrue(Route::has('graphic-designer.shop-video.destroy'));
    }

    public function test_the_bundled_fallback_clip_exists(): void
    {
        $this->assertFileExists(public_path('videos/reef_33.mp4'));
    }
}
