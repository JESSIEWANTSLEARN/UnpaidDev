<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSuperAdminSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuperAdminController extends Controller
{
    use HandlesSuperAdminSupport;

    private const ROLES = [
        'super_admin', 'Operations_Manager', 'Purchasing_Manager', 'Warehouse_Admin', 'Sales_Manager',
        'Purchasing_Staff', 'Inventory_Controller', 'Sales_Staff', 'User_Admin', 'System_User',
    ];

    public function updateProfile(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $userId = (int) session('user_id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('WBO_Users', 'email')->ignore($userId, 'user_id'),
            ],
            'contact_number' => ['nullable', 'string', 'max:20'],
        ]);

        DB::table('WBO_Users')
            ->where('user_id', $userId)
            ->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'contact_number' => $validated['contact_number'] ?: null,
            ]);

        session([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $this->audit($request, 'PROFILE_UPDATED', 'Super Admin updated their account profile.');

        return response()->json([
            'message' => 'Account settings saved successfully.',
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = DB::table('WBO_Users')
            ->select('user_id', 'password_hash')
            ->where('user_id', session('user_id'))
            ->first();

        if (!$user || !Hash::check($validated['current_password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        DB::table('WBO_Users')
            ->where('user_id', $user->user_id)
            ->update([
                'password_hash' => Hash::make($validated['password']),
            ]);

        $this->audit($request, 'PASSWORD_UPDATED', 'Super Admin changed their account password.');

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }


    public function storeUser(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('WBO_Users', 'email')],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(self::ROLES)],
            'account_status' => ['required', Rule::in(['pending_verification', 'active', 'disabled'])],
        ]);

        $userId = DB::table('WBO_Users')->insertGetId([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'contact_number' => $validated['contact_number'] ?: null,
            'password_hash' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'account_status' => $validated['account_status'],
            'email_verified_at' => $validated['account_status'] === 'active' ? now() : null,
            'created_at' => now(),
        ]);

        $this->audit(
            $request,
            'USER_ADDED',
            "Added user {$validated['email']} with role {$validated['role']}."
        );

        return response()->json([
            'message' => 'User added successfully.',
            'user_id' => $userId,
        ], 201);
    }


    public function updateCompanyInformation(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin();

        if (!Schema::hasTable('WBO_SystemSettings')) {
            return response()->json([
                'message' => 'WBO_SystemSettings is not installed. Run the provided SUPER_ADMIN_SETTINGS.sql file first.',
            ], 409);
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'company_email' => ['nullable', 'email', 'max:150'],
            'company_contact' => ['nullable', 'string', 'max:30'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $settings = $this->systemSettings();
        $logoPath = $settings['company_logo_path'] ?: 'site/Logo.png';

        if ($request->hasFile('logo')) {
            $newLogoPath = $request->file('logo')->store('site', 'public');

            if (
                $logoPath
                && $logoPath !== 'site/Logo.png'
                && str_starts_with($logoPath, 'site/')
                && Storage::disk('public')->exists($logoPath)
            ) {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = $newLogoPath;
        }

        $values = [
            'company_name' => $validated['company_name'],
            'company_email' => $validated['company_email'] ?: '',
            'company_contact' => $validated['company_contact'] ?: '',
            'company_address' => $validated['company_address'] ?: '',
            'company_logo_path' => $logoPath,
        ];

        foreach ($values as $key => $value) {
            DB::table('WBO_SystemSettings')->updateOrInsert(
                ['setting_key' => $key],
                [
                    'setting_value' => $value,
                    'updated_by' => session('user_id'),
                    'updated_at' => now(),
                ]
            );
        }

        $this->audit(
            $request,
            'COMPANY_INFORMATION_UPDATED',
            'Updated company information in system settings.'
        );

        return response()->json([
            'message' => 'Company information updated successfully.',
            'settings' => $this->systemSettings(),
        ]);
    }

    public function updateUser(Request $request, int $userId): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $user = DB::table('WBO_Users')->where('user_id', $userId)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('WBO_Users', 'email')->ignore($userId, 'user_id'),
            ],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(self::ROLES)],
            'account_status' => ['required', Rule::in(['pending_verification', 'active', 'disabled'])],
        ]);

        if ($userId === (int) session('user_id')) {
            if ($validated['role'] !== 'super_admin') {
                throw ValidationException::withMessages([
                    'role' => ['You cannot remove the super_admin role from your own signed-in account.'],
                ]);
            }

            if ($validated['account_status'] !== 'active') {
                throw ValidationException::withMessages([
                    'account_status' => ['You cannot disable or place your own signed-in Super Admin account into pending status.'],
                ]);
            }
        }

        $emailVerifiedAt = $user->email_verified_at;

        if ($validated['account_status'] === 'active' && !$emailVerifiedAt) {
            $emailVerifiedAt = now();
        } elseif ($validated['account_status'] === 'pending_verification') {
            $emailVerifiedAt = null;
        }

        DB::table('WBO_Users')
            ->where('user_id', $userId)
            ->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'contact_number' => $validated['contact_number'] ?: null,
                'role' => $validated['role'],
                'account_status' => $validated['account_status'],
                'email_verified_at' => $emailVerifiedAt,
            ]);

        if ($userId === (int) session('user_id')) {
            session([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
            ]);
        }

        $this->audit(
            $request,
            'USER_UPDATED',
            "Updated user #{$userId}: role {$validated['role']}, status {$validated['account_status']}."
        );

        return response()->json([
            'message' => 'User updated successfully.',
        ]);
    }

    public function updateNotification(Request $request, int $notificationId): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $notification = DB::table('WBO_Notifications')
            ->where('notification_id', $notificationId)
            ->first();

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found.',
            ], 404);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['UNREAD', 'ACKNOWLEDGED', 'RESOLVED'])],
        ]);

        DB::table('WBO_Notifications')
            ->where('notification_id', $notificationId)
            ->update([
                'status' => $validated['status'],
                'resolved_at' => $validated['status'] === 'RESOLVED' ? now() : null,
            ]);

        $this->audit(
            $request,
            'NOTIFICATION_UPDATED',
            "Changed notification #{$notificationId} to {$validated['status']}."
        );

        return response()->json([
            'message' => 'Notification updated successfully.',
        ]);
    }

}
