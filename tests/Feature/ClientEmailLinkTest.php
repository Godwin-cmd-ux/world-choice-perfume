<?php

namespace Tests\Feature;

use App\Http\Controllers\CustomerCare\CustomerController;
use Tests\TestCase;

/**
 * Answering a client from their record.
 *
 * The record exists to be acted on, and the client form lets an address be
 * left empty, so the email is shown on a page where a member may well need to
 * write back to the person. Reading the address off the screen to retype it
 * into a fresh message is the step the link removes.
 *
 * The address is checked before it becomes a link. Rows written before the
 * form validated this column can hold anything, and a mailto: built from
 * something that is not an address opens a compose window aimed at nothing,
 * which is a worse thing to put in front of a member than plain text.
 */
class ClientEmailLinkTest extends TestCase
{
    /**
     * The page reads its client from Supabase inside the controller, so the
     * route cannot be rendered here without a live project. The link is
     * exercised where it is built instead, the same way the reply timings in
     * InfoMailStatisticsTest are.
     */
    private function link(?string $stored): ?string
    {
        $controller = (new \ReflectionClass(CustomerController::class))->newInstanceWithoutConstructor();

        return (new \ReflectionMethod($controller, 'mailtoFor'))->invoke($controller, $stored);
    }

    public function test_an_address_becomes_a_mailto_link(): void
    {
        $this->assertSame('mailto:juma@example.co.tz', $this->link('juma@example.co.tz'));
    }

    /**
     * A member pasting an address tends to bring the space or the trailing
     * newline with it, and that has to come off before it is put in a link.
     */
    public function test_surrounding_whitespace_is_trimmed(): void
    {
        $this->assertSame('mailto:juma@example.co.tz', $this->link('  juma@example.co.tz  '));
        $this->assertSame('mailto:juma@example.co.tz', $this->link("juma@example.co.tz\n"));
    }

    public function test_the_address_is_left_exactly_as_stored(): void
    {
        $this->assertSame('mailto:Juma.Buyer@Example.co.tz', $this->link('Juma.Buyer@Example.co.tz'));
        $this->assertSame('mailto:juma+perfumes@example.co.tz', $this->link('juma+perfumes@example.co.tz'));
    }

    public function test_a_missing_address_gets_no_link(): void
    {
        $this->assertNull($this->link(null));
        $this->assertNull($this->link(''));
        $this->assertNull($this->link('   '));
    }

    /**
     * The column has held a phone number and a note in the past, and both are
     * plausible things for a member to have typed into a box asking for an
     * address. Neither can be replied to.
     */
    public function test_something_that_is_not_an_address_gets_no_link(): void
    {
        $this->assertNull($this->link('0710603637'));
        $this->assertNull($this->link('call me on whatsapp'));
        $this->assertNull($this->link('same as phone'));
        $this->assertNull($this->link('juma@'));
        $this->assertNull($this->link('juma example.co.tz'));
    }
}
