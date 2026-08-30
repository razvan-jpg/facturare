<?php

namespace App\Services;

class UserAgentParser
{
    /**
     * @return array{browser: string, platform: string}
     */
    public function parse(?string $userAgent): array
    {
        $ua = trim((string) $userAgent);
        if ($ua === '') {
            return ['browser' => 'Necunoscut', 'platform' => 'Necunoscut'];
        }

        return [
            'browser' => $this->browser($ua),
            'platform' => $this->platform($ua),
        ];
    }

    public function browserLabel(?string $userAgent): string
    {
        $parsed = $this->parse($userAgent);

        if ($parsed['platform'] === 'Necunoscut') {
            return $parsed['browser'];
        }

        return $parsed['browser'].' · '.$parsed['platform'];
    }

    private function browser(string $ua): string
    {
        // Order matters: Edge/Opera before Chrome; Chrome before Safari.
        if (str_contains($ua, 'Edg/') || str_contains($ua, 'EdgiOS/')) {
            return 'Edge';
        }
        if (str_contains($ua, 'OPR/') || str_contains($ua, 'Opera')) {
            return 'Opera';
        }
        if (str_contains($ua, 'SamsungBrowser')) {
            return 'Samsung Internet';
        }
        if (str_contains($ua, 'Firefox/') || str_contains($ua, 'FxiOS/')) {
            return 'Firefox';
        }
        if (str_contains($ua, 'CriOS/') || (str_contains($ua, 'Chrome/') && ! str_contains($ua, 'Edg/'))) {
            return 'Chrome';
        }
        if (str_contains($ua, 'Safari/') && ! str_contains($ua, 'Chrome/') && ! str_contains($ua, 'CriOS/')) {
            return 'Safari';
        }
        if (str_contains($ua, 'MSIE') || str_contains($ua, 'Trident/')) {
            return 'Internet Explorer';
        }
        if (str_contains($ua, 'bot') || str_contains($ua, 'Bot') || str_contains($ua, 'Spider')) {
            return 'Bot';
        }

        return 'Altul';
    }

    private function platform(string $ua): string
    {
        if (str_contains($ua, 'Android')) {
            return 'Android';
        }
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') || str_contains($ua, 'iOS')) {
            return 'iOS';
        }
        if (str_contains($ua, 'Windows')) {
            return 'Windows';
        }
        if (str_contains($ua, 'Mac OS X') || str_contains($ua, 'Macintosh')) {
            return 'macOS';
        }
        if (str_contains($ua, 'Linux')) {
            return 'Linux';
        }

        return 'Necunoscut';
    }
}
