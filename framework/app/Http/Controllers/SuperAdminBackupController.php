<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSuperAdminSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SuperAdminBackupController extends Controller
{
    use HandlesSuperAdminSupport;

    // =========================================================
    // CREATE BACKUP
    // =========================================================

    public function createBackup(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $filename = $this->writeBackup('manual');

        $this->audit(
            $request,
            'BACKUP_CREATED',
            "Created database backup {$filename}."
        );

        return response()->json([
            'message' => 'Backup created successfully.',
            'filename' => $filename,
        ], 201);
    }


    // =========================================================
    // DOWNLOAD BACKUP
    // =========================================================

    public function downloadBackup(
        Request $request,
        string $filename
    ): BinaryFileResponse|JsonResponse {
        $this->authorizeSuperAdmin();

        $path = $this->backupPath($filename);

        if (
            !$path ||
            !Storage::disk('local')->exists($path)
        ) {
            return response()->json([
                'message' => 'Backup file not found.',
            ], 404);
        }

        $this->audit(
            $request,
            'BACKUP_DOWNLOADED',
            "Downloaded database backup {$filename}."
        );

        $fullPath = Storage::disk('local')->path($path);

        return response()->download(
            $fullPath,
            $filename,
            [
                'Content-Type' => 'application/json',
            ]
        );
    }


    // =========================================================
    // RESTORE BACKUP
    // =========================================================

    public function restoreBackup(
        Request $request,
        string $filename
    ): JsonResponse {
        $this->authorizeSuperAdmin();

        $request->validate([
            'confirmation' => [
                'required',
                Rule::in(['RESTORE']),
            ],
        ]);

        $path = $this->backupPath($filename);

        if (
            !$path ||
            !Storage::disk('local')->exists($path)
        ) {
            return response()->json([
                'message' => 'Backup file not found.',
            ], 404);
        }


        // =====================================================
        // READ BACKUP FILE
        // =====================================================

        $backupContents = Storage::disk('local')->get($path);

        $payload = json_decode(
            $backupContents,
            true
        );


        // =====================================================
        // VALIDATE BACKUP FORMAT
        // =====================================================

        if (
            !is_array($payload) ||
            ($payload['schema_version'] ?? null) !== 1 ||
            !isset($payload['tables']) ||
            !is_array($payload['tables'])
        ) {
            return response()->json([
                'message' =>
                    'The selected backup file is invalid or uses an unsupported format.',
            ], 422);
        }


        // =====================================================
        // PROTECT CURRENT SUPER ADMIN
        // =====================================================

        $backupUsers = collect(
            $payload['tables']['WBO_Users'] ?? []
        );

        $sessionUserId = (int) session('user_id');

        $backupCurrentUser = $backupUsers->first(
            fn ($row) =>
                (int) ($row['user_id'] ?? 0)
                === $sessionUserId
        );

        if (
            !$backupCurrentUser ||
            ($backupCurrentUser['role'] ?? null)
                !== 'super_admin' ||
            ($backupCurrentUser['account_status'] ?? null)
                !== 'active'
        ) {
            return response()->json([
                'message' =>
                    'Restore blocked because this backup would remove or disable the currently signed-in Super Admin account.',
            ], 422);
        }


        // =====================================================
        // CREATE SAFETY BACKUP BEFORE RESTORE
        // =====================================================

        $safetyBackup = $this->writeBackup(
            'pre-restore'
        );


        // =====================================================
        // RESTORE DATABASE
        // =====================================================

        DB::beginTransaction();

        try {

            DB::statement(
                'SET FOREIGN_KEY_CHECKS=0'
            );


            // -------------------------------------------------
            // DELETE CURRENT TABLE DATA
            // -------------------------------------------------

            foreach (
                array_reverse($this->backupTables())
                as $table
            ) {
                if (
                    Schema::hasTable($table) &&
                    array_key_exists(
                        $table,
                        $payload['tables']
                    )
                ) {
                    DB::table($table)->delete();
                }
            }


            // -------------------------------------------------
            // RESTORE BACKUP DATA
            // -------------------------------------------------

            foreach (
                $this->backupTables()
                as $table
            ) {
                if (
                    !Schema::hasTable($table) ||
                    !array_key_exists(
                        $table,
                        $payload['tables']
                    )
                ) {
                    continue;
                }

                $rows =
                    $payload['tables'][$table];

                if (
                    !is_array($rows) ||
                    count($rows) === 0
                ) {
                    continue;
                }

                foreach (
                    array_chunk($rows, 250)
                    as $chunk
                ) {
                    DB::table($table)
                        ->insert($chunk);
                }
            }


            DB::statement(
                'SET FOREIGN_KEY_CHECKS=1'
            );

            DB::commit();

        } catch (\Throwable $exception) {

            try {
                DB::statement(
                    'SET FOREIGN_KEY_CHECKS=1'
                );
            } catch (\Throwable) {
                // Ignore secondary FK reset error.
            }

            DB::rollBack();

            throw $exception;
        }


        // =====================================================
        // REFRESH CURRENT USER SESSION
        // =====================================================

        $restoredUser = DB::table('WBO_Users')
            ->where(
                'user_id',
                $sessionUserId
            )
            ->first();

        if (!$restoredUser) {
            return response()->json([
                'message' =>
                    'Backup restored, but the current Super Admin account could not be reloaded.',
            ], 500);
        }

        session([
            'name' => $restoredUser->name,
            'email' => $restoredUser->email,
            'role' => $restoredUser->role,
        ]);


        // =====================================================
        // AUDIT RESTORE
        // =====================================================

        $this->audit(
            $request,
            'BACKUP_RESTORED',
            "Restored {$filename}. Automatic safety backup: {$safetyBackup}."
        );


        // =====================================================
        // RESPONSE
        // =====================================================

        return response()->json([
            'message' =>
                'Backup restored successfully.',
            'safety_backup' =>
                $safetyBackup,
        ]);
    }
}