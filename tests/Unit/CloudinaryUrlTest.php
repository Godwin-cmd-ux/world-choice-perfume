<?php

namespace Tests\Unit;

use App\Services\CloudinaryService;
use Tests\TestCase;

/**
 * Replaced media is deleted by reading its public id back out of the URL the
 * database stores, so that parsing has to be right: get it wrong and the old
 * file stays in Cloudinary (space never reclaimed), or an unrelated URL is
 * handed to the destroy endpoint.
 */
class CloudinaryUrlTest extends TestCase
{
    public function test_it_reads_the_public_id_out_of_a_cloudinary_url(): void
    {
        $cloudinary = new CloudinaryService;

        $this->assertSame(
            'products/reef_1700000000',
            $cloudinary->publicIdFromUrl('https://res.cloudinary.com/demo/image/upload/v1700000000/products/reef_1700000000.jpg')
        );

        $this->assertSame(
            'shop/clip_1700000000',
            $cloudinary->publicIdFromUrl('https://res.cloudinary.com/demo/video/upload/v1700000000/shop/clip_1700000000.mp4')
        );

        // No version segment: the whole path after upload/ is the id.
        $this->assertSame(
            'brands/logo_1',
            $cloudinary->publicIdFromUrl('https://res.cloudinary.com/demo/image/upload/brands/logo_1.png')
        );
    }

    public function test_it_ignores_urls_the_app_does_not_own(): void
    {
        $cloudinary = new CloudinaryService;

        $this->assertNull($cloudinary->publicIdFromUrl(null));
        $this->assertNull($cloudinary->publicIdFromUrl(''));
        $this->assertNull($cloudinary->publicIdFromUrl('https://example.com/logo.png'));
        $this->assertNull($cloudinary->publicIdFromUrl(asset('videos/reef_33.mp4')));
    }
}
