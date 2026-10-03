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
 * The branch category, from the form to the stored row.
 *
 * The category decides whether a branch is products-only, so it has to survive
 * the trip through validation and both writes (Supabase and the SQLite mirror).
 * It also has to default to the baseline rather than to nothing, because a
 * branch with no category would read as autonomous anyway.
 *
 * The controller builds its own Supabase client over HTTP, so the project is
 * faked; the SQLite mirror is stood up column by column, as BranchNameGuardTest
 * does, rather than migrating the whole database for two write statements.
 */
class BranchCategoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // SupabaseService keeps its query cache in a static, which outlives a
        // single test. Without this, a row fetched in an earlier test could be
        // served from cache here and the fake below would never be consulted.
        $cache = new \ReflectionProperty(SupabaseService::class, 'queryCache');
        $cache->setAccessible(true);
        $cache->setValue(null, []);

        $this->createMirrorTable();
    }

    public function test_a_branch_created_as_products_based_is_stored_as_one(): void
    {
        $this->fakeBranches(['id' => 71, 'name' => 'Mwanza branch', 'category' => BranchCategory::PRODUCTS_BASED]);

        $response = $this->actingAsSuperAdmin()->post('/super-admin/branches', [
            'name' => 'Mwanza branch',
            'category' => BranchCategory::PRODUCTS_BASED,
        ]);

        $response->assertRedirect('/super-admin/branches');
        $response->assertSessionHasNoErrors();

        $this->assertSame(
            BranchCategory::PRODUCTS_BASED,
            Branch::where('name', 'Mwanza branch')->value('category')
        );

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_contains($request->url(), '/rest/v1/branches')
            && ($request->data()['category'] ?? null) === BranchCategory::PRODUCTS_BASED);
    }

    public function test_a_branch_created_without_a_category_is_autonomous(): void
    {
        $this->fakeBranches(['id' => 72, 'name' => 'Mwanza branch', 'category' => BranchCategory::AUTONOMOUS]);

        $this->actingAsSuperAdmin()->post('/super-admin/branches', ['name' => 'Mwanza branch']);

        $this->assertSame(
            BranchCategory::AUTONOMOUS,
            Branch::where('name', 'Mwanza branch')->value('category'),
            'A branch with no category chosen must fall back to the baseline.'
        );
    }

    public function test_an_unknown_category_is_rejected(): void
    {
        $this->fakeBranches(['id' => 73, 'name' => 'Mwanza branch', 'category' => BranchCategory::AUTONOMOUS]);

        $response = $this->actingAsSuperAdmin()
            ->from('/super-admin/branches/create')
            ->post('/super-admin/branches', [
                'name' => 'Mwanza branch',
                'category' => 'products_only_typo',
            ]);

        $response->assertRedirect('/super-admin/branches/create');
        $response->assertSessionHasErrors('category');
        $this->assertSame(0, Branch::where('name', 'Mwanza branch')->count());
    }

    public function test_editing_can_move_a_branch_between_categories(): void
    {
        Branch::create(['id' => 74, 'name' => 'Arusha branch', 'category' => BranchCategory::AUTONOMOUS, 'is_active' => true]);

        Http::fake([
            '*/rest/v1/branches*' => Http::response([
                ['id' => 74, 'name' => 'Arusha branch', 'category' => BranchCategory::AUTONOMOUS, 'is_active' => true],
            ], 200),
        ]);

        $response = $this->actingAsSuperAdmin()->put('/super-admin/branches/74', [
            'name' => 'Arusha branch',
            'category' => BranchCategory::PRODUCTS_BASED,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/super-admin/branches');
        $response->assertSessionHasNoErrors();

        $this->assertSame(
            BranchCategory::PRODUCTS_BASED,
            Branch::where('name', 'Arusha branch')->value('category')
        );
    }

    public function test_an_edit_that_omits_the_category_leaves_it_alone(): void
    {
        Branch::create(['id' => 75, 'name' => 'Arusha branch', 'category' => BranchCategory::PRODUCTS_BASED, 'is_active' => true]);

        Http::fake([
            '*/rest/v1/branches*' => Http::response([
                ['id' => 75, 'name' => 'Arusha branch', 'category' => BranchCategory::PRODUCTS_BASED, 'is_active' => true],
            ], 200),
        ]);

        // A form that does not carry the field at all must not reset the branch
        // to the baseline on every save.
        $this->actingAsSuperAdmin()->put('/super-admin/branches/75', [
            'name' => 'Arusha branch',
            'address' => 'New address',
            'is_active' => '1',
        ]);

        $this->assertSame(
            BranchCategory::PRODUCTS_BASED,
            Branch::where('name', 'Arusha branch')->value('category')
        );
    }

    private function fakeBranches(array $row): void
    {
        Http::fake([
            '*/rest/v1/branches*' => Http::response([$row], 200),
        ]);
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
