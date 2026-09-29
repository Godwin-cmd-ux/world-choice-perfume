<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The public pages on a phone.
 *
 * Both the landing page and the shop used to drop to a single column below the
 * small breakpoint, which turned a browsing session into an endless scroll: one
 * oversized card per screenful. The fix was to go two up on a phone.
 *
 * These tests pin the two halves of that change apart. The mobile base classes
 * are what makes a phone usable, and the sm/lg values are the desktop layout
 * that already looked right and must not drift while this is being tuned.
 */
class PublicMobileLayoutTest extends TestCase
{
    public function test_the_landing_page_shows_two_categories_per_row_on_a_phone(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6', false);
    }

    public function test_the_landing_page_keeps_four_categories_per_row_on_a_desktop(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('lg:grid-cols-4', false);
        $response->assertDontSee('grid-cols-1 sm:grid-cols-2 lg:grid-cols-4', false);
    }

    /**
     * A full height hero on a phone pushes the categories an entire swipe down,
     * so the mobile hero is deliberately shorter than the desktop one.
     */
    public function test_the_landing_page_hero_is_shorter_on_a_phone(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('min-h-[75vh] sm:min-h-screen', false);
    }

    public function test_the_landing_page_category_cards_are_shorter_on_a_phone(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('relative h-52 sm:h-80 rounded-2xl', false);
    }

    /**
     * The shop reads its catalogue from Supabase inside the controller, so the
     * page cannot be rendered here without a live project. The grid is checked
     * in the view source instead.
     */
    public function test_the_shop_shows_two_products_per_row_on_a_phone(): void
    {
        $source = file_get_contents(resource_path('views/customer/products/index.blade.php'));

        $this->assertStringContainsString(
            'grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-6',
            $source
        );
    }

    public function test_the_shop_keeps_three_products_per_row_on_a_desktop(): void
    {
        $source = file_get_contents(resource_path('views/customer/products/index.blade.php'));

        $this->assertStringContainsString('lg:grid-cols-3', $source);
        $this->assertStringNotContainsString(
            'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
            $source
        );
    }

    /**
     * Two cards across a phone is only half of the job: the product picture has
     * to give up its fixed height as well, or the card body is still pushed off
     * the screen by one tall image.
     */
    public function test_the_shop_product_picture_is_proportional_on_a_phone(): void
    {
        $source = file_get_contents(resource_path('views/customer/products/index.blade.php'));

        $this->assertStringContainsString('aspect-[4/5] sm:aspect-auto sm:h-56', $source);
    }

    /**
     * The description and the View Details chip are what make a card taller than
     * its neighbour on a narrow screen, so they only appear once there is room.
     */
    public function test_the_shop_hides_the_tallest_card_extras_on_a_phone(): void
    {
        $source = file_get_contents(resource_path('views/customer/products/index.blade.php'));

        $this->assertStringContainsString('hidden sm:block text-xs text-gray-500', $source);
        $this->assertStringContainsString('hidden sm:inline-flex px-3 py-1.5 bg-gold-500/10', $source);
    }
}
