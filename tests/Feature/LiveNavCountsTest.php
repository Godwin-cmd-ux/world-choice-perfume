<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NavigationCountsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveNavCountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_signed_in_role_receives_its_counts(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'approved',
        ]);

        $this->mock(NavigationCountsService::class, function ($mock) {
            $mock->shouldReceive('forUser')->once()->andReturn([
                'orders' => 4,
                'mails' => 2,
                'notifications' => 1,
            ]);
        });

        $response = $this->actingAs($user)->get(route('live.nav-counts'));

        $response->assertOk()
            ->assertJsonPath('counts.orders', 4)
            ->assertJsonPath('counts.mails', 2)
            ->assertJsonPath('counts.notifications', 1);

        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control')
        );
    }

    public function test_a_guest_cannot_poll_counts(): void
    {
        $this->get(route('live.nav-counts'))->assertRedirect(route('login'));
    }
}
