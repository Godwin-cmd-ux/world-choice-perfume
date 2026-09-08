<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\CompanySettingService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = CompanySettingService::getAll();

        return view('super-admin.settings', [
            'superAdminSecret' => $settings['super_admin_secret'] ?? 'WCP-SUPER-2026',
            'staffSecretCode' => $settings['staff_secret_code'] ?? 'WCP-STAFF-2026',
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'super_admin_secret' => 'required|string|min:4|max:255',
            'staff_secret_code' => 'required|string|min:4|max:255',
        ]);

        CompanySettingService::set('super_admin_secret', $validated['super_admin_secret']);
        CompanySettingService::set('staff_secret_code', $validated['staff_secret_code']);

        return back()->with('success', 'Company secret codes updated successfully.');
    }
}