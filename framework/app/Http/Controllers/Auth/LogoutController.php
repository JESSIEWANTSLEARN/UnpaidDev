<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthSessionService;
use App\Services\Auth\TrustedDeviceService;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function logout(
        Request $request,
        AuthSessionService $sessions,
        TrustedDeviceService $trustedDevices
    ) {
        $forgetDevice =
            $request->boolean('forget_device');

        $userId =
            $request->session()->get('user_id');

        $cookie = null;

        if ($forgetDevice) {
            $cookie =
                $trustedDevices->revokeCurrent(
                    $request,
                    $userId ? (int) $userId : null
                );
        }

        $reason =
            (string) $request->input(
                'reason',
                'manual'
            );

        if ($reason === 'idle') {
            $sessions->invalidate(
                $request,
                'SESSION_IDLE_TIMEOUT',
                'User was automatically logged out after inactivity.'
            );
        } else {
            $sessions->invalidate(
                $request,
                'LOGOUT',
                'User logged out manually.'
            );
        }

        $response = response()->json([
            'success' => true,
            'redirect' => '/login',
        ]);

        if ($cookie) {
            $response->withCookie($cookie);
        }

        return $response;
    }
}