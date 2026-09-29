<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Calling the shop from the public pages.
 *
 * A printed phone number is no use to someone holding a phone; they have to
 * copy it off the screen and paste it into the dialer, which is where most
 * of them give up. Both places that print it therefore link to `tel:` so a
 * tap opens the call.
 *
 * The link has to carry the same number that is written next to it. A `tel:`
 * href is a dial string and not a sentence, so the spaces and brackets of the
 * printed form have to come out, and the digits that remain have to be the
 * digits on screen. A page where the two disagree looks completely correct
 * and calls a stranger, so the pair is checked rather than trusted.
 */
class ContactPhoneLinkTest extends TestCase
{
    public function test_tapping_the_contact_section_number_opens_the_dialer(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('tel:+255710603637', false);
    }

    /**
     * The heart of it: every number the page will dial has to be the number it
     * printed, with only the punctuation a dial string cannot carry removed.
     */
    public function test_the_number_dialled_is_the_number_shown(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        preg_match_all('/href="tel:([^"]+)"/', $response->getContent(), $matches);

        $this->assertNotEmpty($matches[1], 'the page has no tel: link at all');
        $this->assertSame(
            [preg_replace('/[^\d+]/', '', config('contact.phone'))],
            array_values(array_unique($matches[1]))
        );
    }

    /**
     * The number is written in more than one place, so it is read from the
     * configuration rather than typed into each page. Changing the setting
     * moves every copy and the dialled digits with it.
     */
    public function test_the_number_follows_the_configuration(): void
    {
        config(['contact.phone' => '+255 754 111 222', 'contact.dial' => '+255754111222']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('tel:+255754111222', false);
        $response->assertSee('+255 754 111 222');
        $response->assertDontSee('tel:+255710603637', false);
    }

    public function test_the_footer_number_opens_the_dialer_too(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        // The contact section and the footer are separate markup, so the link
        // is expected on both rather than on whichever one renders first.
        $this->assertSame(2, substr_count($response->getContent(), 'tel:+255710603637'));
    }
}
