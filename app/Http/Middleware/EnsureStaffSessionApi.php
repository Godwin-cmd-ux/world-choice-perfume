<?php

namespace App\Http\Middleware;

use App\Services\SupabaseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

/**
 * API twin of the website's authenticated session + `role:` middleware.
 *
 * The website signs a staff member in with a session cookie and every
 * /super-admin/* route then re-checks `role:super_admin`. The mobile app has
 * no cookie jar, so POST /api/staff/login issues an encrypted, short-lived
 * X-Staff-Session token carrying the user id. Every admin API request presents
 * it; this middleware decrypts it, re-reads the user row from the database
 * (so a role revoked on the server takes effect immediately — the token is
 * only a pointer, never the authority), and optionally enforces the roles
 * given as middleware parameters.
 *
 * The mobile app therefore cannot decide it is a Super Admin: it only relays
 * a token the server issued after checking the password, and the server
 * re-checks role + account status on every call.
 */
class EnsureStaffSessionApi
{
    public const HEADER = 'X-Staff-Session';

    /** Mirrors Laravel's default session lifetime (120 minutes). */
    private const MAX_AGE_SECONDS = 7200;

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $token = $request->header(self::HEADER);

        if (! is_string($token) || $token === '') {
            return $this->denySession();
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (\Throwable $e) {
            return $this->denySession();
        }

        if (! is_array($payload) || ($payload['scope'] ?? null) !== 'staff_session') {
            return $this->denySession();
        }

        $issuedAt = (int) ($payload['iat'] ?? 0);
        if ($issuedAt <= 0 || time() - $issuedAt > self::MAX_AGE_SECONDS) {
            return $this->denySession();
        }

        $uid = $payload['uid'] ?? null;
        if ($uid === null || $uid === '') {
            return $this->denySession();
        }

        $user = (new SupabaseService)->find('users', $uid, 'id,name,email,phone,role,status,branch_id,profile_picture');
        if (! $user) {
            return $this->denySession();
        }

        // Same account-status rules as the website: pending / rejected /
        // blocked accounts are signed out everywhere.
        $status = (string) ($user['status'] ?? '');
        if (in_array($status, ['pending', 'rejected', 'blocked'], true)) {
            return response()->json([
                'message' => 'Your account is no longer active. Please contact your administrator.',
                'code' => 'account_disabled',
            ], 403);
        }

        if ($roles !== [] && ! in_array((string) ($user['role'] ?? ''), $roles, true)) {
            // Name the role the endpoint actually needs — the Super Admin
            // wording stays byte-identical for the /api/admin group.
            $labels = array_map(
                static fn (string $r) => implode(' ', array_map(
                    'ucfirst',
                    explode(' ', str_replace(['_', '-'], ' ', $r))
                )),
                $roles
            );

            return response()->json([
                'message' => implode(' or ', $labels).' access is required for this operation.',
                'code' => 'forbidden',
            ], 403);
        }

        $request->attributes->set('staff_user', $user);

        return $next($request);
    }

    /** The authenticated user row resolved by this middleware. */
    public static function user(Request $request): ?array
    {
        $user = $request->attributes->get('staff_user');

        return is_array($user) ? $user : null;
    }

    private function denySession(): Response
    {
        return response()->json([
            'message' => 'Your session has expired. Please sign in again.',
            'code' => 'session_expired',
        ], 401);
    }
}
