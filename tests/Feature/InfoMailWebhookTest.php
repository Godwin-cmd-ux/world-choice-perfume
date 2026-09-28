<?php

namespace Tests\Feature;

use App\Services\InfoMailService;
use Mockery;
use Tests\TestCase;

class InfoMailWebhookTest extends TestCase
{
    private const TOKEN = 'wcp_in_test_token';

    private const RAW = "From: Amina <amina@example.co.tz>\r\n"
        . "To: info@worldchoiceperfume.com\r\n"
        . "Subject: Reef 33 price\r\n"
        . "Message-ID: <webhook-1@example.co.tz>\r\n"
        . "\r\n"
        . "How much is Reef 33?\r\n";

    protected function setUp(): void
    {
        parent::setUp();
        config(['info_mail.inbound_webhook' => self::TOKEN]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * The controller is built through the container, so the real service is
     * replaced with a double: the webhook behaviour is under test here, not
     * Supabase.
     */
    private function fakeService(?array $stored = ['id' => 7], ?\Throwable $throws = null, bool $expectCall = true): InfoMailService
    {
        $service = Mockery::mock(InfoMailService::class);

        if (!$expectCall) {
            $this->app->instance(InfoMailService::class, $service);

            return $service;
        }

        if ($throws) {
            $service->shouldReceive('storeRawEmail')->andThrow($throws);
        } else {
            $service->shouldReceive('storeRawEmail')->andReturn($stored);
        }

        $this->app->instance(InfoMailService::class, $service);

        return $service;
    }

    public function test_it_stores_a_delivered_message(): void
    {
        $this->fakeService(['id' => 7]);

        $response = $this->postJson('/api/inbound-emails', [
            'raw' => self::RAW,
            'from' => 'amina@example.co.tz',
            'to' => 'info@worldchoiceperfume.com',
        ], ['X-Webhook-Token' => self::TOKEN]);

        $response->assertOk()
            ->assertJson(['ok' => true, 'stored' => true, 'id' => 7]);
    }

    public function test_it_accepts_the_raw_message_posted_as_plain_text(): void
    {
        $service = $this->fakeService(null, null, false);
        $service->shouldReceive('storeRawEmail')
            ->once()
            ->with(Mockery::on(fn ($raw) => str_starts_with($raw, 'From: Amina')), Mockery::any())
            ->andReturn(['id' => 8]);

        $response = $this->call(
            'POST',
            '/api/inbound-emails',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain', 'HTTP_X_WEBHOOK_TOKEN' => self::TOKEN],
            self::RAW
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_it_refuses_a_caller_without_the_token(): void
    {
        $this->fakeService();

        $this->postJson('/api/inbound-emails', ['raw' => self::RAW])
            ->assertUnauthorized();
    }

    public function test_it_refuses_a_caller_with_the_wrong_token(): void
    {
        $this->fakeService();

        $this->postJson('/api/inbound-emails', ['raw' => self::RAW], ['X-Webhook-Token' => 'nope'])
            ->assertUnauthorized();
    }

    public function test_it_answers_503_when_the_secret_is_missing_on_the_server(): void
    {
        config(['info_mail.inbound_webhook' => '']);
        $this->fakeService();

        $this->postJson('/api/inbound-emails', ['raw' => self::RAW], ['X-Webhook-Token' => self::TOKEN])
            ->assertStatus(503);
    }

    public function test_a_re_delivered_message_is_accepted_but_not_stored_twice(): void
    {
        $this->fakeService(null);

        $this->postJson('/api/inbound-emails', ['raw' => self::RAW], ['X-Webhook-Token' => self::TOKEN])
            ->assertOk()
            ->assertJson(['ok' => true, 'stored' => false]);
    }

    public function test_a_message_without_content_is_rejected(): void
    {
        $this->fakeService();

        $this->postJson('/api/inbound-emails', ['from' => 'amina@example.co.tz'], ['X-Webhook-Token' => self::TOKEN])
            ->assertStatus(422);
    }

    public function test_a_storage_failure_is_reported_as_500(): void
    {
        $this->fakeService(null, new \RuntimeException('Supabase is down'));

        $this->postJson('/api/inbound-emails', ['raw' => self::RAW], ['X-Webhook-Token' => self::TOKEN])
            ->assertStatus(500);
    }

    public function test_the_token_may_also_travel_in_the_query_string(): void
    {
        $this->fakeService(['id' => 9]);

        $this->postJson('/api/inbound-emails?token=' . self::TOKEN, ['raw' => self::RAW])
            ->assertOk()
            ->assertJson(['ok' => true, 'id' => 9]);
    }
}
