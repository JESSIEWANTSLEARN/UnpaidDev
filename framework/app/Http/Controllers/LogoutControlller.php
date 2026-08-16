<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        $userId = $request->session()->get('user_id');

        if ($userId) {
            try {
                DB::table('WBO_AuditLogs')->insert([
                    'user_id' => $userId,
                    'action' => 'LOGOUT',
                    'description' => 'User logged out manually',
                    'ip_address' => $request->ip(),
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'redirect' => '/login',
        ]);
    }
}
