<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Deleting a staff account from the app's Staff list.
 *
 * The app posts the same delete the website's row button does, so it has to
 * mean the same thing: the row disappears, the local mirror the sign-in flow
 * keeps goes with it, and the two accounts that must survive are refused — the
 * super admin (the only account that can restore anything) and, defensively,
 * the account doing the deleting. A delete the database rejects is reported,
 * never claimed as success.
 */
class AdminStaffDeleteApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // SupabaseService's request-level cache outlives a single test.
        SupabaseService::clearCache();
    }

    private function token(int $uid = 1): string
    {
        return Crypt::encryptString(json_encode([
            'scope' => 'staff_session',
            'uid' => $uid,
            'role' => 'super_admin',
            'iat' => time(),
        ]));
    }

    /**
     * The PostgREST answers behind the endpoints: the session's user row, the
     * row being deleted, the audit writes, and whether the delete is accepted.
     *
     * @param  array<int, array<string, mixed>>  $users
     */
    private function fake(array $users, bool $deleteSucceeds = true): void
    {
        Http::fake(function (Request $request) use ($users, $deleteSucceeds) {
            $url = urldecode($request->url());

            if (str_contains($url, '/rest/v1/users')) {
                if ($request->method() === 'DELETE') {
                    return $deleteSucceeds
                        ? Http::response('', 204)
                        : Http::response(['message' => 'violates foreign key constraint'], 500);
                }

                if (preg_match('/id=eq\.([^&]+)/', $url, $m)) {
                    return Http::response(array_values(array_filter(
                        $users,
                        fn ($user) => (string) $user['id'] === $m[1]
                    )), 200);
                }

                return Http::response($users, 200);
            }

            return Http::response([], 200);
        });
    }

    private function superAdmin(): array
    {
        return [
            'id' => 1,
            'name' => 'Super Admin',
            'email' => 'admin@example.co.tz',
            'phone' => null,
            'role' => 'super_admin',
            'status' => 'active',
            'branch_id' => null,
        ];
    }

    private function cashier(): array
    {
        return [
            'id' => 5,
            'name' => 'Amina Cashier',
            'email' => 'amina@example.co.tz',
            'phone' => '0754000111',
            'role' => 'cashier',
            'status' => 'active',
            'branch_id' => 11,
        ];
    }

    /** The local mirror row the sign-in flow keeps for that account. */
    private function mirrored(array $user): User
    {
        $mirror = new User;
        $mirror->forceFill([
            'supabase_id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => $user['status'],
            // no branch row in this test's database, and the mirror is keyed
            // by supabase_id/email — the branch plays no part in the delete
            'branch_id' => null,
            'password' => 'unused-in-this-test',
        ])->save();

        return $mirror;
    }

    public function test_deleting_removes_the_account_and_its_local_mirror(): void
    {
        $cashier = $this->cashier();
        $this->mirrored($cashier);
        $this->fake([$this->superAdmin(), $cashier]);

        $response = $this->withHeaders(['X-Staff-Session' => $this->token()])
            ->deleteJson('/api/admin/staff/5');

        $response->assertOk();
        $response->assertJsonPath('message', 'Amina Cashier has been deleted.');

        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
            && str_contains(urldecode($request->url()), '/rest/v1/users')
            && str_contains(urldecode($request->url()), 'id=eq.5'));

        // the person is gone from this app's own users table too
        $this->assertDatabaseMissing('users', ['email' => 'amina@example.co.tz']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_the_super_admin_account_cannot_be_deleted(): void
    {
        $this->fake([
            $this->superAdmin(),
            ['id' => 2] + $this->superAdmin() + ['name' => 'Second Admin', 'email' => 'admin2@example.co.tz'],
        ]);

        $response = $this->withHeaders(['X-Staff-Session' => $this->token()])
            ->deleteJson('/api/admin/staff/2');

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The super admin account cannot be deleted.');

        Http::assertNotSent(fn (Request $request) => $request->method() === 'DELETE');
    }

    public function test_a_deleted_account_that_does_not_exist_is_a_404(): void
    {
        $this->fake([$this->superAdmin()]);

        $this->withHeaders(['X-Staff-Session' => $this->token()])
            ->deleteJson('/api/admin/staff/99')
            ->assertNotFound()
            ->assertJsonPath('message', 'User not found.');
    }

    public function test_a_rejected_delete_is_reported_instead_of_claimed_as_success(): void
    {
        $cashier = $this->cashier();
        $this->mirrored($cashier);
        $this->fake([$this->superAdmin(), $cashier], deleteSucceeds: false);

        $response = $this->withHeaders(['X-Staff-Session' => $this->token()])
            ->deleteJson('/api/admin/staff/5');

        $response->assertStatus(502);
        $this->assertStringContainsString('Could not delete Amina Cashier', (string) $response->json('message'));
        $this->assertStringContainsString('violates foreign key constraint', (string) $response->json('message'));

        // the mirror stays, because the account is still there
        $this->assertDatabaseHas('users', ['email' => 'amina@example.co.tz']);
    }
}
