<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PasswordHistoryService
{
    // Keep five previous password hashes in addition to the current password hash.
    private const HISTORY_LIMIT = 5;

    public function assertNotReused(
        int $userId,
        string $candidatePassword,
        string $currentPasswordHash
    ): void {
        $this->assertTableReady();

        if (Hash::check($candidatePassword, $currentPasswordHash)) {
            $this->throwReuseValidation();
        }

        $previousHashes = DB::table('WBO_PasswordHistory')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('password_history_id')
            ->limit(self::HISTORY_LIMIT)
            ->pluck('password_hash');

        foreach ($previousHashes as $previousHash) {
            if (Hash::check($candidatePassword, (string) $previousHash)) {
                $this->throwReuseValidation();
            }
        }
    }

    public function rememberCurrent(
        int $userId,
        string $currentPasswordHash
    ): void {
        $this->assertTableReady();

        DB::table('WBO_PasswordHistory')->insert([
            'user_id' => $userId,
            'password_hash' => $currentPasswordHash,
            'created_at' => now(),
        ]);

        // Keep only the newest five previous hashes for this user.
        $staleIds = DB::table('WBO_PasswordHistory')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('password_history_id')
            ->pluck('password_history_id')
            ->slice(self::HISTORY_LIMIT)
            ->values()
            ->all();

        if ($staleIds !== []) {
            DB::table('WBO_PasswordHistory')
                ->whereIn('password_history_id', $staleIds)
                ->delete();
        }
    }

    private function assertTableReady(): void
    {
        if (!Schema::hasTable('WBO_PasswordHistory')) {
            throw new \RuntimeException(
                'WBO_PasswordHistory is not installed. Run the Batch 3 database migration.'
            );
        }
    }

    private function throwReuseValidation(): void
    {
        throw ValidationException::withMessages([
            'password' => [
                'You cannot reuse your current password or any of your previous 5 passwords.',
            ],
        ]);
    }
}