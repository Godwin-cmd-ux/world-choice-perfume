<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureStaffAccess
{
    /**
     * Cookie that carries the "this browser already verified the company
     * secret code" grant. Its value is plain JSON on the way out — the
     * EncryptCookies middleware signs and encrypts it with APP_KEY, so it
     * cannot be forged and never contains the code itself.
     */
    public const GRANT_COOKIE = 'staff_access';

    /** How long a verified browser keeps that grant (12 hours = one shift). */
    public const GRANT_MINUTES = 720;

    public function handle(Request $request, Closure $next)
    {
        if (! self::hasAccess($request)) {
            return redirect()->route('home')->with('show_staff_modal', true);
        }

        self::rememberSessionFlag($request);

        return $next($request);
    }

    /**
     * Is this request allowed past the staff secret-code gate?
     *
     * Either the `staff_access_verified` session flag the website has always
     * used, or the encrypted grant cookie issued when the code was verified.
     * The cookie is what keeps a filled-in login form from being thrown back
     * to the code screen: the session alone can disappear underneath the user
     * (2-hour idle expiry, a restarted server, a restored browser tab), and
     * that used to happen silently — POST /login answered with the bare code
     * page, no message, and threw away what they had typed.
     */
    public static function hasAccess(Request $request): bool
    {
        if ($request->hasSession() && $request->session()->get('staff_access_verified')) {
            return true;
        }

        return self::grantIsValid($request);
    }

    /**
     * Re-assert the session flag from a still-valid grant cookie, so the rest
     * of the request (login page register links, registration routes) sees the
     * same state it would have seen had the session never been lost.
     */
    public static function rememberSessionFlag(Request $request): void
    {
        if ($request->hasSession()
            && ! $request->session()->get('staff_access_verified')
            && self::grantIsValid($request)) {
            $request->session()->put('staff_access_verified', true);
        }
    }

    /**
     * The grant cookie is valid when it decrypts (done by EncryptCookies),
     * carries the staff_access scope and is younger than GRANT_MINUTES.
     * Anything else — missing, tampered with, expired, or from before an
     * APP_KEY rotation — is treated as "not verified", never as an error.
     */
    public static function grantIsValid(Request $request): bool
    {
        $payload = $request->cookie(self::GRANT_COOKIE);

        if (! is_string($payload) || $payload === '') {
            return false;
        }

        $data = json_decode($payload, true);

        if (! is_array($data) || ($data['scope'] ?? null) !== 'staff_access') {
            return false;
        }

        $issuedAt = (int) ($data['iat'] ?? 0);

        return $issuedAt > 0 && (time() - $issuedAt) <= self::GRANT_MINUTES * 60;
    }
}
