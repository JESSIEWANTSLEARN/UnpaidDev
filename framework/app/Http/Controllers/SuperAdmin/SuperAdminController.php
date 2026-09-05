<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesSuperAdminSupport;
use App\Services\Auth\PasswordHistoryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Super Admin account, user, session, notification, and account-archive operations. */
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

    public function updatePassword(
        Request $request,
        PasswordHistoryService $passwordHistory
    ): JsonResponse
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

        $passwordHistory->assertNotReused(
            (int) $user->user_id,
            $validated['password'],
            $user->password_hash
        );

        DB::transaction(function () use (
            $user,
            $validated,
            $passwordHistory
        ) {
            $passwordHistory->rememberCurrent(
                (int) $user->user_id,
                $user->password_hash
            );

            DB::table('WBO_Users')
                ->where('user_id', $user->user_id)
                ->update([
                    'password_hash' => Hash::make($validated['password']),
                ]);
        });

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
        $logoPath = $settings['company_logo'] ?? $settings['company_logo_path'] ?? 'site/Logo.png';

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
            'company_logo' => $logoPath,
        ];

        foreach ($values as $key => $value) {
            DB::table('WBO_SystemSettings')->updateOrInsert(
                ['setting_key' => $key],
                [
                    'setting_value' => $value,
                    'updated_by_user_id' => session('user_id'),
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

        if ($validated['account_status'] !== 'active') {
            $revokedSessions = $this->revokeSessionsForUser($userId);
            $revokedTrustedDevices = $this->revokeTrustedDevicesForUser($userId);

            DB::table('WBO_Users')
                ->where('user_id', $userId)
                ->update(['last_seen_at' => null]);

            $this->audit(
                $request,
                'USER_ACCESS_REVOKED',
                "Revoked {$revokedSessions} active session(s) and {$revokedTrustedDevices} trusted device(s) for disabled/pending user #{$userId}."
            );
        }

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

    public function userSessions(Request $request, int $userId): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $user = DB::table('WBO_Users')
            ->select('user_id', 'name', 'email', 'role', 'account_status')
            ->where('user_id', $userId)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        if (!Schema::hasTable('WBO_UserSessions')) {
            return response()->json([
                'user' => $user,
                'sessions' => [],
                'pagination' => [
                    'page' => 1,
                    'per_page' => 10,
                    'total' => 0,
                    'total_pages' => 1,
                ],
            ]);
        }

        $currentTrackingId = (string) session('auth_session_id', '');

        $this->housekeepTrackedSessions(
            $userId,
            $userId === (int) session('user_id')
                ? $currentTrackingId
                : null
        );

        $page = max(1, (int) $request->query('page', 1));

        $perPage = min(
            25,
            max(
                5,
                (int) $request->query(
                    'per_page',
                    (int) config(
                        'auth_security.tracked_session_page_size',
                        10
                    )
                )
            )
        );

        $baseQuery = DB::table('WBO_UserSessions')
            ->where('user_id', $userId);

        $total = (clone $baseQuery)->count();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);

        $sessions = $baseQuery
            ->orderByDesc('is_active')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('logged_in_at')
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($session) use ($currentTrackingId) {
                return [
                    'session_id' => (string) $session->session_id,
                    'device_name' => $session->device_name ?: 'Unknown Device',
                    'browser_name' => $session->browser_name ?: 'Unknown Browser',
                    'operating_system' => $session->operating_system ?: 'Unknown OS',
                    'ip_address' => $session->ip_address ?: 'Unknown',
                    'logged_in_at' => $this->sessionDate($session->logged_in_at),
                    'last_activity_at' => $this->sessionDate($session->last_activity_at),
                    'logged_out_at' => $this->sessionDate($session->logged_out_at),
                    'is_active' => (bool) $session->is_active,
                    'is_current_session' =>
                        $currentTrackingId !== ''
                        && hash_equals(
                            $currentTrackingId,
                            (string) $session->session_id
                        ),
                ];
            })
            ->values();

        return response()->json([
            'user' => $user,
            'sessions' => $sessions,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
            'timezone' => 'Asia/Manila',
        ]);
    }

    public function revokeUserSession(
        Request $request,
        int $userId,
        string $sessionId
    ): JsonResponse {
        $this->authorizeSuperAdmin();

        $user = DB::table('WBO_Users')
            ->select('user_id', 'name')
            ->where('user_id', $userId)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        if (!Schema::hasTable('WBO_UserSessions')) {
            return response()->json([
                'message' => 'Session tracking is not installed.',
            ], 409);
        }

        $tracked = DB::table('WBO_UserSessions')
            ->where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->first();

        if (!$tracked) {
            return response()->json([
                'message' => 'Session not found.',
            ], 404);
        }

        $currentUserId = (int) session('user_id');
        $currentTrackingId = (string) session('auth_session_id', '');

        if (
            $userId === $currentUserId
            && $currentTrackingId !== ''
            && hash_equals($currentTrackingId, $sessionId)
        ) {
            throw ValidationException::withMessages([
                'session' => [
                    'You cannot revoke the Super Admin session you are currently using. Use Sign Out for the current session.',
                ],
            ]);
        }

        if ((bool) $tracked->is_active) {
            DB::table('WBO_UserSessions')
                ->where('user_id', $userId)
                ->where('session_id', $sessionId)
                ->update([
                    'is_active' => false,
                    'logged_out_at' => now(),
                ]);

            $this->deleteLaravelSessions([$sessionId]);
            $this->clearPresenceWhenNoActiveSession($userId);
        }

        $this->audit(
            $request,
            'SESSION_REVOKED_BY_ADMIN',
            "Super Admin revoked a session for user #{$userId} ({$user->name})."
        );

        return response()->json([
            'message' => 'Session revoked successfully.',
        ]);
    }

    public function revokeAllUserSessions(
        Request $request,
        int $userId
    ): JsonResponse {
        $this->authorizeSuperAdmin();

        $user = DB::table('WBO_Users')
            ->select('user_id', 'name')
            ->where('user_id', $userId)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $exceptSessionId = null;

        if ($userId === (int) session('user_id')) {
            $currentTrackingId = (string) session('auth_session_id', '');
            $exceptSessionId = $currentTrackingId !== ''
                ? $currentTrackingId
                : null;
        }

        $revoked = $this->revokeSessionsForUser(
            $userId,
            $exceptSessionId
        );

        $this->clearPresenceWhenNoActiveSession($userId);

        $description = $exceptSessionId
            ? "Super Admin revoked {$revoked} other session(s) from their own account."
            : "Super Admin revoked {$revoked} session(s) for user #{$userId} ({$user->name}).";

        $this->audit(
            $request,
            'USER_SESSIONS_REVOKED',
            $description
        );

        return response()->json([
            'message' => $exceptSessionId
                ? 'Other sessions revoked successfully.'
                : 'All active sessions revoked successfully.',
            'revoked_sessions' => $revoked,
        ]);
    }

    public function deleteUser(Request $request, int $userId): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'confirmation' => ['required', Rule::in(['DELETE'])],
        ]);

        $user = DB::table('WBO_Users')
            ->where('user_id', $userId)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        if ($userId === (int) session('user_id')) {
            throw ValidationException::withMessages([
                'user' => [
                    'You cannot permanently delete the Super Admin account you are currently using.',
                ],
            ]);
        }

        if (
            $user->role === 'super_admin'
            && DB::table('WBO_Users')
                ->where('role', 'super_admin')
                ->count() <= 1
        ) {
            throw ValidationException::withMessages([
                'user' => [
                    'The last Super Admin account cannot be deleted.',
                ],
            ]);
        }

        $references = $this->userDeletionReferences($userId);

        if ($references !== []) {
            $archivedEmail =
                "deleted.{$userId}." .
                bin2hex(random_bytes(6)) .
                '@wbo.invalid';

            DB::transaction(function () use (
                $userId,
                $archivedEmail
            ) {
                $this->revokeSessionsForUser($userId);
                $this->revokeTrustedDevicesForUser($userId);

                DB::table('WBO_Users')
                    ->where('user_id', $userId)
                    ->update([
                        'email' => $archivedEmail,
                        'password_hash' =>
                            Hash::make(bin2hex(random_bytes(32))),
                        'account_status' => 'disabled',
                        'last_seen_at' => null,
                    ]);
            });

            $this->audit(
                $request,
                'USER_ARCHIVED',
                "Archived account #{$userId}, revoked access, preserved protected history, and released its previous email for a new registration."
            );

            return response()->json([
                'message' =>
                    'Account archived successfully. Historical records were preserved and the previous email can now be registered as a new account.',
                'archived' => true,
                'email_released' => true,
                'references' => $references,
            ]);
        }

        try {
            DB::transaction(function () use ($userId) {
                $this->revokeSessionsForUser($userId);
                $this->revokeTrustedDevicesForUser($userId);

                if (Schema::hasTable('WBO_UserSessions')) {
                    DB::table('WBO_UserSessions')
                        ->where('user_id', $userId)
                        ->delete();
                }

                if (Schema::hasTable('WBO_TrustedDevices')) {
                    DB::table('WBO_TrustedDevices')
                        ->where('user_id', $userId)
                        ->delete();
                }

                if (Schema::hasTable('WBO_UserProfilePhotos')) {
                    DB::table('WBO_UserProfilePhotos')
                        ->where('user_id', $userId)
                        ->delete();
                }

                if (Schema::hasTable('WBO_PasswordHistory')) {
                    DB::table('WBO_PasswordHistory')
                        ->where('user_id', $userId)
                        ->delete();
                }

                DB::table('WBO_Users')
                    ->where('user_id', $userId)
                    ->delete();
            });
        } catch (\Illuminate\Database\QueryException $exception) {
            report($exception);

            return response()->json([
                'message' =>
                    'The account is still referenced by protected system records. Disable it instead of permanently deleting it.',
            ], 409);
        }

        $this->audit(
            $request,
            'USER_DELETED',
            "Permanently deleted unused account #{$userId} ({$user->email})."
        );

        return response()->json([
            'message' => 'Unused account deleted permanently.',
        ]);
    }

    private function revokeSessionsForUser(
        int $userId,
        ?string $exceptSessionId = null
    ): int {
        if (!Schema::hasTable('WBO_UserSessions')) {
            return 0;
        }

        $query = DB::table('WBO_UserSessions')
            ->where('user_id', $userId)
            ->where('is_active', true);

        if ($exceptSessionId) {
            $query->where('session_id', '<>', $exceptSessionId);
        }

        $sessionIds = (clone $query)
            ->pluck('session_id')
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        if ($sessionIds === []) {
            return 0;
        }

        $updated = $query->update([
            'is_active' => false,
            'logged_out_at' => now(),
        ]);

        $this->deleteLaravelSessions($sessionIds);

        return (int) $updated;
    }

    private function deleteLaravelSessions(array $sessionIds): void
    {
        if (
            $sessionIds === []
            || !Schema::hasTable('sessions')
            || !Schema::hasColumn('sessions', 'id')
        ) {
            return;
        }

        DB::table('sessions')
            ->whereIn('id', $sessionIds)
            ->delete();
    }

    private function revokeTrustedDevicesForUser(int $userId): int
    {
        if (!Schema::hasTable('WBO_TrustedDevices')) {
            return 0;
        }

        return DB::table('WBO_TrustedDevices')
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
            ]);
    }

    private function clearPresenceWhenNoActiveSession(int $userId): void
    {
        if (!Schema::hasTable('WBO_UserSessions')) {
            return;
        }

        $hasActiveSession = DB::table('WBO_UserSessions')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->exists();

        if (!$hasActiveSession) {
            DB::table('WBO_Users')
                ->where('user_id', $userId)
                ->update([
                    'last_seen_at' => null,
                ]);
        }
    }

    private function housekeepTrackedSessions(
        int $userId,
        ?string $exceptSessionId = null
    ): void {
        if (!Schema::hasTable('WBO_UserSessions')) {
            return;
        }

        $staleHours = max(
            1,
            (int) config(
                'auth_security.tracked_session_stale_hours',
                24
            )
        );

        $retentionDays = max(
            7,
            (int) config(
                'auth_security.tracked_session_retention_days',
                90
            )
        );

        $staleCutoff = now()->subHours($staleHours);
        $retentionCutoff = now()->subDays($retentionDays);

        $stale = DB::table('WBO_UserSessions')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where(function ($query) use ($staleCutoff) {
                $query
                    ->whereNull('last_activity_at')
                    ->orWhere('last_activity_at', '<', $staleCutoff);
            });

        if ($exceptSessionId) {
            $stale->where(
                'session_id',
                '<>',
                $exceptSessionId
            );
        }

        $stale->update([
            'is_active' => false,
            'logged_out_at' => now(),
        ]);

        DB::table('WBO_UserSessions')
            ->where('user_id', $userId)
            ->where('is_active', false)
            ->where(function ($query) use ($retentionCutoff) {
                $query
                    ->where('logged_out_at', '<', $retentionCutoff)
                    ->orWhere(function ($nested) use ($retentionCutoff) {
                        $nested
                            ->whereNull('logged_out_at')
                            ->where(
                                'last_activity_at',
                                '<',
                                $retentionCutoff
                            );
                    });
            })
            ->delete();

        $this->clearPresenceWhenNoActiveSession($userId);
    }
    private function sessionDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse((string) $value, 'UTC')
            ->setTimezone('Asia/Manila')
            ->toIso8601String();
    }

    private function userDeletionReferences(int $userId): array
    {
        $references = [];
        $checked = [];

        $cleanupTables = [
            'WBO_UserSessions',
            'WBO_TrustedDevices',
            'WBO_UserProfilePhotos',
            'WBO_PasswordHistory',
        ];

        try {
            $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->select('TABLE_NAME', 'COLUMN_NAME')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('REFERENCED_TABLE_NAME', 'WBO_Users')
                ->where('REFERENCED_COLUMN_NAME', 'user_id')
                ->get();

            foreach ($foreignKeys as $foreignKey) {
                $table = (string) $foreignKey->TABLE_NAME;
                $column = (string) $foreignKey->COLUMN_NAME;

                if (
                    in_array($table, $cleanupTables, true)
                    || $table === 'WBO_Users'
                ) {
                    continue;
                }

                $key = "{$table}.{$column}";
                $checked[$key] = true;

                if (
                    Schema::hasTable($table)
                    && Schema::hasColumn($table, $column)
                ) {
                    $count = DB::table($table)
                        ->where($column, $userId)
                        ->count();

                    if ($count > 0) {
                        $references[] = [
                            'source' => $key,
                            'count' => (int) $count,
                        ];
                    }
                }
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        $knownReferences = [
            ['WBO_Orders', 'customer_user_id'],
            ['WBO_Transactions', 'performed_by_user_id'],
            ['WBO_PurchaseOrders', 'created_by_user_id'],
            ['WBO_PurchaseOrders', 'approved_by_user_id'],
            ['WBO_Notifications', 'recipient_user_id'],
            ['WBO_AuditLogs', 'user_id'],
            ['WBO_SystemSettings', 'updated_by_user_id'],
            ['WBO_DataImports', 'user_id'],
            ['WBO_DataImports', 'imported_by_user_id'],
            ['WBO_DataImports', 'uploaded_by_user_id'],
            ['WBO_DataImports', 'created_by_user_id'],
        ];

        foreach ($knownReferences as [$table, $column]) {
            $key = "{$table}.{$column}";

            if (isset($checked[$key])) {
                continue;
            }

            if (
                !Schema::hasTable($table)
                || !Schema::hasColumn($table, $column)
            ) {
                continue;
            }

            $count = DB::table($table)
                ->where($column, $userId)
                ->count();

            if ($count > 0) {
                $references[] = [
                    'source' => $key,
                    'count' => (int) $count,
                ];
            }
        }

        return $references;
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
                'acknowledged_at' =>
                    $validated['status'] === 'ACKNOWLEDGED'
                        ? now()
                        : (
                            $validated['status'] === 'UNREAD'
                                ? null
                                : $notification->acknowledged_at
                        ),
                'resolved_at' =>
                    $validated['status'] === 'RESOLVED'
                        ? now()
                        : null,
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
