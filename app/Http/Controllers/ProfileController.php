<?php

namespace App\Http\Controllers;

use App\Services\CloudinaryService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function edit()
    {
        $user = auth()->user();

        // Get branch info from Supabase if user has a branch
        $branch = null;
        if ($user->branch_id) {
            $branch = $this->supabase->findOne('branches', ['id' => $user->branch_id]);
            if ($branch) $branch = (object) $branch;
        }

        // Use role-specific profile view where available
        $view = match($user->role) {
            'stock_manager' => 'stock-manager.profile',
            default => 'profile.edit',
        };

        return view($view, ['user' => $user, 'branch' => $branch]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email',
            'profile_picture' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');

            // Surface the most common production failure (PHP post_max_size
            // exceeded — the file never reaches the validation step) clearly.
            if (!$file->isValid()) {
                $code = $file->getError();
                $message = $code === UPLOAD_ERR_INI_SIZE
                    ? 'The picture is too large. Please choose an image under 4MB.'
                    : 'The picture could not be uploaded (error code ' . $code . '). Please try again.';
                return back()->withErrors(['profile_picture' => $message])->withInput();
            }

            $cloudinary = new CloudinaryService();
            $uploadedUrl = $cloudinary->upload($file, 'profiles');
            if ($uploadedUrl) {
                $validated['profile_picture'] = $uploadedUrl;
            } else {
                // Don't fail silently — tell the user the image service failed
                // so they know their photo was not saved.
                return back()->withErrors([
                    'profile_picture' => 'Could not store the picture with the image service. Please try again in a moment.',
                ])->withInput();
            }
        } else {
            unset($validated['profile_picture']);
        }

        $validated['updated_at'] = now()->toIso8601String();

        // Update in Supabase
        $this->supabase->update('users', $validated, ['email' => $user->email]);

        // Update local SQLite
        $user->update($validated);

        // Refresh auth session so sidebar shows updated photo immediately
        auth()->login($user->fresh());

        return back()->with('success', 'Profile updated successfully.');
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $newPassword = Hash::make($validated['password']);

        // Update in Supabase
        $this->supabase->update('users', [
            'password' => $newPassword,
            'updated_at' => now()->toIso8601String(),
        ], ['email' => auth()->user()->email]);

        // Update local SQLite
        auth()->user()->update(['password' => $newPassword]);

        return back()->with('success', 'Password changed successfully.');
    }

    public function updateBranchLocation(Request $request)
    {
        $user = auth()->user();

        if (!$user->isBranchAdmin() && !$user->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        // Update in Supabase
        $this->supabase->update('branches', [
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $user->branch_id]);

        // Update in SQLite
        \App\Models\Branch::where('id', $user->branch_id)->update([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        return back()->with('success', 'Branch location updated.');
    }
}
