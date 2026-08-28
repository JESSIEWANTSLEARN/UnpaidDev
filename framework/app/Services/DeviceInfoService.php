<?php

namespace App\Services;

use Illuminate\Http\Request;

class DeviceInfoService
{
    public function fromRequest(Request $request): array
    {
        $userAgent = $this->cleanUserAgent(
            (string) $request->userAgent()
        );

        return [
            'device_name' => $this->deviceName($userAgent),
            'browser_name' => $this->browserName($userAgent),
            'operating_system' =>
                $this->operatingSystem($userAgent),
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
        ];
    }

    public function sameUserAgent(
        ?string $stored,
        Request $request
    ): bool {
        return hash_equals(
            (string) $stored,
            $this->cleanUserAgent(
                (string) $request->userAgent()
            )
        );
    }

    private function cleanUserAgent(string $userAgent): string
    {
        return mb_substr(
            trim($userAgent),
            0,
            500
        );
    }

    private function deviceName(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        if (
            str_contains($ua, 'ipad') ||
            str_contains($ua, 'tablet')
        ) {
            return 'Tablet';
        }

        if (
            str_contains($ua, 'mobile') ||
            str_contains($ua, 'android') ||
            str_contains($ua, 'iphone')
        ) {
            return 'Mobile Device';
        }

        return 'Desktop / Laptop';
    }

    private function browserName(string $userAgent): string
    {
        if (
            str_contains($userAgent, 'Edg/')
        ) {
            return 'Microsoft Edge';
        }

        if (
            str_contains($userAgent, 'OPR/') ||
            str_contains($userAgent, 'Opera')
        ) {
            return 'Opera';
        }

        if (
            str_contains($userAgent, 'Chrome/') &&
            !str_contains($userAgent, 'Chromium/')
        ) {
            return 'Google Chrome';
        }

        if (
            str_contains($userAgent, 'Firefox/')
        ) {
            return 'Mozilla Firefox';
        }

        if (
            str_contains($userAgent, 'Safari/') &&
            !str_contains($userAgent, 'Chrome/')
        ) {
            return 'Safari';
        }

        return 'Unknown Browser';
    }

    private function operatingSystem(string $userAgent): string
    {
        if (str_contains($userAgent, 'Windows NT 10.0')) {
            return 'Windows';
        }

        if (
            str_contains($userAgent, 'Android')
        ) {
            return 'Android';
        }

        if (
            str_contains($userAgent, 'iPhone') ||
            str_contains($userAgent, 'iPad')
        ) {
            return 'iOS / iPadOS';
        }

        if (
            str_contains($userAgent, 'Mac OS X') ||
            str_contains($userAgent, 'Macintosh')
        ) {
            return 'macOS';
        }

        if (str_contains($userAgent, 'Linux')) {
            return 'Linux';
        }

        return 'Unknown OS';
    }
}