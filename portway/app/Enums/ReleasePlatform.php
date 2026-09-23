<?php

namespace App\Enums;

enum ReleasePlatform: string
{
    case Windows = 'windows';
    case MacOS = 'macos';
    case Linux = 'linux';

    public function label(): string
    {
        return match ($this) {
            self::Windows => 'Windows',
            self::MacOS => 'macOS',
            self::Linux => 'Linux',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Windows => 'computer-desktop',
            self::MacOS => 'command-line',
            self::Linux => 'cpu-chip',
        };
    }

    /**
     * File extensions a build for this platform is allowed to use. Anything
     * else is rejected at upload time — this is both a sanity check for the
     * publisher and a hard guard against someone using the release store as
     * a way to host arbitrary executable web content.
     */
    public function allowedExtensions(): array
    {
        return match ($this) {
            self::Windows => ['exe', 'msi', 'msix', 'appx', 'zip'],
            self::MacOS => ['dmg', 'pkg', 'zip'],
            self::Linux => ['appimage', 'deb', 'rpm', 'gz', 'xz', 'zst', 'zip'],
        };
    }

    public function extensionHint(): string
    {
        return '.'.implode(', .', $this->allowedExtensions());
    }

    /**
     * Best-effort detection of the visitor's platform from a User-Agent
     * string, used to highlight the matching download on the public page.
     */
    public static function detectFromUserAgent(?string $userAgent): ?self
    {
        $ua = strtolower((string) $userAgent);

        if ($ua === '') {
            return null;
        }

        return match (true) {
            str_contains($ua, 'windows') => self::Windows,
            str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') => self::MacOS,
            str_contains($ua, 'linux') || str_contains($ua, 'x11') => self::Linux,
            default => null,
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
