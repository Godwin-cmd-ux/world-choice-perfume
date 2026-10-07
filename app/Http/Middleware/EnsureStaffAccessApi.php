<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

/**
 * API twin of EnsureStaffAccess (`staff.access`).
 *
 * The website gates the login form and every registration route behind the
 * `staff_access_verified` session flag set when the secret code is entered.
 * The mobile app has no session, so /api/verify-staff-access issues an
 * encrypted, short-lived grant (X-Staff-Access) instead. Presenting it proves
 * the secret code was verified on this device recently; the code itself never
 * travels with the request. Lifetime matches Laravel's default session
 * lifetime (2 hours).
 */
class EnsureStaffAccessApi
{
    public const HEADER = 'X-Staff-Access';

    private const MAX_AGE_SECONDS = 7200;

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header(self::HEADER);

        if (! is_string($token) || $token === '') {
            return $this->deny();
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (\Throwable $e) {
            return $this->deny();
        }

        if (! is_array($payload) || ($payload['scope'] ?? null) !== 'staff_access') {
            return $this->deny();
        }

        $issuedAt = (int) ($payload['iat'] ?? 0);
        if ($issuedAt <= 0 || time() - $issuedAt > self::MAX_AGE_SECONDS) {
            return $this->deny();
        }

        return $next($request);
    }

    private function deny(): Response
    {
        return response()->json([
            'message' => 'Staff access verification is required. Please verify the secret code again.',
        ], 403);
    }
}
