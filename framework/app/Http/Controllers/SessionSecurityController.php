<?php

namespace App\Http\Controllers;

use App\Services\AuthSessionService;
use App\Services\TrustedDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionSecurityController extends Controller
{
    public function status(
        Request $request,
        AuthSessionService $sessions,
        TrustedDeviceService $trustedDevices
    ): JsonResponse {
        if (
            $request->session()->get('logged_in') !== true ||
            !$request->session()->get('user_id')
        ) {
            return response()->json([
                'authenticated' => false,
                'redirect' => '/login',
            ], 401);
        }

        $userId =
            (int) $request->session()->get('user_id');

        $role =
            (string) $request->session()->get('role', '');

        return response()->json([
            'authenticated' => true,
            'role' => $role,
            'name' => (string) $request->session()->get('name', ''),
            'idle_seconds' => $sessions->idleSeconds($role),
            'warning_seconds' =>
                $sessions->warningSeconds($role),
            'trusted_device' =>
                $trustedDevices->currentIsTrusted(
                    $request,
                    $userId
                ),
        ]);
    }

    public function activity(
        Request $request,
        AuthSessionService $sessions
    ): JsonResponse {
        if (!$sessions->touch($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'redirect' => '/login',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'last_activity_at' =>
                now()->toDateTimeString(),
        ]);
    }

    public function forgetDevice(
        Request $request,
        TrustedDeviceService $trustedDevices
    ) {
        $userId =
            $request->session()->get('user_id');

        $cookie = $trustedDevices->revokeCurrent(
            $request,
            $userId ? (int) $userId : null
        );

        return response()->json([
            'success' => true,
            'message' =>
                'This device is no longer remembered.',
        ])->withCookie($cookie);
    }
}
