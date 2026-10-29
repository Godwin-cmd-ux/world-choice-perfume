<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The public navbar belongs to the page, not to the viewport.
 *
 * It used to be position:fixed, so it stayed pinned over the content while the
 * home page scrolled underneath it. It should scroll away like any other part
 * of the page — the pages below already pad for its height, so only its
 * positioning has to stay honest.
 */
class PublicNavbarScrollTest extends TestCase
{
    public function test_the_home_navbar_scrolls_away_instead_of_staying_pinned(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/<nav[^>]*id="mainNav"[^>]*>/',
            $html,
            'the public navbar should still be on the page'
        );

        preg_match('/<nav[^>]*id="mainNav"[^>]*>/', $html, $matches);
        $tag = $matches[0];

        $this->assertStringContainsString('absolute', $tag, 'the navbar should be positioned in the page, not the viewport');
        $this->assertStringNotContainsString('fixed', $tag, 'a fixed navbar stays pinned while the page scrolls');
        $this->assertStringContainsString('top-0', $tag);
    }
}
