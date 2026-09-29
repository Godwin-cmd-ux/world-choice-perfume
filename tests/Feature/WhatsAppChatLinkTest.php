<?php

namespace Tests\Feature;

use App\Services\WhatsAppService;
use Tests\TestCase;

/**
 * Chatting with a client on WhatsApp from their record.
 *
 * The number on a client record is whatever a member typed into a free text
 * box, and the form gives no guidance on format. `wa.me` is far stricter: it is
 * addressed by country code and digits, and a leading plus, a space or a
 * domestic zero all stop it resolving. The same value has to stay readable and
 * searchable on the record, so it is left as typed and translated at the point
 * the link is built.
 */
class WhatsAppChatLinkTest extends TestCase
{
    private function link(?string $stored): ?string
    {
        return (new WhatsAppService)->chatLink($stored);
    }

    public function test_a_domestic_number_gains_the_country_code(): void
    {
        $this->assertSame('https://wa.me/255710603637', $this->link('0710603637'));
    }

    public function test_the_separators_a_member_typed_are_removed(): void
    {
        $this->assertSame('https://wa.me/255710603637', $this->link('+255 710 603 637'));
        $this->assertSame('https://wa.me/255710603637', $this->link('0710-603-637'));
        $this->assertSame('https://wa.me/255710603637', $this->link('(0710) 603 637'));
        $this->assertSame('https://wa.me/255710603637', $this->link(' 0710 603 637 '));
    }

    public function test_a_number_already_in_international_form_is_left_alone(): void
    {
        $this->assertSame('https://wa.me/255710603637', $this->link('255710603637'));
        $this->assertSame('https://wa.me/255710603637', $this->link('+255710603637'));
    }

    /**
     * A number WhatsApp cannot route is worse than no number at all, so
     * anything too short to be a real one is left as plain text on the record
     * rather than hung off a link that opens a stranger's chat.
     */
    public function test_a_number_too_short_to_dial_gets_no_link(): void
    {
        $this->assertNull($this->link('123'));
        $this->assertNull($this->link('0'));
        $this->assertNull($this->link('abc'));
    }

    public function test_a_missing_number_gets_no_link(): void
    {
        $this->assertNull($this->link(null));
        $this->assertNull($this->link(''));
        $this->assertNull($this->link('   '));
    }

    /**
     * E.164 tops out at fifteen digits, so a longer string is a note or a
     * second number pasted in by mistake rather than a phone number.
     */
    public function test_a_number_longer_than_a_phone_number_gets_no_link(): void
    {
        $this->assertNull($this->link('2557106036379999'));
    }
}
