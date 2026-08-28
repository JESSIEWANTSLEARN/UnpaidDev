<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\WBOUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

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

        Mail::to($user->email)->send(
            new OtpMail(
                otpCode: $otp,
                userName: $user->name,
                purpose: $purpose,
                expiryMinutes: $this->expiryMinutes()
            )
        );
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