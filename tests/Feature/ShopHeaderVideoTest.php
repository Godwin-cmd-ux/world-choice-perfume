<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The shop's header plays reef_33.mp4.
 *
 * The clip is a file on disk that the markup names by hand, so the two can
 * drift: rename the file and the page looks fine in the editor while the
 * header silently stops playing, with no error anywhere — the visitor simply
 * gets the overlay on an empty box. Both halves are pinned here: the name the
 * page asks for, and the file that name has to resolve to.
 */
class ShopHeaderVideoTest extends TestCase
{
    public function test_the_shop_header_plays_the_new_clip(): void
    {
        $source = file_get_contents(resource_path('views/customer/products/index.blade.php'));

        $this->assertStringContainsString("asset('videos/reef_33.mp4')", $source);
        $this->assertStringNotContainsString("videos/reef-33.mp4", $source);
    }

    public function test_the_clip_is_where_the_page_expects_it(): void
    {
        $this->assertFileExists(public_path('videos/reef_33.mp4'));

        // the replaced file is gone, so nothing can quietly keep serving it
        $this->assertFileDoesNotExist(public_path('videos/reef-33.mp4'));
    }
}
