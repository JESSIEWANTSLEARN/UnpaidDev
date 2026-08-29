<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
class ThrottleLoginAttempts
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $email = strtolower(
            trim((string) $request->input('email', ''))
        );
        $key = 'wbo-login:' . sha1(
            $email . '|' . (string) $request->ip()
        );
        $maxAttempts = max(
            1,
            (int) config(
                'auth_security.login_rate_limit.max_attempts',
                5
            )
        );
        $lockSeconds = max(
            60,
            (int) config(
                'auth_security.login_rate_limit.lock_seconds',
                300
            )
        );
        if (
            $email !== '' &&
            RateLimiter::tooManyAttempts(
                $key,
                $maxAttempts
            )
        ) {
            return $this->lockedResponse(
                $key,
                $lockSeconds
            );
        }
        /** @var Response $response */
        $response = $next($request);
        /*
        |--------------------------------------------------------------------------
        | Only count invalid credentials
        |--------------------------------------------------------------------------
        |
        | LoginController returns HTTP 401 when the email/password pair is
        | incorrect. Validation errors, disabled accounts, OTP mail failures,
        | CSRF failures and normal server errors are intentionally NOT counted.
        |
        */
        if (
            $email !== '' &&
            $response->getStatusCode() === 401
        ) {
            RateLimiter::hit(
                $key,
                $lockSeconds
            );
            $attempts = RateLimiter::attempts($key);
            if ($attempts >= $maxAttempts) {
                return $this->lockedResponse(
                    $key,
                    $lockSeconds
                );
            }
            return response()->json([
                'success' => false,
                'message' =>
                    'Email or password is incorrect.',
                'attempts_remaining' =>
                    max(0, $maxAttempts - $attempts),
            ], 401);
        }
        /*
        |--------------------------------------------------------------------------
        | Successful login step clears failures
        |--------------------------------------------------------------------------
        */
        if (
            $email !== '' &&
            $response->isSuccessful()
        ) {
            RateLimiter::clear($key);
        }
        return $response;
    }
    private function lockedResponse(
        string $key,
        int $defaultSeconds
    ): JsonResponse {
        $seconds = RateLimiter::availableIn($key);
        if ($seconds <= 0) {
            $seconds = $defaultSeconds;
        }
        $minutes = max(
            1,
            (int) ceil($seconds / 60)
        );
        return response()
            ->json([
                'success' => false,
                'message' =>
                    "Too many login attempts. Try again in {$minutes} minute"
                    . ($minutes === 1 ? '.' : 's.'),
                'retry_after' => $seconds,
            ], 429)
            ->header(
                'Retry-After',
                (string) $seconds
            );
    }
}