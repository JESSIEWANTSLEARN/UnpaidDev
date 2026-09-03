<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SystemHealthController extends Controller
{
    public function show(): JsonResponse
    {
        if (session('logged_in') !== true) {
            abort(401, 'Authentication required.');
        }

        if (session('role') !== 'super_admin') {
            abort(403, 'Super Admin access required.');
        }

        $checks = [];

        // -----------------------------------------------------
        // Application
        // -----------------------------------------------------
        // Reaching this controller successfully means Laravel
        // handled the request and the application is responding.
        $checks[] = [
            'key' => 'application',
            'label' => 'Application',
            'status' => 'HEALTHY',
            'detail' => 'Laravel application responded successfully.',
        ];

        // -----------------------------------------------------
        // Database
        // -----------------------------------------------------
        $databaseStatus = 'HEALTHY';
        $databaseDetail = 'Database connection is responding.';
        $databaseLatency = null;

        try {
            $startedAt = microtime(true);

            DB::select('SELECT 1');

            $databaseLatency = round(
                (microtime(true) - $startedAt) * 1000,
                1
            );
        } catch (\Throwable $exception) {
            report($exception);

            $databaseStatus = 'CRITICAL';
            $databaseDetail = 'Database connection failed.';
        }

        $checks[] = [
            'key' => 'database',
            'label' => 'Database',
            'status' => $databaseStatus,
            'detail' => $databaseDetail,
            'latency_ms' => $databaseLatency,
        ];

        // -----------------------------------------------------
        // Laravel storage
        // -----------------------------------------------------
        $storageHealthy =
            is_dir(storage_path('app')) &&
            is_writable(storage_path('app')) &&
            is_dir(storage_path('framework')) &&
            is_writable(storage_path('framework'));

        $checks[] = [
            'key' => 'storage',
            'label' => 'Storage',
            'status' => $storageHealthy ? 'HEALTHY' : 'DEGRADED',
            'detail' => $storageHealthy
                ? 'Required Laravel storage directories are writable.'
                : 'One or more Laravel storage directories are not writable.',
        ];

        // -----------------------------------------------------
        // Session Tracking
        // -----------------------------------------------------
        // A table existing is not enough. The actual query must
        // also succeed before this check can be HEALTHY.
        $sessionStatus = 'HEALTHY';
        $sessionDetail =
            'Session tracking table and active-session query are working.';
        $activeSessions = null;

        try {
            if (!Schema::hasTable('WBO_UserSessions')) {
                $sessionStatus = 'DEGRADED';
                $sessionDetail = 'Session tracking table is unavailable.';
            } else {
                $activeSessions = DB::table('WBO_UserSessions')
                    ->where('is_active', true)
                    ->count();
            }
        } catch (\Throwable $exception) {
            report($exception);

            $sessionStatus = 'DEGRADED';
            $sessionDetail = 'Session tracking query failed.';
            $activeSessions = null;
        }

        $checks[] = [
            'key' => 'sessions',
            'label' => 'Session Tracking',
            'status' => $sessionStatus,
            'detail' => $sessionDetail,
            'active_sessions' => $activeSessions,
        ];

        // -----------------------------------------------------
        // OTP Email / Brevo
        // -----------------------------------------------------
        // Validate real Brevo API authentication/connectivity
        // without sending a test OTP or email.
        $brevoKey = trim((string) config('services.brevo.key'));
        $mailFrom = trim((string) config('mail.from.address'));

        $otpStatus = 'DEGRADED';
        $otpDetail =
            'Brevo API key or mail sender configuration is missing.';
        $otpNote =
            'Live Brevo authentication/connectivity check; no test email is sent.';

        if ($brevoKey !== '' && $mailFrom !== '') {
            try {
                $response = Http::acceptJson()
                    ->withHeaders([
                        'api-key' => $brevoKey,
                    ])
                    ->connectTimeout(2)
                    ->timeout(4)
                    ->get('https://api.brevo.com/v3/account');

                if ($response->successful()) {
                    $otpStatus = 'HEALTHY';
                    $otpDetail =
                        'Brevo API authentication and connectivity are working, and the mail sender is configured.';
                } else {
                    $otpStatus = 'DEGRADED';
                    $otpDetail =
                        'Brevo API health check returned HTTP ' .
                        $response->status() .
                        '.';
                }
            } catch (\Throwable $exception) {
                report($exception);

                $otpStatus = 'DEGRADED';
                $otpDetail = 'Brevo API connectivity check failed.';
            }
        }

        $checks[] = [
            'key' => 'otp',
            'label' => 'OTP Email',
            'status' => $otpStatus,
            'detail' => $otpDetail,
            'note' => $otpNote,
        ];

        // -----------------------------------------------------
        // Overall
        // -----------------------------------------------------
        $healthyChecks = collect($checks)
            ->where('status', 'HEALTHY')
            ->count();

        $totalChecks = count($checks);

        $hasCritical = collect($checks)
            ->contains(
                fn ($check) => $check['status'] === 'CRITICAL'
            );

        $overallStatus = $hasCritical
            ? 'CRITICAL'
            : (
                $healthyChecks === $totalChecks
                    ? 'HEALTHY'
                    : 'DEGRADED'
            );

        return response()->json([
            'overall_status' => $overallStatus,
            'healthy_checks' => $healthyChecks,
            'total_checks' => $totalChecks,
            'checks' => $checks,
            'checked_at' => now()->toIso8601String(),
        ]);
    }
}