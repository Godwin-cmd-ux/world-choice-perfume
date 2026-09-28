<?php

namespace Tests\Feature;

use App\Mail\InfoMailReply;
use Tests\TestCase;

class InfoMailReplyTest extends TestCase
{
    public function test_it_answers_the_sender_as_info_with_the_original_quoted(): void
    {
        $mailable = (new InfoMailReply(
            replyToName: 'Amina Yusuf',
            mailSubject: 'Re: Reef 33 price',
            bodyHtml: 'Jambo Amina, yes it is in stock.',
            originalText: "Jambo,\n\nHow much is Reef 33?",
        ))
            ->from('info@worldchoiceperfume.com', 'World Choice Perfumes')
            ->to('amina@example.co.tz', 'Amina Yusuf')
            ->replyTo('info@worldchoiceperfume.com', 'World Choice Perfumes');

        $envelope = $mailable->envelope();

        $this->assertSame('Re: Reef 33 price', $envelope->subject);

        $rendered = $mailable->render();

        $this->assertStringContainsString('Jambo Amina, yes it is in stock.', $rendered);
        $this->assertStringContainsString('How much is Reef 33?', $rendered);
        $this->assertStringContainsString('On Amina Yusuf wrote', $rendered);
    }

    public function test_it_renders_a_plain_text_twin_of_the_answer(): void
    {
        $mailable = new InfoMailReply(
            replyToName: 'Amina Yusuf',
            mailSubject: 'Re: Reef 33 price',
            bodyHtml: 'Jambo Amina, yes it is in stock.',
            originalText: 'How much is Reef 33?',
        );

        $text = $this->plainTextOf($mailable);

        $this->assertStringContainsString('Jambo Amina, yes it is in stock.', $text);
        $this->assertStringContainsString('> How much is Reef 33?', $text);
        $this->assertStringContainsString('info@worldchoiceperfume.com', $text);
    }

    /**
     * The text/plain part of the mail, i.e. what a phone or a plain-text
     * mail client actually shows.
     */
    private function plainTextOf(InfoMailReply $mailable): string
    {
        $content = $mailable->content();

        return (string) view($content->text, $content->with)->render();
    }
}
