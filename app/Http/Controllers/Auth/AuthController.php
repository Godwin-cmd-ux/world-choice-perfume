<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use App\Services\OtpService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function verifyStaffAccess(Request $request)
    {
        $request->validate([
            'secret_code' => 'required|string',
        ]);

        $validCode = config('app.staff_secret_code', 'WCP-STAFF-2026');

        if ($request->secret_code !== $validCode) {
            return back()->withErrors(['secret_code' => 'Invalid company secret code. Please contact your administrator.']);
        }

        session(['staff_access_verified' => true]);

        return redirect()->route('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Look up user in Supabase
        $sbUser = $this->supabase->findOne('users', ['email' => $credentials['email']]);

        if (!$sbUser || !Hash::check($credentials['password'], $sbUser['password'])) {
            return back()->withErrors(['email' => 'Invalid email or password.']);
        }

        if (($sbUser['status'] ?? '') === 'pending') {
            return back()->withErrors(['email' => 'Your account is pending approval. Please wait for an administrator to approve your account.']);
        }

        if (($sbUser['status'] ?? '') === 'rejected') {
            return back()->withErrors(['email' => 'Your account has been rejected. Please contact support.']);
        }

        // Ensure branch exists in SQLite before creating user
        if (!empty($sbUser['branch_id']) && !Branch::find($sbUser['branch_id'])) {
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
        if (!$localUser) {
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
            if (!empty($sbUser['branch_id']) && !Branch::find($sbUser['branch_id'])) {
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

        if ($validated['secret_code'] !== config('app.super_admin_secret', 'WCP-SUPER-2026')) {
            return back()->withErrors(['secret_code' => 'Invalid company secret code.']);
        }

        // Check if email already exists in Supabase
        $existing = $this->supabase->findOne('users', ['email' => $validated['email']]);
        if ($existing) {
            return back()->withErrors(['email' => 'This email is already registered.']);
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

        $otpService = new OtpService();
        $otpService->generate($validated['email'], 'registration', $user->id, $validated['name']);

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
        ]))->map(fn($b) => (object) $b);

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

        if ($validated['secret_code'] !== config('app.super_admin_secret', 'WCP-SUPER-2026')) {
            return back()->withErrors(['secret_code' => 'Invalid company secret code.']);
        }

        // Check if email already exists in Supabase
        $existing = $this->supabase->findOne('users', ['email' => $validated['email']]);
        if ($existing) {
            return back()->withErrors(['email' => 'This email is already registered.']);
        }

        // Verify branch exists
        $branch = $this->supabase->find('branches', $validated['branch_id']);
        if (!$branch) {
            return back()->withErrors(['branch_id' => 'Selected branch does not exist.']);
        }

        $hashedPassword = Hash::make($validated['password']);

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

        // Sync branch to SQLite
        if (!Branch::find($validated['branch_id'])) {
            Branch::updateOrCreate(
                ['id' => $branch['id']],
                ['name' => $branch['name'], 'address' => $branch['address'] ?? null, 'is_active' => $branch['is_active'] ?? false]
            );
        }

        $otpService = new OtpService();
        $otpService->generate($validated['email'], 'registration', $user->id, $validated['name']);

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
        ]))->map(fn($b) => (object) $b);

        return view('auth.register-cashier', ['branches' => $branches]);
    }

    public function registerCashier(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'branch_id' => 'required',
            'profile_picture' => 'nullable|image|max:2048',
        ]);

        // Check if email already exists in Supabase
        $existing = $this->supabase->findOne('users', ['email' => $validated['email']]);
        if ($existing) {
            return back()->withErrors(['email' => 'This email is already registered.']);
        }

        $profilePicture = null;
        if ($request->hasFile('profile_picture')) {
            $cloudinaryService = new \App\Services\CloudinaryService();
            $profilePicture = $cloudinaryService->upload($request->file('profile_picture'), 'profiles');
        }

        $hashedPassword = Hash::make($validated['password']);

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

        $otpService = new OtpService();
        $otpService->generate($validated['email'], 'registration', $user->id, $validated['name']);

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

        $otpService = new OtpService();
        if ($otpService->verify($validated['email'], $validated['otp'], $validated['type'])) {
            // Update both SQLite and Supabase
            $user = User::find($validated['user_id']);
            if ($user) {
                $user->update(['otp_verified' => true]);
            }
            $this->supabase->update('users', ['otp_verified' => true], ['email' => $validated['email']]);

            if ($user && $user->status === 'pending') {
                return view('auth.pending-approval', ['user' => $user]);
            }

            return redirect()->route('login')->with('success', 'Email verified successfully! You can now log in.');
        }

        return back()->withErrors(['otp' => 'Invalid or expired OTP code.']);
    }

    public function resendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'type' => 'required|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($validated['user_id']);
        $otpService = new OtpService();
        $otpService->generate($validated['email'], $validated['type'], $validated['user_id'], $user->name ?? 'User');

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
        if (!$sbUser) {
            return back()->withErrors(['email' => 'No account found with this email address.']);
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

        $otpService = new OtpService();
        $otpService->generate($validated['email'], 'password_reset', $user->id, $sbUser['name'] ?? 'User');

        return view('auth.reset-password', ['email' => $validated['email']])
            ->with('success', 'A verification code has been sent to your email.');
    }

    public function showPasswordResetForm(Request $request)
    {
        $email = $request->query('email');

        if (!$email) {
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

        $otpService = new OtpService();
        if (!$otpService->verify($validated['email'], $validated['otp'], 'password_reset')) {
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

    private function redirectByRole(array $sbUser)
    {
        return match($sbUser['role'] ?? '') {
            'super_admin' => redirect()->route('super-admin.dashboard'),
            'branch_admin' => redirect()->route('branch-admin.dashboard'),
            'cashier' => redirect()->route('cashier.dashboard'),
            default => redirect()->route('login'),
        };
    }
}
