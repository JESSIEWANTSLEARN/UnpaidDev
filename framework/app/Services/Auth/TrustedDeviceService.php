<?php

namespace App\Services\Auth;

use App\Models\WBOUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Cookie;

class TrustedDeviceService
{
    public function __construct(
        private DeviceInfoService $deviceInfo
    ) {
    }

    public function validForUser(
        Request $request,
        WBOUser $user
    ): bool {
        $rawToken = $request->cookie($this->cookieName());

        if (!is_string($rawToken) || $rawToken === '') {
            return false;
        }

        $device = DB::table('WBO_TrustedDevices')
            ->where(
                'token_hash',
                hash('sha256', $rawToken)
            )
            ->where(
                'user_id',
                (int) $user->user_id
            )
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$device) {
            return false;
        }

        // Do not bind to IP because mobile/home IP addresses can change.
        // User-agent matching adds a basic stolen-cookie safeguard.
        if (
            !$this->deviceInfo->sameUserAgent(
                $device->user_agent,
                $request
            )
        ) {
            return false;
        }

        DB::table('WBO_TrustedDevices')
            ->where(
                'trusted_device_id',
                $device->trusted_device_id
            )
            ->update([
                'last_used_at' => now(),
                'last_ip_address' => $request->ip(),
            ]);

        return true;
    }

    public function issueCookie(
        Request $request,
        WBOUser $user
    ): Cookie {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $info = $this->deviceInfo->fromRequest($request);
        $expiresAt = now()->addDays($this->days());

        DB::table('WBO_TrustedDevices')->insert([
            'user_id' => (int) $user->user_id,
            'token_hash' => $tokenHash,
            'device_name' => $info['device_name'],
            'browser_name' => $info['browser_name'],
            'operating_system' =>
                $info['operating_system'],
            'user_agent' => $info['user_agent'],
            'last_ip_address' => $info['ip_address'],
            'last_used_at' => now(),
            'expires_at' => $expiresAt,
            'revoked_at' => null,
            'created_at' => now(),
        ]);

        $this->audit(
            $request,
            (int) $user->user_id,
            'TRUSTED_DEVICE_CREATED',
            'User trusted the current device for future OTP verification.'
        );

        return cookie(
            $this->cookieName(),
            $rawToken,
            $this->days() * 24 * 60,
            '/',
            null,
            $this->secureCookie($request),
            true,
            false,
            (string) config(
                'auth_security.trusted_device.same_site',
                'lax'
            )
        );
    }

    public function revokeCurrent(
        Request $request,
        ?int $userId = null
    ): Cookie {
        $rawToken = $request->cookie($this->cookieName());

        if (is_string($rawToken) && $rawToken !== '') {
            $query = DB::table('WBO_TrustedDevices')
                ->where(
                    'token_hash',
                    hash('sha256', $rawToken)
                )
                ->whereNull('revoked_at');

            if ($userId) {
                $query->where('user_id', $userId);
            }

            $device = $query->first();

            if ($device) {
                DB::table('WBO_TrustedDevices')
                    ->where(
                        'trusted_device_id',
                        $device->trusted_device_id
                    )
                    ->update([
                        'revoked_at' => now(),
                    ]);

                $this->audit(
                    $request,
                    (int) $device->user_id,
                    'TRUSTED_DEVICE_REVOKED',
                    'User revoked the current trusted device.'
                );
            }
        }

        return cookie()->forget(
            $this->cookieName(),
            '/'
        );
    }

    public function currentIsTrusted(
        Request $request,
        int $userId
    ): bool {
        $rawToken = $request->cookie($this->cookieName());

        if (!is_string($rawToken) || $rawToken === '') {
            return false;
        }

        return DB::table('WBO_TrustedDevices')
            ->where(
                'token_hash',
                hash('sha256', $rawToken)
            )
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists();
    }

    private function cookieName(): string
    {
        return (string) config(
            'auth_security.trusted_device.cookie_name',
            'wbo_trusted_device'
        );
    }

    private function days(): int
    {
        return max(
            1,
            (int) config(
                'auth_security.trusted_device.days',
                30
            )
        );
    }

    private function secureCookie(Request $request): bool
    {
        $configured = config(
            'auth_security.trusted_device.secure_cookie'
        );

        if ($configured === null || $configured === '') {
            return $request->isSecure();
        }

        return filter_var(
            $configured,
            FILTER_VALIDATE_BOOL
        );
    }

    private function audit(
        Request $request,
        int $userId,
        string $action,
        string $description
    ): void {
        try {
            DB::table('WBO_AuditLogs')->insert([
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'ip_address' => $request->ip(),
                'user_agent' =>
                    mb_substr(
                        (string) $request->userAgent(),
                        0,
                        500
                    ),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}