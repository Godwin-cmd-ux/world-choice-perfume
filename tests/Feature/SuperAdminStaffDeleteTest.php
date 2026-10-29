<?php

namespace Tests\Feature;

use App\Http\Controllers\SuperAdmin\StaffController;
use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Deleting a staff account from the super admin's staff list.
 *
 * "Delete" has to mean the account is gone, not hidden: the staff list offers
 * the action, the row in the database the app reads from is removed, and the
 * local mirror the sign-in flow keeps goes with it — otherwise the person is
 * still a row in this application's own users table.
 *
 * The two accounts that must survive are pinned too: the super admin (the only
 * account that can restore anything) and the account doing the deleting.
 */
class SuperAdminStaffDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attributes = []): User
    {
        $admin = new User;
        $admin->forceFill(array_merge([
            'id' => 1,
            'supabase_id' => 1,
            'name' => 'Super Admin',
            'email' => 'admin@example.co.tz',
            'role' => 'super_admin',
            'status' => 'active',
            'branch_id' => null,
        ], $attributes));

        return $admin;
    }

    /** A SupabaseService stand-in that answers the calls destroy() makes. */
    private function supabaseStub(?array $user, bool $deleteSucceeds = true): SupabaseService
    {
        return new class($user, $deleteSucceeds) extends SupabaseService
        {
            /** @var list<array{0: string, 1: array}> */
            public array $deleted = [];

            /** @var list<array{0: string, 1: array}> */
            public array $inserted = [];

            public function __construct(private ?array $user, private bool $deleteSucceeds)
            {
            }

            public function find(string $table, int|string $id, string $select = '*'): ?array
            {
                return $table === 'users' ? $this->user : null;
            }

            public function delete(string $table, array $conditions): bool
            {
                $this->deleted[] = [$table, $conditions];

                return $this->deleteSucceeds;
            }

            public function insert(string $table, array|object $data): ?array
            {
                $this->inserted[] = [$table, is_array($data) ? $data : (array) $data];

                return is_array($data) ? $data : (array) $data;
            }
        };
    }

    private function controllerWith(SupabaseService $supabase): StaffController
    {
        $controller = (new \ReflectionClass(StaffController::class))->newInstanceWithoutConstructor();

        $property = new \ReflectionProperty(StaffController::class, 'supabase');
        $property->setAccessible(true);
        $property->setValue($controller, $supabase);

        return $controller;
    }

    public function test_the_staff_list_offers_delete_for_staff_but_not_for_the_super_admin(): void
    {
        Http::fake([
            '*/rest/v1/users*' => Http::response([
                [
                    'id' => 5, 'name' => 'Amina Cashier', 'email' => 'amina@example.co.tz',
                    'role' => 'cashier', 'status' => 'active', 'branch' => null,
                    'profile_picture' => null, 'created_at' => '2026-01-05T08:00:00+00:00',
                ],
                [
                    'id' => 1, 'name' => 'Super Admin', 'email' => 'admin@example.co.tz',
                    'role' => 'super_admin', 'status' => 'active', 'branch' => null,
                    'profile_picture' => null, 'created_at' => '2026-01-01T08:00:00+00:00',
                ],
            ], 200),
            '*' => Http::response([], 200),
        ]);

        $response = $this->be($this->admin())->get('/super-admin/staff');

        $response->assertOk();

        // the cashier row carries the delete action, with a warning that says
        // what "permanently" means
        $response->assertSee('Delete Amina Cashier permanently?', false);
        $response->assertSee(route('super-admin.staff.destroy', 5), false);

        // the super admin row does not: there would be no one left to restore it
        $response->assertDontSee('Delete Super Admin permanently?', false);
        $this->assertSame(
            1,
            substr_count($response->getContent(), 'name="_method" value="DELETE"'),
            'the staff list should offer exactly one delete action'
        );
    }

    public function test_the_delete_action_works_through_the_route_the_list_posts_to(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/rest/v1/users')) {
                if ($request->method() === 'DELETE') {
                    return Http::response('', 204);
                }

                return Http::response([[
                    'id' => 5, 'name' => 'Amina Cashier', 'email' => 'amina@example.co.tz',
                    'role' => 'cashier', 'status' => 'active', 'profile_picture' => null,
                ]], 200);
            }

            return Http::response([], 200);
        });

        $response = $this->be($this->admin())->delete('/super-admin/staff/5');

        $response->assertRedirect(route('super-admin.staff.index'));
        $response->assertSessionHas('success', 'Amina Cashier has been deleted.');

        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), '/rest/v1/users?id=eq.5'));
    }

    public function test_deleting_a_staff_member_removes_the_row_and_the_local_mirror(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        // the mirror the sign-in flow creates for Auth::login()
        User::create([
            'name' => 'Amina Cashier',
            'email' => 'amina@example.co.tz',
            'password' => Hash::make('secret-password'),
            'role' => 'cashier',
            'status' => 'active',
            'branch_id' => null,
            'supabase_id' => 5,
        ]);

        $supabase = $this->supabaseStub([
            'id' => 5, 'name' => 'Amina Cashier', 'email' => 'amina@example.co.tz',
            'role' => 'cashier', 'status' => 'active',
        ]);

        $this->be($this->admin());
        $this->controllerWith($supabase)->destroy(5);

        $this->assertSame([['users', ['id' => 5]]], $supabase->deleted, 'the account row was not deleted');
        $this->assertSame(0, User::where('email', 'amina@example.co.tz')->count(), 'the local mirror survived the delete');
        $this->assertSame('Amina Cashier has been deleted.', session('success'));
    }

    public function test_the_super_admin_account_cannot_be_deleted(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $supabase = $this->supabaseStub([
            'id' => 9, 'name' => 'Other Super Admin', 'email' => 'other@example.co.tz',
            'role' => 'super_admin', 'status' => 'active',
        ]);

        $this->be($this->admin());
        $this->controllerWith($supabase)->destroy(9);

        $this->assertSame([], $supabase->deleted, 'a super admin account was deleted');
        $this->assertSame('The super admin account cannot be deleted.', session('error'));
        $this->assertSame(null, session('success'));
    }

    public function test_the_signed_in_account_cannot_delete_itself(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $supabase = $this->supabaseStub([
            'id' => 42, 'name' => 'Me', 'email' => 'me@example.co.tz',
            'role' => 'branch_admin', 'status' => 'active',
        ]);

        $this->be($this->admin(['id' => 42, 'supabase_id' => 42]));
        $this->controllerWith($supabase)->destroy(42);

        $this->assertSame([], $supabase->deleted);
        $this->assertSame('You cannot delete the account you are signed in with.', session('error'));
    }

    public function test_a_rejected_delete_is_reported_instead_of_claimed_as_success(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        User::create([
            'name' => 'Amina Cashier',
            'email' => 'amina@example.co.tz',
            'password' => Hash::make('secret-password'),
            'role' => 'cashier',
            'status' => 'active',
            'branch_id' => null,
            'supabase_id' => 5,
        ]);

        $supabase = $this->supabaseStub([
            'id' => 5, 'name' => 'Amina Cashier', 'email' => 'amina@example.co.tz',
            'role' => 'cashier', 'status' => 'active',
        ], deleteSucceeds: false);

        $this->be($this->admin());
        $this->controllerWith($supabase)->destroy(5);

        $this->assertSame(null, session('success'));
        $this->assertStringContainsString('Could not delete Amina Cashier', (string) session('error'));

        // and the account is still there, so the report is not a lie
        $this->assertSame(1, User::where('email', 'amina@example.co.tz')->count());
    }
}
