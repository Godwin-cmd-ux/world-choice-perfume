<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The public landing page.
 *
 * The contact section is the one place a visitor can be told the address of the
 * mailbox that Customer Care actually reads, so it is worth pinning down: the
 * address has to come from the same setting the app sends and receives with,
 * not from a copy typed into the markup.
 */
class LandingPageContactTest extends TestCase
{
    public function test_the_contact_section_shows_the_mailbox_the_app_reads(): void
    {
        config(['info_mail.address' => 'info@worldchoiceperfume.com']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('info@worldchoiceperfume.com');
        $response->assertSee('mailto:info@worldchoiceperfume.com', false);
        $response->assertSee('Email us');
    }

    /**
     * A hard coded address on the page would go stale the moment the setting
     * changed, so the page follows the configuration.
     */
    public function test_the_contact_address_follows_the_configuration(): void
    {
        config(['info_mail.address' => 'care@example.co.tz']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('mailto:care@example.co.tz', false);
        $response->assertDontSee('mailto:info@worldchoiceperfume.com', false);
    }
}
