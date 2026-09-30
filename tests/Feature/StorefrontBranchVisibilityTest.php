<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Every active branch reaches the public site.
 *
 * The branch list was filtered by name in three places: the landing page, the
 * shop listing and the product detail page. Each one kept a branch invisible
 * unless its name happened to contain "kinondoni", "mikocheni" or "dodoma" —
 * so a branch added tomorrow would exist in the database, be reachable by its
 * ID, and simply not appear on the site, with no error and no clue why.
 *
 * These assertions read the source rather than the rendered page. The branch
 * list is fetched from Supabase inside the controller and the route closure, so
 * the page cannot be rendered here without a live project. What is worth
 * pinning is the thing that caused the bug: a name being used to decide
 * visibility. (Same approach, and reason, as PublicMobileLayoutTest.)
 */
class StorefrontBranchVisibilityTest extends TestCase
{
    private const WHITELIST = "str_contains(\$name, 'kinondoni')";

    public function test_the_landing_page_offers_every_active_branch(): void
    {
        $source = file_get_contents(base_path('routes/web.php'));

        $this->assertStringNotContainsString(self::WHITELIST, $source);
        $this->assertStringNotContainsString("str_contains(\$name, 'mikocheni')", $source);
        $this->assertStringNotContainsString("str_contains(\$name, 'dodoma')", $source);

        // Still scoped to live branches, so a closed branch stays closed.
        $this->assertStringContainsString("'is_active' => 'eq.true'", $source);
    }

    public function test_the_shop_offers_every_active_branch(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Customer/ProductController.php'));

        $this->assertStringNotContainsString(self::WHITELIST, $source);
        $this->assertStringNotContainsString("str_contains(\$name, 'mikocheni')", $source);
        $this->assertStringNotContainsString("str_contains(\$name, 'dodoma')", $source);
    }

    /**
     * The landing page counts its branches for the visitor ("3 Locations"), so
     * a fixed sentence naming three of them would contradict the number printed
     * beside it the moment a fourth branch is added.
     */
    public function test_the_landing_page_copy_follows_the_branch_count(): void
    {
        $source = file_get_contents(resource_path('views/home.blade.php'));

        $this->assertStringNotContainsString('With three branches', $source);
        $this->assertStringNotContainsString('Kinondoni, Mikocheni, and Dodoma', $source);
        $this->assertStringContainsString('$branches->count()', $source);
    }
}
