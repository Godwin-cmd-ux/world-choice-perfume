<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use App\Services\CompanySettingService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * JSON twin of SuperAdmin\SettingsController (company secret codes) plus the
 * shared Profile page operations (view, update, change password) the sidebar's
 * Account section links to. Values are only ever shown to a request that has
 * already passed the Super Admin role check in EnsureStaffSessionApi.
 */
class AdminSettingsController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function settings()
    {
        $settings = CompanySettingService::getAll();

        return response()->json([
            'super_admin_secret' => $settings['super_admin_secret'] ?? 'WCP-SUPER-2026',
            'staff_secret_code' => $settings['staff_secret_code'] ?? 'WCP-STAFF-2026',
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'super_admin_secret' => 'required|string|min:4|max:255',
            'staff_secret_code' => 'required|string|min:4|max:255',
        ]);

        CompanySettingService::set('super_admin_secret', $validated['super_admin_secret']);
        CompanySettingService::set('staff_secret_code', $validated['staff_secret_code']);

        return response()->json(['message' => 'Company secret codes updated successfully.']);
    }

    public function profile(Request $request)
    {
        $sessionUser = $request->attributes->get('staff_user');
        $uid = $sessionUser['id'] ?? null;

        $user = $this->supabase->find('users', $uid, 'id,name,email,phone,role,status,branch_id,profile_picture');
        if (! $user) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $branch = null;
        if (! empty($user['branch_id'])) {
            $branch = $this->supabase->find('branches', $user['branch_id'], 'id,name,address');
        }

        return response()->json(['user' => $user, 'branch' => $branch]);
    }

    public function updateProfile(Request $request)
    {
        $sessionUser = $request->attributes->get('staff_user');
        $uid = $sessionUser['id'] ?? null;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email',
            'profile_picture' => 'nullable|image|max:51200',
        ]);

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            if (! $file->isValid()) {
                return response()->json([
                    'message' => 'The picture could not be uploaded. Please try again.',
                    'errors' => ['profile_picture' => ['The picture could not be uploaded. Please try again.']],
                ], 422);
            }

            $uploadedUrl = (new CloudinaryService)->upload($file, 'profiles');
            if (! $uploadedUrl) {
                return response()->json([
                    'message' => 'Could not store the picture with the image service. Please try again in a moment.',
                    'errors' => ['profile_picture' => ['Could not store the picture with the image service. Please try again in a moment.']],
                ], 422);
            }
            $validated['profile_picture'] = $uploadedUrl;
        } else {
            unset($validated['profile_picture']);
        }

        $validated['updated_at'] = now()->toIso8601String();

        $this->supabase->update('users', $validated, ['id' => $uid]);

        try {
            \App\Models\User::where('email', $sessionUser['email'] ?? '')->update($validated);
        } catch (\Exception $e) {
            // Local mirror sync must not block the profile update.
        }

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $this->supabase->find('users', $uid, 'id,name,email,phone,role,status,branch_id,profile_picture'),
        ]);
    }

    public function changePassword(Request $request)
    {
        $sessionUser = $request->attributes->get('staff_user');

        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $stored = $this->supabase->find('users', $sessionUser['id'] ?? 0, 'id,email,password');
        if (! $stored || empty($stored['password']) || ! Hash::check($validated['current_password'], $stored['password'])) {
            return response()->json([
                'message' => 'Current password is incorrect.',
                'errors' => ['current_password' => ['Current password is incorrect.']],
            ], 422);
        }

        $newPassword = Hash::make($validated['password']);

        $this->supabase->update('users', [
            'password' => $newPassword,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $sessionUser['id']]);

        try {
            \App\Models\User::where('email', $stored['email'])->update(['password' => $newPassword]);
        } catch (\Exception $e) {
            // Local mirror sync must not block the password change.
        }

        return response()->json(['message' => 'Password changed successfully.']);
    }
}
