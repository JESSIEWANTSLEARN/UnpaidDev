<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PresenceController extends Controller
{
    private const ONLINE_WINDOW_MINUTES = 5;

    public function heartbeat(): JsonResponse
    {
        if (session('logged_in') !== true || !session('user_id')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $updated = DB::table('WBO_Users')
            ->where('user_id', session('user_id'))
            ->update(['last_seen_at' => now()]);

        if ($updated === 0 && !DB::table('WBO_Users')->where('user_id', session('user_id'))->exists()) {
            session()->invalidate();
            return response()->json(['message' => 'User account no longer exists.'], 401);
        }

        return response()->json([
            'online' => true,
            'last_seen_at' => now()->toDateTimeString(),
        ]);
    }

    public function offline(): JsonResponse
    {
        if (session('logged_in') !== true || !session('user_id')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        DB::table('WBO_Users')
            ->where('user_id', session('user_id'))
            ->update(['last_seen_at' => null]);

        return response()->json(['online' => false]);
    }

    public function superAdminIndex(): JsonResponse
    {
        if (session('logged_in') !== true || !session('user_id')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (session('role') !== 'super_admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $cutoff = now()->subMinutes(self::ONLINE_WINDOW_MINUTES);

        $users = DB::table('WBO_Users')
            ->select('user_id', 'account_status', 'last_seen_at')
            ->orderBy('user_id')
            ->get()
            ->map(function ($user) use ($cutoff) {
                $user->is_online = $user->account_status === 'active'
                    && $user->last_seen_at !== null
                    && \Carbon\Carbon::parse($user->last_seen_at)->greaterThanOrEqualTo($cutoff);

                return $user;
            });

        return response()->json([
            'online_window_minutes' => self::ONLINE_WINDOW_MINUTES,
            'users' => $users,
        ]);
    }
}
