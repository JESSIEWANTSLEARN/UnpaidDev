<?php

namespace App\Services;

use App\Models\WBOUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class OtpService
{
    public function length(): int
    {
        return max(4, min(8, (int) config('otp.length', 6)));
    }

    public function expiryMinutes(): int
    {
        return max(1, (int) config('otp.expiry_minutes', 5));
    }

    public function resendCooldownSeconds(): int
    {
        return max(
            0,
            (int) config('otp.resend_cooldown_seconds', 30)
        );
    }

    public function maxResends(): int
    {
        return max(0, (int) config('otp.max_resends', 2));
    }

    public function maxAttempts(): int
    {
        return max(1, (int) config('otp.max_attempts', 5));
    }

    public function generate(): string
    {
        $length = $this->length();
        $minimum = 10 ** ($length - 1);
        $maximum = (10 ** $length) - 1;

        return (string) random_int($minimum, $maximum);
    }

    public function hash(string $otp): string
    {
        return Hash::make($otp);
    }

    public function matches(string $otp, ?string $hash): bool
    {
        if (!is_string($hash) || $hash === '') {
            return false;
        }

        return Hash::check($otp, $hash);
    }

    public function expiryTimestamp(): int
    {
        return now()
            ->addMinutes($this->expiryMinutes())
            ->timestamp;
    }

    public function send(
        WBOUser $user,
        string $otp,
        string $purpose
    ): void {
        if (!in_array($purpose, ['login', 'signup'], true)) {
            throw new InvalidArgumentException('Invalid OTP purpose.');
        }

        $apiKey = (string) config('services.brevo.key');

        if ($apiKey === '') {
            throw new RuntimeException(
                'BREVO_API_KEY is not configured.'
            );
        }

        $senderEmail = (string) config('mail.from.address');
        $senderName = (string) config('mail.from.name', 'WalangBrownout');

        if ($senderEmail === '') {
            throw new RuntimeException(
                'MAIL_FROM_ADDRESS is not configured.'
            );
        }

        $subject = config(
            "otp.subjects.{$purpose}",
            'WalangBrownout OTP Verification'
        );

// Give each login OTP email a unique reference so email clients
// are less likely to group repeated login verification messages.
if ($purpose === 'login') {
    $requestReference = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $subject .= " - {$requestReference}";
}

        // Keep the existing OTP Blade design exactly as-is.
        $html = view('emails.otp', [
            'otpCode' => $otp,
            'userName' => $user->name,
            'purpose' => $purpose,
            'expiryMinutes' => $this->expiryMinutes(),
        ])->render();

        $response = Http::acceptJson()
            ->withHeaders([
                'api-key' => $apiKey,
            ])
            ->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => $senderName,
                    'email' => $senderEmail,
                ],
                'to' => [
                    [
                        'email' => $user->email,
                        'name' => $user->name,
                    ],
                ],
                'subject' => $subject,
                'htmlContent' => $html,
            ]);

        $response->throw();
    }

    public function publicPolicy(): array
    {
        return [
            'length' => $this->length(),
            'expiry_minutes' => $this->expiryMinutes(),
            'resend_cooldown_seconds' =>
                $this->resendCooldownSeconds(),
            'max_resends' => $this->maxResends(),
            'max_attempts' => $this->maxAttempts(),
        ];
    }
}
