<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureStaffSessionApi;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

/**
 * Session-free staff sign-in for the mobile app.
 *
 * Validates staff credentials with the same mechanism as
 * AuthController::login — the Supabase `users` row, Hash::check, and the
 * pending/rejected/blocked status rules — but answers in JSON instead of
 * starting a web session, because /api routes are stateless by design.
 *
 * The secret code is NOT accepted here: like the website, the flow is
 * secret-code verification first (POST /api/verify-staff-access), then
 * credentials. The password hash and the secret code never leave the server,
 * and every staff dashboard keeps its own role middleware — a successful
 * response here only tells the app who the server says the user is.
 */
class StaffAuthController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService;
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $sbUser = $this->supabase->findOne('users', ['email' => $credentials['email']]);

        if (! $sbUser || empty($sbUser['password']) || ! Hash::check($credentials['password'], $sbUser['password'])) {
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }

        // Same account-status rules as the website login.
        $status = (string) ($sbUser['status'] ?? '');

        if ($status === 'pending') {
            return response()->json([
                'message' => 'Your account is pending approval. Please wait for an administrator to approve your account.',
            ], 403);
        }

        if ($status === 'rejected') {
            return response()->json([
                'message' => 'Your account has been rejected. Please contact support.',
            ], 403);
        }

        if ($status === 'blocked') {
            return response()->json([
                'message' => 'Your account has been blocked. Please contact your administrator.',
            ], 403);
        }

        $branch = null;
        if (! empty($sbUser['branch_id'])) {
            $branch = $this->supabase->find('branches', $sbUser['branch_id'], 'id,name,address');
        }

        // Stateless twin of the website's session cookie: an encrypted,
        // short-lived token carrying only the user id. Every admin API call
        // presents it as X-Staff-Session; the middleware re-reads the user
        // row and re-checks role + status server-side, so this token is a
        // pointer, never the authority.
        $sessionToken = Crypt::encryptString(json_encode([
            'scope' => 'staff_session',
            'uid' => $sbUser['id'] ?? null,
            'role' => $sbUser['role'] ?? null,
            'iat' => time(),
        ]));

        return response()->json([
            'user' => [
                'id' => $sbUser['id'] ?? null,
                'name' => $sbUser['name'] ?? null,
                'email' => $sbUser['email'] ?? null,
                'role' => $sbUser['role'] ?? null,
                'branch' => $branch ? [
                    'id' => $branch['id'] ?? null,
                    'name' => $branch['name'] ?? null,
                ] : null,
            ],
            EnsureStaffSessionApi::HEADER => $sessionToken,
        ]);
    }
}
