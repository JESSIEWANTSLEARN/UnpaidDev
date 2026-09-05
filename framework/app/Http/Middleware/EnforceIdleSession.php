<?php

namespace App\Http\Middleware;

use App\Services\Auth\AuthSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceIdleSession
{
    public function __construct(
        private AuthSessionService $sessions
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (
            $request->session()->get('logged_in') === true
        ) {
            $state = $this->sessions->validate($request);

            if ($state !== 'ok') {
                if (
                    $request->expectsJson() ||
                    $request->is('api/*')
                ) {
                    return response()->json([
                        'success' => false,
                        'message' =>
                            $state === 'idle_timeout'
                                ? 'Your session expired because you were inactive.'
                                : 'Your session is no longer active.',
                        'code' =>
                            $state === 'idle_timeout'
                                ? 'SESSION_IDLE_TIMEOUT'
                                : 'SESSION_INVALID',
                        'redirect' => '/login',
                    ], 401);
                }

                return redirect('/login');
            }
        }

        return $next($request);
    }
}