<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnonymousLandingVisitController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if ($request->session()->get('logged_in') === true) {
            return response()->json(['recorded' => false]);
        }

        $sessionId = (string) $request->session()->getId();
        if ($sessionId === '') {
            return response()->json(['recorded' => false], 400);
        }

        $visitDate = Carbon::now('Asia/Manila')->toDateString();
        $visitorHash = hash_hmac(
            'sha256',
            $visitDate . ':' . $sessionId,
            (string) config('app.key')
        );

        DB::table('WBO_AnonymousLandingVisits')->insertOrIgnore([
            'visitor_hash' => $visitorHash,
            'visit_date' => $visitDate,
            'first_seen_at' => Carbon::now('UTC')->format('Y-m-d H:i:s'),
        ]);

        return response()->json(['recorded' => true]);
    }
}
