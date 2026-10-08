<?php

namespace App\Support;

/**
 * Where each staff role lands.
 *
 * One map, used by the post-login redirect (AuthController::redirectByRole)
 * and by the public navbar. The navbar used to know only four of the seven
 * roles: a signed-in customer care, seller or graphic designer was offered a
 * "Dashboard" link pointing at `route('login')` — which the guest middleware
 * sent to the landing page — or, on desktop, no link at all, while the Staff
 * Login button stayed hidden behind the same @auth branch. Both complaints
 * ("it puts me on the landing page instead of a dashboard", "the Staff Login
 * button is gone") came from this list being short and from /login being
 * wrapped in `guest`.
 */
class StaffDashboard
{
    /**
     * role => named route, mirroring AuthController::redirectByRole and the
     * route prefixes in routes/web.php.
     *
     * @var array<string, string>
     */
    private const ROUTES = [
        'super_admin' => 'super-admin.dashboard',
        'branch_admin' => 'branch-admin.dashboard',
        'cashier' => 'cashier.dashboard',
        'stock_manager' => 'stock-manager.dashboard',
        'customer_care' => 'customer-care.dashboard',
        'seller' => 'seller.dashboard',
        'graphic_designer' => 'graphic-designer.news.index',
    ];

    /**
     * The named route for a role, or null when the role has no module page.
     */
    public static function routeName(?string $role): ?string
    {
        return self::ROUTES[$role ?? ''] ?? null;
    }

    /**
     * A route every signed-in staff member can open: their own module page
     * when the role has one, otherwise the profile page every role shares
     * (`profile.edit` is behind `auth` only, no role gate).
     *
     * Never `login` — that is the loop the guest middleware used to close.
     */
    public static function fallbackRoute(?string $role): string
    {
        return self::routeName($role) ?? 'profile.edit';
    }
}
