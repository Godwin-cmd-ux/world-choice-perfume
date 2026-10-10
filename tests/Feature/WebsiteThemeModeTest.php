<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The website ships dark and can switch to light, on every page.
 *
 * Two things have to hold for that to be true, and each is easy to lose by
 * accident:
 *
 *   1. the mode is on the <html> element before anything paints — a script in
 *      the head reads localStorage and writes data-theme, falling back to the
 *      dark default the layouts render server-side (so the site is still dark
 *      with JavaScript off);
 *   2. the theme stylesheet and the switch are actually included by every
 *      layout, including the standalone pages that carry their own <html>.
 *
 * The public pages are used for the rendered assertions because they are the
 * only ones a request can reach without a live Supabase project; the staff
 * layouts are pinned by reading their source, the same way
 * StorefrontBranchVisibilityTest pins the branch filter.
 */
class WebsiteThemeModeTest extends TestCase
{
    /** The layouts a visitor or a staff member actually lands on. */
    private const LAYOUTS = [
        'layouts/public.blade.php',
        'layouts/app.blade.php',
        'stock-manager/layouts/app.blade.php',
        'customer/orders/create.blade.php',
        'customer/orders/success.blade.php',
        'customer/orders/track.blade.php',
        'customer/orders/tracked.blade.php',
        'customer/navigation.blade.php',
        'errors/413.blade.php',
        'auth/register-super-admin.blade.php',
    ];

    public function test_the_dark_mode_is_rendered_before_the_first_paint(): void
    {
        $response = $this->get('/login');

        $response->assertOk();

        // server-rendered default: correct even with storage or JS unavailable
        $response->assertSee('data-theme="dark"', false);

        // and the saved choice is applied to <html> before anything paints
        $response->assertSee("localStorage.getItem(KEY)", false);
        $response->assertSee("setAttribute('data-theme', theme)", false);
    }

    public function test_the_page_carries_both_modes_and_the_switch(): void
    {
        $response = $this->get('/login');

        $response->assertSee('html[data-theme="light"]', false);
        $response->assertSee('html[data-theme="dark"]', false);

        // the toggle, and the key it shares with the boot script
        $response->assertSee('data-theme-toggle', false);
        $response->assertSee("var KEY = 'wcp-theme'", false);
    }

    public function test_every_button_is_the_brand_colour_in_both_modes(): void
    {
        $response = $this->get('/login');

        $response->assertSee('#F89A1E', false);

        $source = file_get_contents(resource_path('views/partials/theme-mode.blade.php'));
        $buttonBlock = substr($source, 0, strpos($source, '2. Mode plumbing'));

        // the button rules are keyed on the element and the utility it carries,
        // never on the mode — so a button cannot change colour with the theme
        $this->assertStringContainsString(':is(button, a):is(', $buttonBlock);
        $this->assertStringContainsString('.bg-emerald-600', $buttonBlock);
        $this->assertStringContainsString('[class*="bg-gradient-to-"]', $buttonBlock);
        $this->assertStringNotContainsString('html[data-theme', $buttonBlock);
        $this->assertStringContainsString('#F89A1E !important', $buttonBlock);
    }

    public function test_a_staff_page_renders_in_dark_mode_too(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

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

        $response = $this->be($user)->get('/super-admin/dashboard');

        $response->assertOk();
        $response->assertSee('data-theme="dark"', false);
        $response->assertSee('data-theme-toggle', false);
        $response->assertSee('html[data-theme="light"]', false);
    }

    public function test_every_layout_renders_the_theme_and_can_switch_it(): void
    {
        foreach (self::LAYOUTS as $layout) {
            $source = file_get_contents(resource_path('views/'.$layout));

            $this->assertStringContainsString('data-theme="dark"', $source, "$layout: no dark default");
            $this->assertStringContainsString("partials.theme-boot", $source, "$layout: no pre-paint boot");
            $this->assertStringContainsString("partials.theme-mode", $source, "$layout: no theme stylesheet");
        }

        foreach (['layouts/public.blade.php', 'layouts/app.blade.php', 'stock-manager/layouts/app.blade.php'] as $layout) {
            $source = file_get_contents(resource_path('views/'.$layout));

            $this->assertStringContainsString('partials.theme-toggle', $source, "$layout: no theme switch");
        }
    }
}
