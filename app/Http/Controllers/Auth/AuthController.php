<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureStaffAccess;
use App\Models\Branch;
use App\Models\User;
use App\Services\CloudinaryService;
use App\Services\CompanySettingService;
use App\Services\OtpService;
use App\Services\SupabaseService;
use App\Support\StaffDashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService;
    }

    public function showLoginForm(Request $request)
    {
        // The login page itself is hidden behind the staff secret code. Until
        // the code is verified we render only the code prompt, never the form.
        if (! EnsureStaffAccess::hasAccess($request)) {
            return view('auth.staff-code');
        }

        // Keep the session flag in step with the grant cookie, so the page
        // below (and the registration links on it) behave as they always did.
        EnsureStaffAccess::rememberSessionFlag($request);

        return view('auth.login');
    }

    public function verifyStaffAccess(Request $request)
    {
        $request->validate([
            'secret_code' => 'required|string',
        ]);

        $validCode = CompanySettingService::get('staff_secret_code', 'WCP-STAFF-2026');

        if ($request->expectsJson()) {
            // Mobile app (POST /api/verify-staff-access): same code, same
            // source of truth, but a JSON answer instead of a redirect —
            // the API route is stateless, so there is no session to flag.
            if ($request->secret_code !== $validCode) {
                return response()->json([
                    'verified' => false,
                    'message' => 'Invalid company secret code. Please contact your administrator.',
                ], 422);
            }

            // Stateless stand-in for the website's `staff_access_verified`
            // session flag: an encrypted, short-lived grant the mobile app
            // presents as X-Staff-Access on staff login/registration API
            // routes (see EnsureStaffAccessApi). The secret code itself is
            // never echoed back or stored.
            return response()->json([
                'verified' => true,
                'access_token' => Crypt::encryptString(json_encode([
                    'scope' => 'staff_access',
                    'iat' => time(),
                ])),
            ]);
        }

        if ($request->secret_code !== $validCode) {
            return $this->formError($request, ['secret_code' => 'Invalid company secret code. Please contact your administrator.']);
        }

        session(['staff_access_verified' => true]);

        // …and hand the browser an encrypted grant cookie (never the code
        // itself) so the verification survives a lost or expired session. The
        // session alone used to be the only proof, which meant a login form
        // left open past the session's lifetime silently bounced the member
        // back to this code screen with no explanation.
        Cookie::queue(Cookie::make(
            EnsureStaffAccess::GRANT_COOKIE,
            json_encode(['scope' => 'staff_access', 'iat' => time()]),
            EnsureStaffAccess::GRANT_MINUTES,
            '/',
            null,
            true,
            true,
            false,
            'Lax'
        ));

        // Carry an email the member already typed on the way here back to
        // the form, so recovering from an expired verification costs one
        // code entry instead of starting over.
        return redirect()->route('login')->withInput(['email' => $request->input('email')]);
    }

    public function login(Request $request)
    {
        // Guard against posting the login form without first entering the
        // staff secret code on /login. A grant cookie issued when the code was
        // verified also satisfies this, so a session that expired underneath
        // an open form no longer dumps the member back on the code screen.
        if (! EnsureStaffAccess::hasAccess($request)) {
            // Never answer a credential POST with a bare code page: say why,
            // and hand back the address they had already typed.
            return redirect()
                ->route('login')
                ->with('staff_access_notice', 'Your staff access verification has expired. Please re-enter the company secret code, then sign in again.')
                ->withInput(['email' => $request->input('email')]);
        }

        EnsureStaffAccess::rememberSessionFlag($request);

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Look up user in Supabase
        $sbUser = $this->supabase->findOne('users', ['email' => $credentials['email']]);

        if (! $sbUser || ! Hash::check($credentials['password'], $sbUser['password'])) {
            return back()->withErrors(['email' => 'Invalid email or password.']);
        }

        if (($sbUser['status'] ?? '') === 'pending') {
            return back()->withErrors(['email' => 'Your account is pending approval. Please wait for an administrator to approve your account.']);
        }

        if (($sbUser['status'] ?? '') === 'rejected') {
            return back()->withErrors(['email' => 'Your account has been rejected. Please contact support.']);
        }

        if (($sbUser['status'] ?? '') === 'blocked') {
            return back()->withErrors(['email' => 'Your account has been blocked. Please contact your administrator.']);
        }

        // Ensure branch exists in SQLite before creating user
        if (! empty($sbUser['branch_id']) && ! Branch::find($sbUser['branch_id'])) {
            $sbBranch = $this->supabase->find('branches', $sbUser['branch_id']);
            if ($sbBranch) {
                Branch::updateOrCreate(
                    ['id' => $sbBranch['id']],
                    ['name' => $sbBranch['name'], 'address' => $sbBranch['address'] ?? null, 'is_active' => $sbBranch['is_active'] ?? true]
                );
            }
        }

        // Ensure user exists in SQLite for Auth::login()
        $localUser = User::where('email', $credentials['email'])->first();
        if (! $localUser) {
            // Create minimal local record for auth
            $localUser = User::create([
                'name' => $sbUser['name'],
                'email' => $sbUser['email'],
                'password' => $sbUser['password'],
                'phone' => $sbUser['phone'] ?? null,
                'role' => $sbUser['role'],
                'status' => $sbUser['status'],
                'branch_id' => $sbUser['branch_id'] ?? null,
                'otp_verified' => $sbUser['otp_verified'] ?? false,
                'supabase_id' => $sbUser['id'],
            ]);
        } else {
            // Sync local user with Supabase data
            $localUser->update([
                'status' => $sbUser['status'],
                'role' => $sbUser['role'],
                'password' => $sbUser['password'],
                'branch_id' => $sbUser['branch_id'] ?? $localUser->branch_id,
                'supabase_id' => $sbUser['id'],
                'profile_picture' => $sbUser['profile_picture'] ?? $localUser->profile_picture,
            ]);
            // If branch exists in Supabase but not SQLite, sync it
            if (! empty($sbUser['branch_id']) && ! Branch::find($sbUser['branch_id'])) {
                $sbBranch = $this->supabase->find('branches', $sbUser['branch_id']);
                if ($sbBranch) {
                    Branch::updateOrCreate(
                        ['id' => $sbBranch['id']],
                        ['name' => $sbBranch['name'], 'address' => $sbBranch['address'] ?? null, 'is_active' => $sbBranch['is_active'] ?? true]
                    );
                }
            }
        }

        Auth::login($localUser);

        // If status is approved, activate
        if (($sbUser['status'] ?? '') === 'approved') {
            $this->supabase->update('users', ['status' => 'active'], ['email' => $credentials['email']]);
            $localUser->update(['status' => 'active']);
        }

        // Log audit in Supabase
        $this->supabase->insert('audit_logs', [
            'user_id' => $sbUser['id'],
            'action' => 'login',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return $this->redirectByRole($sbUser);
    }

    public function logout()
    {
        session()->forget('staff_access_verified');
        // Signing out drops the grant too, so the next sign-in still starts
        // at the secret code — exactly the behaviour the flag alone had.
        Cookie::queue(Cookie::forget(EnsureStaffAccess::GRANT_COOKIE));
        Auth::logout();

        return redirect()->route('home');
    }

    // ========================
    // REGISTRATION
    // ========================

    public function showSuperAdminRegistration()
    {
        return view('auth.register-super-admin');
    }

    public function registerSuperAdmin(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'secret_code' => 'required|string',
        ]);

        if ($validated['secret_code'] !== CompanySettingService::get('super_admin_secret', 'WCP-SUPER-2026')) {
            return $this->formError($request, ['secret_code' => 'Invalid company secret code.']);
        }

        // Check if email already exists in Supabase
        $existing = $this->supabase->findOne('users', ['email' => $validated['email']]);
        if ($existing) {
            return $this->formError($request, ['email' => 'This email is already registered.']);
        }

        $hashedPassword = Hash::make($validated['password']);

        // Create in Supabase (primary)
        $sbUser = $this->supabase->insert('users', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $hashedPassword,
            'role' => 'super_admin',
            'status' => 'active',
            'otp_verified' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // Also create/update in SQLite for Auth::login()
        $user = User::updateOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => $hashedPassword,
                'role' => 'super_admin',
                'status' => 'active',
                'otp_verified' => false,
            ]
        );

        $otpService = new OtpService;
        $otpService->generate($validated['email'], 'registration', $user->id, $validated['name']);

        if ($request->expectsJson()) {
            return response()->json([
                'otp_required' => true,
                'email' => $validated['email'],
                'type' => 'registration',
                'user_id' => $user->id,
                'message' => 'A verification code has been sent to your email.',
            ]);
        }

        return view('auth.verify-otp', [
            'email' => $validated['email'],
            'type' => 'registration',
            'user_id' => $user->id,
            'message' => 'A verification code has been sent to your email.',
        ]);
    }

    public function showBranchAdminRegistration()
    {
        $branches = collect($this->supabase->query('branches', [
            'select' => '*',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->map(fn ($b) => (object) $b);

        return view('auth.register-branch-admin', ['branches' => $branches]);
    }

    public function registerBranchAdmin(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'secret_code' => 'required|string',
            'branch_id' => 'required|integer',
        ]);

        if ($validated['secret_code'] !== CompanySettingService::get('super_admin_secret', 'WCP-SUPER-2026')) {
            return $this->formError($request, ['secret_code' => 'Invalid company secret code.']);
        }

        // Check if email already exists in Supabase
        $existing = $this->supabase->findOne('users', ['email' => $validated['email']]);
        if ($existing) {
            return $this->formError($request, ['email' => 'This email is already registered.']);
        }

        // Verify branch exists
        $branch = $this->supabase->find('branches', $validated['branch_id']);
        if (! $branch) {
            return $this->formError($request, ['branch_id' => 'Selected branch does not exist.']);
        }

        $hashedPassword = Hash::make($validated['password']);

        // Sync branch to SQLite BEFORE creating user (foreign key constraint)
        if (! Branch::find($validated['branch_id'])) {
            Branch::updateOrCreate(
                ['id' => $branch['id']],
                ['name' => $branch['name'], 'address' => $branch['address'] ?? null, 'is_active' => $branch['is_active'] ?? false]
            );
        }

        // Create user in Supabase (primary)
        $this->supabase->insert('users', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $hashedPassword,
            'role' => 'branch_admin',
            'status' => 'pending',
            'branch_id' => (int) $validated['branch_id'],
            'otp_verified' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // Also create/update in SQLite for Auth::login()
        $user = User::updateOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => $hashedPassword,
                'role' => 'branch_admin',
                'status' => 'pending',
                'branch_id' => $validated['branch_id'],
                'otp_verified' => false,
            ]
        );

        $otpService = new OtpService;
        $otpService->generate($validated['email'], 'registration', $user->id, $validated['name']);

        if ($request->expectsJson()) {
            return response()->json([
                'otp_required' => true,
                'email' => $validated['email'],
                'type' => 'registration',
                'user_id' => $user->id,
                'message' => 'A verification code has been sent to your email.',
            ]);
        }

        return view('auth.verify-otp', [
            'email' => $validated['email'],
            'type' => 'registration',
            'user_id' => $user->id,
            'message' => 'A verification code has been sent to your email.',
        ]);
    }

    public function showCashierRegistration()
    {
        $branches = collect($this->supabase->query('branches', [
            'select' => '*',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->map(fn ($b) => (object) $b);

        return view('auth.register-cashier', ['branches' => $branches]);
    }

    public function showStockManagerRegistration()
    {
        $branches = collect($this->supabase->query('branches', [
            'select' => '*',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->map(fn ($b) => (object) $b);

        return view('auth.register-stock-manager', ['branches' => $branches]);
    }

    public function showCustomerCareRegistration()
    {
        $branches = collect($this->supabase->query('branches', [
            'select' => '*',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->map(fn ($b) => (object) $b);

        return view('auth.register-customer-care', ['branches' => $branches]);
    }

    public function registerCustomerCare(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'secret_code' => 'required|string',
            'branch_id' => 'required',
        ]);

        if ($validated['secret_code'] !== CompanySettingService::get('staff_secret_code', 'WCP-STAFF-2026')) {
            return $this->formError($request, ['secret_code' => 'Invalid company secret code. Please contact your administrator.']);
        }

        // Check if email already exists in Supabase
        $existing = $this->supabase->findOne('users', ['email' => $validated['email']]);
        if ($existing) {
            return $this->formError($request, ['email' => 'This email is already registered.']);
        }

        // Verify branch exists in Supabase
        $branch = $this->supabase->find('branches', $validated['branch_id']);
        if (! $branch) {
            return $this->formError($request, ['branch_id' => 'Selected branch does not exist.']);
        }

        // Sync branch to SQLite
        if (! Branch::find($validated['branch_id'])) {
            Branch::updateOrCreate(
                ['id' => $branch['id']],
                ['name' => $branch['name'], 'address' => $branch['address'] ?? null, 'is_active' => $branch['is_active'] ?? false]
            );
        }

        $hashedPassword = Hash::make($validated['password']);

        // Create in Supabase (primary)
        $sbUser = $this->supabase->insert('users', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $hashedPassword,
            'role' => 'customer_care',
            'status' => 'pending',
            'branch_id' => (int) $validated['branch_id'],
            'otp_verified' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        if (! $sbUser || ! isset($sbUser['id'])) {
            return $this->formError($request, ['email' => 'Failed to create account. Please contact support.']);
        }

        // Also create/update in SQLite for Auth::login()
        $user = User::updateOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => $hashedPassword,
                'role' => 'customer_care',
                'status' => 'pending',
                'branch_id' => $validated['branch_id'],
                'otp_verified' => false,
                'supabase_id' => $sbUser['id'],
            ]
        );

        $otpService = new OtpService;
        $otpService->generate($validated['email'], 'registration', $sbUser['id'], $validated['name']);

        if ($request->expectsJson()) {
            return response()->json([
                'otp_required' => true,
                'email' => $validated['email'],
                'type' => 'registration',
                'user_id' => $user->id,
                'message' => 'A verification code has been sent to your email.',
            ]);
        }

        return view('auth.verify-otp', [
            'email' => $validated['email'],
            'type' => 'registration',
            'user_id' => $user->id,
            'message' => 'A verification code has been sent to your email.',
        ]);
    }

    public function showSellerRegistration()
    {
        $branches = collect($this->supabase->query('branches', [
            'select' => '*',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->map(fn ($b) => (object) $b);

        return view('auth.register-seller', ['branches' => $branches]);
    }

    public function registerSeller(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'secret_code' => 'required|string',
            'branch_id' => 'required',
        ]);

        if ($validated['secret_code'] !== CompanySettingService::get('staff_secret_code', 'WCP-STAFF-2026')) {
            return $this->formError($request, ['secret_code' => 'Invalid company secret code. Please contact your administrator.']);
        }

        // Check if email already exists in Supabase
        $existing = $this->supabase->findOne('users', ['email' => $validated['email']]);
        if ($existing) {
            return $this->formError($request, ['email' => 'This email is already registered.']);
        }

        // Verify branch exists in Supabase
        $branch = $this->supabase->find('branches', $validated['branch_id']);
        if (! $branch) {
            return $this->formError($request, ['branch_id' => 'Selected branch does not exist.']);
        }

        // Sync branch to SQLite
        if (! Branch::find($validated['branch_id'])) {
            Branch::updateOrCreate(
                ['id' => $branch['id']],
                ['name' => $branch['name'], 'address' => $branch['address'] ?? null, 'is_active' => $branch['is_active'] ?? false]
            );
        }

        $hashedPassword = Hash::make($validated['password']);

        // Create in Supabase (primary)
        $sbUser = $this->supabase->insert('users', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $hashedPassword,
            'role' => 'seller',
            'status' => 'pending',
            'branch_id' => (int) $validated['branch_id'],
            'otp_verified' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        if (! $sbUser || ! isset($sbUser['id'])) {
            return $this->formError($request, ['email' => 'Failed to create account. Please contact support.']);
        }

        // Also create/update in SQLite for Auth::login()
        $user = User::updateOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => $hashedPassword,
                'role' => 'seller',
                'status' => 'pending',
                'branch_id' => $validated['branch_id'],
                'otp_verified' => false,
                'supabase_id' => $sbUser['id'],
            ]
        );

        $otpService = new OtpService;
        $otpService->generate($validated['email'], 'registration', $sbUser['id'], $validated['name']);

        if ($request->expectsJson()) {
            return response()->json([
                'otp_required' => true,
                'email' => $validated['email'],
                'type' => 'registration',
                'user_id' => $user->id,
                'message' => 'A verification code has been sent to your email.',
            ]);
        }

        return view('auth.verify-otp', [
            'email' => $validated['email'],
            'type' => 'registration',
            'user_id' => $user->id,
            'message' => 'A verification code has been sent to your email.',
        ]);
    }

    public function showGraphicDesignerRegistration()
    {
        return view('auth.register-graphic-designer');
    }

    public function registerGraphicDesigner(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'secret_code' => 'required|string',
        ]);

        if ($validated['secret_code'] !== CompanySettingService::get('staff_secret_code', 'WCP-STAFF-2026')) {
            return $this->formError($request, ['secret_code' => 'Invalid company secret code. Please contact your administrator.']);
        }

        // Check if email already exists in Supabase
        $existing = $this->supabase->findOne('users', ['email' => $validated['email']]);
        if ($existing) {
            return $this->formError($request, ['email' => 'This email is already registered.']);
        }

        $hashedPassword = Hash::make($validated['password']);

        // Create in Supabase (primary) — branch independent, approved by super admin
        $sbUser = $this->supabase->insert('users', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $hashedPassword,
            'role' => 'graphic_designer',
            'status' => 'pending',
            'branch_id' => null,
            'otp_verified' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        if (! $sbUser || ! isset($sbUser['id'])) {
            return $this->formError($request, ['email' => 'Failed to create account. Please contact support.']);
        }

        // Also create/update in SQLite for Auth::login()
        $user = User::updateOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => $hashedPassword,
                'role' => 'graphic_designer',
                'status' => 'pending',
                'branch_id' => null,
                'otp_verified' => false,
                'supabase_id' => $sbUser['id'],
            ]
        );

        $otpService = new OtpService;
        $otpService->generate($validated['email'], 'registration', $sbUser['id'], $validated['name']);

        if ($request->expectsJson()) {
            return response()->json([
                'otp_required' => true,
                'email' => $validated['email'],
                'type' => 'registration',
                'user_id' => $user->id,
                'message' => 'A verification code has been sent to your email.',
            ]);
        }

        return view('auth.verify-otp', [
            'email' => $validated['email'],
            'type' => 'registration',
            'user_id' => $user->id,
            'message' => 'A verification code has been sent to your email.',
        ]);
    }

    public function registerStockManager(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'secret_code' => 'required|string',
            'branch_id' => 'required',
        ]);

        if ($validated['secret_code'] !== CompanySettingService::get('staff_secret_code', 'WCP-STAFF-2026')) {
            return $this->formError($request, ['secret_code' => 'Invalid company secret code. Please contact your administrator.']);
        }

        // Check if email already exists in Supabase
        $existing = $this->supabase->findOne('users', ['email' => $validated['email']]);
        if ($existing) {
            return $this->formError($request, ['email' => 'This email is already registered.']);
        }

        // Verify branch exists in Supabase
        $branch = $this->supabase->find('branches', $validated['branch_id']);
        if (! $branch) {
            return $this->formError($request, ['branch_id' => 'Selected branch does not exist.']);
        }

        // Sync branch to SQLite
        if (! Branch::find($validated['branch_id'])) {
            Branch::updateOrCreate(
                ['id' => $branch['id']],
                ['name' => $branch['name'], 'address' => $branch['address'] ?? null, 'is_active' => $branch['is_active'] ?? false]
            );
        }

        $hashedPassword = Hash::make($validated['password']);

        // Create in Supabase (primary)
        $sbUser = $this->supabase->insert('users', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $hashedPassword,
            'role' => 'stock_manager',
            'status' => 'pending',
            'branch_id' => (int) $validated['branch_id'],
            'otp_verified' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        if (! $sbUser || ! isset($sbUser['id'])) {
            return $this->formError($request, ['email' => 'Failed to create account. Please contact support.']);
        }

        // Also create/update in SQLite for Auth::login()
        $user = User::updateOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => $hashedPassword,
                'role' => 'stock_manager',
                'status' => 'pending',
                'branch_id' => $validated['branch_id'],
                'otp_verified' => false,
                'supabase_id' => $sbUser['id'],
            ]
        );

        $otpService = new OtpService;
        $otpService->generate($validated['email'], 'registration', $sbUser['id'], $validated['name']);

        if ($request->expectsJson()) {
            return response()->json([
                'otp_required' => true,
                'email' => $validated['email'],
                'type' => 'registration',
                'user_id' => $user->id,
                'message' => 'A verification code has been sent to your email.',
            ]);
        }

        return view('auth.verify-otp', [
            'email' => $validated['email'],
            'type' => 'registration',
            'user_id' => $user->id,
            'message' => 'A verification code has been sent to your email.',
        ]);
    }

    public function registerCashier(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'branch_id' => 'required',
            'profile_picture' => 'nullable|image|max:51200',
        ]);

        // Check if email already exists in Supabase
        $existing = $this->supabase->findOne('users', ['email' => $validated['email']]);
        if ($existing) {
            return $this->formError($request, ['email' => 'This email is already registered.']);
        }

        $profilePicture = null;
        if ($request->hasFile('profile_picture')) {
            $cloudinaryService = new CloudinaryService;
            $profilePicture = $cloudinaryService->upload($request->file('profile_picture'), 'profiles');
        }

        $hashedPassword = Hash::make($validated['password']);

        // Verify branch exists in Supabase
        $branch = $this->supabase->find('branches', $validated['branch_id']);
        if (! $branch) {
            return $this->formError($request, ['branch_id' => 'Selected branch does not exist.']);
        }

        // Sync branch to SQLite
        if (! Branch::find($validated['branch_id'])) {
            Branch::updateOrCreate(
                ['id' => $branch['id']],
                ['name' => $branch['name'], 'address' => $branch['address'] ?? null, 'is_active' => $branch['is_active'] ?? false]
            );
        }

        // Create in Supabase (primary)
        $this->supabase->insert('users', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $hashedPassword,
            'role' => 'cashier',
            'status' => 'pending',
            'branch_id' => (int) $validated['branch_id'],
            'profile_picture' => $profilePicture,
            'otp_verified' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // Also create/update in SQLite for Auth::login()
        $user = User::updateOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => $hashedPassword,
                'role' => 'cashier',
                'status' => 'pending',
                'branch_id' => $validated['branch_id'],
                'profile_picture' => $profilePicture,
                'otp_verified' => false,
            ]
        );

        $otpService = new OtpService;
        $otpService->generate($validated['email'], 'registration', $user->id, $validated['name']);

        if ($request->expectsJson()) {
            return response()->json([
                'otp_required' => true,
                'email' => $validated['email'],
                'type' => 'registration',
                'user_id' => $user->id,
                'message' => 'A verification code has been sent to your email.',
            ]);
        }

        return view('auth.verify-otp', [
            'email' => $validated['email'],
            'type' => 'registration',
            'user_id' => $user->id,
            'message' => 'A verification code has been sent to your email.',
        ]);
    }

    // ========================
    // OTP VERIFICATION
    // ========================

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'type' => 'required|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $otpService = new OtpService;
        if ($otpService->verify($validated['email'], $validated['otp'], $validated['type'])) {
            // Update both SQLite and Supabase
            $user = User::find($validated['user_id']);
            if ($user) {
                $user->update(['otp_verified' => true]);
            }
            $this->supabase->update('users', ['otp_verified' => true], ['email' => $validated['email']]);

            if ($user && $user->status === 'pending') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'verified' => true,
                        'pending' => true,
                        'email' => $validated['email'],
                        'message' => 'Your '.ucfirst(str_replace('_', ' ', $user->role ?? 'staff')).' account has been created successfully.',
                    ]);
                }

                return view('auth.pending-approval', ['user' => $user]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'verified' => true,
                    'pending' => false,
                    'message' => 'Email verified successfully! You can now log in.',
                ]);
            }

            return redirect()->route('login')->with('success', 'Email verified successfully! You can now log in.');
        }

        return $this->formError($request, ['otp' => 'Invalid or expired OTP code.']);
    }

    public function resendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'type' => 'required|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($validated['user_id']);
        $otpService = new OtpService;
        $otpService->generate($validated['email'], $validated['type'], $validated['user_id'], $user->name ?? 'User');

        if ($request->expectsJson()) {
            return response()->json(['message' => 'A new verification code has been sent to your email.']);
        }

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    // ========================
    // FORGOT / RESET PASSWORD
    // ========================

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordResetOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        // Check if user exists in Supabase
        $sbUser = $this->supabase->findOne('users', ['email' => $validated['email']]);
        if (! $sbUser) {
            return back()->withErrors(['email' => 'No account found with this email address.']);
        }

        // Ensure branch exists in SQLite before creating user
        if (! empty($sbUser['branch_id']) && ! Branch::find($sbUser['branch_id'])) {
            $sbBranch = $this->supabase->find('branches', $sbUser['branch_id']);
            if ($sbBranch) {
                Branch::updateOrCreate(
                    ['id' => $sbBranch['id']],
                    ['name' => $sbBranch['name'], 'address' => $sbBranch['address'] ?? null, 'is_active' => $sbBranch['is_active'] ?? true]
                );
            }
        }

        // Ensure local user exists for OTP record
        $user = User::firstOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $sbUser['name'],
                'password' => $sbUser['password'],
                'role' => $sbUser['role'],
                'status' => $sbUser['status'],
                'branch_id' => $sbUser['branch_id'] ?? null,
                'supabase_id' => $sbUser['id'] ?? null,
            ]
        );

        $otpService = new OtpService;
        $otpService->generate($validated['email'], 'password_reset', $user->id, $sbUser['name'] ?? 'User');

        return view('auth.reset-password', ['email' => $validated['email']])
            ->with('success', 'A verification code has been sent to your email.');
    }

    public function showPasswordResetForm(Request $request)
    {
        $email = $request->query('email');

        if (! $email) {
            return redirect()->route('password.forgot')->with('error', 'Invalid request. Please start over.');
        }

        return view('auth.reset-password', ['email' => $email]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $otpService = new OtpService;
        if (! $otpService->verify($validated['email'], $validated['otp'], 'password_reset')) {
            return back()->withErrors(['otp' => 'Invalid or expired verification code.'])->withInput();
        }

        $newPassword = Hash::make($validated['password']);

        // Update in both SQLite and Supabase
        User::where('email', $validated['email'])->update(['password' => $newPassword]);
        $this->supabase->update('users', ['password' => $newPassword], ['email' => $validated['email']]);

        return redirect()->route('login')->with('success', 'Your password has been reset successfully. You can now log in.');
    }

    // ========================
    // HELPERS
    // ========================

    /**
     * Validation-failure answer shared by the form controllers.
     *
     * Web requests keep the behaviour they always had — redirect back with
     * errors — while JSON requests (the mobile app) get Laravel's standard
     * 422 shape ({message, errors: {field: [...]}}) so each message can be
     * shown next to the field it belongs to.
     */
    private function formError(Request $request, array $errors)
    {
        if ($request->expectsJson()) {
            $payload = [];
            foreach ($errors as $field => $message) {
                $payload[$field] = [$message];
            }

            return response()->json([
                'message' => collect($errors)->first(),
                'errors' => $payload,
            ], 422);
        }

        return back()->withErrors($errors);
    }

    private function redirectByRole(array $sbUser)
    {
        $role = (string) ($sbUser['role'] ?? '');

        // The Supabase row decides the sign-in; the local row is what the
        // `role:` middleware actually checks. When the remote value is
        // missing or unrecognised, try the local one before giving up.
        if (! StaffDashboard::routeName($role) && auth()->check()) {
            $localRole = (string) (auth()->user()->role ?? '');
            if (StaffDashboard::routeName($localRole)) {
                $role = $localRole;
            }
        }

        // Never `login`: the guest middleware used to bounce that to the
        // landing page, so a member could sign in and end up at the front
        // door instead of their own dashboard. Unknown roles open the
        // profile page every signed-in role owns.
        return redirect()->route(StaffDashboard::fallbackRoute($role));
    }
}
