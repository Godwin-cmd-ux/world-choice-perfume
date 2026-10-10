<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseService;
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

    /**
     * The public navigation renders the switch twice — desktop bar and mobile
     * menu — but the script that wires it is emitted once, at the first of the
     * two. A listener bound per button at that moment never reaches the phone's
     * switch, which sits further down the document, so the phone's tap did
     * nothing. The script therefore delegates from the document.
     */
    public function test_the_phone_switch_is_wired_by_the_same_script_as_the_desktop_one(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $cache = new \ReflectionProperty(SupabaseService::class, 'queryCache');
        $cache->setAccessible(true);
        $cache->setValue(null, []);

        $response = $this->get('/');

        $response->assertOk();

        $html = $response->getContent();

        // desktop bar + the switch inside the collapsed mobile menu
        $this->assertSame(
            2,
            substr_count($html, '<button type="button" data-theme-toggle'),
            'the mobile menu lost its theme switch'
        );

        // and both are behind one delegated listener, so the one rendered after
        // the script still responds
        $this->assertSame(1, substr_count($html, "closest('[data-theme-toggle]')"));
        $this->assertStringNotContainsString('buttons.forEach', $html, 'switches are bound one by one again');
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

    /**
     * "Find Your Signature Scent" was unreadable in light mode.
     *
     * The band is built with a Tailwind gradient, which is painted with
     * background-image — so the light-mode background-color rules could not
     * reach it. The band stayed #0d0d0d to #1a1a1a while the rules flipped the
     * ink on it to #111111 and #52525B: dark on dark. Light mode now restates
     * the band, and this test reads both the restated band and the ink out of
     * the stylesheet and checks the contrast the browser would compute, so a
     * future palette tweak cannot quietly put the section back in the dark.
     */
    public function test_the_signature_scent_band_is_readable_in_light_mode(): void
    {
        $theme = file_get_contents(resource_path('views/partials/theme-mode.blade.php'));
        $home = file_get_contents(resource_path('views/home.blade.php'));

        // the band is still on the page, and light mode answers it
        $this->assertStringContainsString('bg-gradient-to-r from-dark-900 via-dark-800 to-dark-900', $home);
        $this->assertStringContainsString(
            'html[data-theme="light"] .wcp-dark-skin [class~="from-dark-900"][class~="via-dark-800"][class~="to-dark-900"]',
            $theme,
            'the light band no longer matches the markup that uses it'
        );

        $band = $this->gradientAfter($theme, '[class~="from-dark-900"][class~="via-dark-800"][class~="to-dark-900"]');
        $stops = $this->colourStops($band);

        $this->assertNotEmpty($stops, 'the light band declares no colour stops');

        // the visible band is plus que light, and no dark stop survived in it
        foreach ($stops as $stop) {
            $this->assertGreaterThan(150, $this->average($stop), "the light band still carries a dark stop: $band");
        }

        $heading = $this->hex($this->valueAfter($theme, '.wcp-dark-skin.text-white', 'color'));
        $copy = $this->hex($this->valueAfter($theme, '.wcp-dark-skin.text-gray-400', 'color'));
        $gold = $this->colourStops($this->gradientAfter($theme, '.wcp-dark-skin.gold-text'));

        // h2 is 48px bold and the paragraph 18px, so AA asks for 3.0 and 4.5
        $this->assertGreaterThanOrEqual(3.0, $this->worstContrast($stops, [$heading]), 'the heading lost its contrast on the band');
        $this->assertGreaterThanOrEqual(3.0, $this->worstContrast($stops, $gold), 'the gold half of the heading lost its contrast on the band');
        $this->assertGreaterThanOrEqual(4.5, $this->worstContrast($stops, [$copy]), 'the copy lost its contrast on the band');
    }

    /**
     * The other bands on the public pages that carry text are restated in light
     * mode too, including the two that sit over a picture: their ink is dark in
     * light mode, so the veil's ink end has to be near-opaque for the dark text
     * to land on the veil instead of on the photograph behind it.
     */
    public function test_the_other_text_carrying_bands_are_restated_light(): void
    {
        $theme = file_get_contents(resource_path('views/partials/theme-mode.blade.php'));
        $home = file_get_contents(resource_path('views/home.blade.php'));
        $shop = file_get_contents(resource_path('views/customer/products/index.blade.php'));

        $this->assertStringContainsString('bg-gradient-to-br from-dark-800 to-dark-900', $home);
        $this->assertStringContainsString(
            'html[data-theme="light"] .wcp-dark-skin [class~="from-dark-800"][class~="to-dark-900"]',
            $theme
        );

        // card veils over photographs: home categories and the shop's video hero
        foreach ([
            '[class~="from-dark-900"][class~="via-dark-900',
            '[class~="from-dark-950',
        ] as $veil) {
            $this->assertStringContainsString($veil, $theme, "$veil: no light veil");

            $gradient = $this->gradientAfter($theme, $veil);
            preg_match('/rgba\(247, 247, 244, ([0-9.]+)\)/', $gradient, $first);

            $this->assertNotEmpty($first, "$veil: the light veil is not built from the light page colour");
            $this->assertGreaterThanOrEqual(0.95, (float) $first[1], "$veil: the ink end of the veil is not opaque enough");
        }

        $this->assertStringContainsString('bg-gradient-to-t from-dark-900 via-dark-900/60 to-transparent', $home);
        $this->assertStringContainsString('bg-gradient-to-r from-dark-950/95 via-dark-950/80 to-dark-950/55', $shop);
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

    /** The declarations after `$needle` up to the end of the rule. */
    private function ruleAfter(string $source, string $needle, string $property): string
    {
        $start = strpos($source, $needle);
        $this->assertNotFalse($start, "$needle is not in the stylesheet");
        $property .= ':';
        $start = strpos($source, $property, $start);
        $this->assertNotFalse($start, "$needle does not declare $property");

        return trim(substr($source, $start + strlen($property), strpos($source, ';', $start) - $start - strlen($property)));
    }

    private function gradientAfter(string $source, string $needle): string
    {
        $source = substr($source, strpos($source, $needle));
        $start = strpos($source, 'linear-gradient(');
        $this->assertNotFalse($start, "$needle does not restate a gradient");

        return substr($source, $start, strpos($source, ';', $start) - $start);
    }

    private function valueAfter(string $source, string $needle, string $property): string
    {
        return preg_replace('/[^#0-9A-Fa-f]/', '', self::ruleAfter($source, $needle, $property));
    }

    /** @return list<array{0: float, 1: float, 2: float}> */
    private function colourStops(string $gradient): array
    {
        preg_match_all('/#[0-9A-Fa-f]{6}|#[0-9A-Fa-f]{3}/', $gradient, $matches);

        return array_map(fn (string $hex): array => $this->hex($hex), $matches[0]);
    }

    /** @return array{0: float, 1: float, 2: float} */
    private function hex(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [(float) hexdec(substr($hex, 0, 2)), (float) hexdec(substr($hex, 2, 2)), (float) hexdec(substr($hex, 4, 2))];
    }

    private function average(array $rgb): float
    {
        return ($rgb[0] + $rgb[1] + $rgb[2]) / 3;
    }

    private function luminance(array $rgb): float
    {
        $channels = array_map(static function (float $value): float {
            $value /= 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /** The lowest WCAG contrast between any of $ink and any of $backgrounds. */
    private function worstContrast(array $backgrounds, array $ink): float
    {
        $worst = null;

        foreach ($ink as $foreground) {
            foreach ($backgrounds as $background) {
                $light = $this->luminance($foreground);
                $dark = $this->luminance($background);
                $ratio = (max($light, $dark) + 0.05) / (min($light, $dark) + 0.05);
                $worst = $worst === null ? $ratio : min($worst, $ratio);
            }
        }

        return $worst;
    }
}
