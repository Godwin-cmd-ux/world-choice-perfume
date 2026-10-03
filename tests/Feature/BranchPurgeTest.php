<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Services\SupabaseService;
use App\Support\BranchCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Permanently deleting an inactive branch.
 *
 * This is irreversible and it cascades, so three things are pinned here: that
 * an ACTIVE branch is refused (the deactivate step cannot be skipped), that the
 * records the database would refuse to cascade are cleared first, and that the
 * staff accounts are removed too — they are the one relationship the foreign
 * key sets to NULL, so they have to be read before the branch row goes.
 */
class BranchPurgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // SupabaseService caches query results in a static that outlives a
        // single test; without clearing it a previous test's row could answer
        // this one's read and the fake would never be consulted.
        $cache = new \ReflectionProperty(SupabaseService::class, 'queryCache');
        $cache->setAccessible(true);
        $cache->setValue(null, []);

        $this->createMirrorTable();
    }

    public function test_an_active_branch_is_refused_and_nothing_is_deleted(): void
    {
        Http::fake([
            '*/rest/v1/branches*' => Http::response([
                ['id' => 81, 'name' => 'Mwanza branch', 'category' => BranchCategory::AUTONOMOUS, 'is_active' => true],
            ], 200),
        ]);

        Branch::create(['id' => 81, 'name' => 'Mwanza branch', 'is_active' => true]);

        $response = $this->actingAsSuperAdmin()->delete('/super-admin/branches/81/purge');

        $response->assertRedirect('/super-admin/branches');
        $response->assertSessionHas('error');
        $response->assertSessionMissing('success');

        Http::assertNotSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), '/rest/v1/branches'));

        $this->assertSame(1, Branch::where('name', 'Mwanza branch')->count());
    }

    public function test_an_inactive_branch_is_deleted_with_its_staff_and_records(): void
    {
        Http::fake([
            '*/rest/v1/branches*' => Http::response([
                ['id' => 82, 'name' => 'Mwanza branch', 'category' => BranchCategory::AUTONOMOUS, 'is_active' => false],
            ], 200),
            '*/rest/v1/users*' => Http::response([['id' => 501], ['id' => 502]], 200),
            '*/rest/v1/*' => Http::response([], 200),
        ]);

        Branch::create(['id' => 82, 'name' => 'Mwanza branch', 'is_active' => false]);

        $response = $this->actingAsSuperAdmin()->delete('/super-admin/branches/82/purge');

        $response->assertRedirect('/super-admin/branches');
        $response->assertSessionHas('success');

        // The local mirror row goes too.
        $this->assertSame(0, Branch::where('name', 'Mwanza branch')->count());

        // The branch row itself.
        $this->assertDeleted('/rest/v1/branches?');

        // The tables the database would not cascade for us: stock transfers
        // reference the branch ON DELETE RESTRICT, and inquiries / news_posts
        // are not declared in the project's SQL at all.
        foreach (['stock_transfers', 'inquiries', 'news_posts'] as $table) {
            $this->assertDeleted("/rest/v1/{$table}?");
        }

        // Both staff accounts, found by the branch before the branch row went.
        foreach ([501, 502] as $id) {
            Http::assertSent(fn ($request) => $request->method() === 'DELETE'
                && str_contains($request->url(), '/rest/v1/users?')
                && str_contains($request->url(), "id=eq.{$id}"));
        }
    }

    /**
     * The two branches that carry a company-wide privilege cannot be deleted.
     *
     * Their privilege is keyed on the branch NAME, so deleting either would end
     * cross-branch stock monitoring or the company mailbox with nothing to
     * reassign it to. Renaming is the supported way to give one up.
     */
    public function test_a_branch_holding_a_privilege_cannot_be_deleted(): void
    {
        foreach (['Kinondoni branch' => 83, 'Head Quarters-Mikocheni' => 84] as $name => $id) {
            Http::fake([
                '*/rest/v1/branches*' => Http::response([
                    ['id' => $id, 'name' => $name, 'category' => BranchCategory::AUTONOMOUS, 'is_active' => false],
                ], 200),
            ]);

            Branch::create(['id' => $id, 'name' => $name, 'is_active' => false]);

            $response = $this->actingAsSuperAdmin()->delete("/super-admin/branches/{$id}/purge");

            $response->assertRedirect('/super-admin/branches');
            $response->assertSessionHas('error');
            $response->assertSessionMissing('success');

            Http::assertNotSent(fn ($request) => $request->method() === 'DELETE'
                && str_contains($request->url(), '/rest/v1/branches'));

            $this->assertSame(1, Branch::where('name', $name)->count(), "{$name} must survive");
        }
    }

    public function test_a_missing_branch_is_a_404(): void
    {
        Http::fake(['*/rest/v1/*' => Http::response([], 200)]);

        $this->actingAsSuperAdmin()
            ->delete('/super-admin/branches/999/purge')
            ->assertNotFound();
    }

    private function assertDeleted(string $urlFragment): void
    {
        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), $urlFragment));
    }

    private function createMirrorTable(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 8, 6)->nullable();
            $table->decimal('longitude', 8, 6)->nullable();
            $table->string('profile_picture')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('category')->default(BranchCategory::AUTONOMOUS);
            $table->timestamps();
        });
    }

    private function actingAsSuperAdmin(): static
    {
        $user = new User;
        $user->forceFill([
            'id' => 1,
            'supabase_id' => 1,
            'name' => 'Super Admin',
            'email' => 'admin@example.co.tz',
            'role' => 'super_admin',
            'branch_id' => null,
            'status' => 'active',
        ]);

        return $this->be($user);
    }
}
