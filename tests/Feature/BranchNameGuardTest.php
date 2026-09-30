<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\BranchAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Refusing an exception name at the point of entry.
 *
 * Branch names decide access, so the moment a second branch is allowed to take
 * the name of an exception, its staff quietly inherit cross-branch monitoring,
 * the Head Quarters bottle block or the company mailbox. Neither create nor
 * rename may open that door.
 *
 * The rename cases below re-read the branch from Supabase, so they are faked —
 * every test drives the decision itself and none of them reaches a live project.
 * (The create case fails validation before any query, so it needs no fake.)
 */
class BranchNameGuardTest extends TestCase
{
    public function test_creating_a_branch_cannot_reuse_an_exception_name(): void
    {
        foreach (['Kinondoni branch', 'Head Quarters-Mikocheni', '  KINONDONI BRANCH  '] as $name) {
            $response = $this->actingAsSuperAdmin()
                ->from('/super-admin/branches/create')
                ->post('/super-admin/branches', ['name' => $name]);

            $response->assertRedirect('/super-admin/branches/create');
            $response->assertSessionHasErrors('name');
        }
    }

    public function test_an_ordinary_branch_cannot_be_renamed_onto_an_exception_name(): void
    {
        // Branch 11 stands in for any future branch, which the baseline rules
        // treat exactly like Dodoma.
        $this->fakeBranch(11, 'Arusha branch');

        $response = $this->actingAsSuperAdmin()
            ->from('/super-admin/branches/11/edit')
            ->put('/super-admin/branches/11', [
                'name' => 'Head Quarters-Mikocheni',
                'is_active' => '1',
            ]);

        $response->assertRedirect('/super-admin/branches/11/edit');
        $response->assertSessionHasErrors('name');
    }

    /**
     * The exception branches have to stay editable.
     *
     * Their edit form resubmits their own name on every save, so a guard that
     * refuses the name outright would lock the operator out of Kinondoni's and
     * Head Quarters' address, coordinates and active flag for good.
     */
    public function test_an_exception_branch_can_still_be_edited(): void
    {
        $this->createMirrorTable();
        $this->fakeBranch(8, 'Kinondoni branch');

        $response = $this->actingAsSuperAdmin()
            ->from('/super-admin/branches/8/edit')
            ->put('/super-admin/branches/8', [
                'name' => 'Kinondoni branch',
                'address' => 'New address',
                'is_active' => '1',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/super-admin/branches');
    }

    /** Giving up an exception is allowed — that removes privileges, not grants. */
    public function test_an_exception_branch_may_be_renamed_away(): void
    {
        $this->createMirrorTable();
        $this->fakeBranch(8, 'Kinondoni branch');

        $response = $this->actingAsSuperAdmin()
            ->from('/super-admin/branches/8/edit')
            ->put('/super-admin/branches/8', [
                'name' => 'Kinondoni West',
                'is_active' => '1',
            ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_the_guard_agrees_with_how_the_scopes_compare_names(): void
    {
        // The scopes match on a lowercased, trimmed name, so a guard that
        // compared any other way could be walked straight past.
        $this->assertTrue(BranchAccess::isExceptionName('  kinondoni branch  '));
        $this->assertTrue(BranchAccess::isExceptionName('HEAD QUARTERS-MIKOCHENI'));

        // A substring of an exception is not the exception. Dodoma shares no
        // name with either and is on the site today.
        $this->assertFalse(BranchAccess::isExceptionName('Kinondoni West'));
        $this->assertFalse(BranchAccess::isExceptionName('Mikocheni'));
        $this->assertFalse(BranchAccess::isExceptionName('Dodoma branch'));
        $this->assertFalse(BranchAccess::isExceptionName('Arusha branch'));

        $this->assertFalse(BranchAccess::isExceptionName(null));
        $this->assertFalse(BranchAccess::isExceptionName('   '));
    }

    /** Stand in for the one read a rename does, plus the writes that follow it. */
    private function fakeBranch(int $id, string $name): void
    {
        Http::fake([
            '*/rest/v1/branches*' => Http::response([
                ['id' => $id, 'name' => $name, 'is_active' => true],
            ], 200),
        ]);
    }

    /**
     * The accepted renames go on to write the SQLite mirror of the branches
     * table. Nothing else in the suite uses that database — Supabase is the
     * store of record and SQLite is left over — so rather than migrate all 27
     * tables for the sake of one update statement, stand up just this one.
     */
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
