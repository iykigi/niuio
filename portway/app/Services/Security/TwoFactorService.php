<?php

namespace App\Services\Security;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Everything to do with a user's TOTP secret and recovery codes lives
 * here, so the setup/disable flow in SecurityCenter stays a thin
 * controller and can be unit-tested without rendering Livewire.
 */
class TwoFactorService
{
    private Google2FA $engine;

    public function __construct()
    {
        $this->engine = new Google2FA;
    }

    public function generateSecretKey(): string
    {
        return $this->engine->generateSecretKey();
    }

    public function qrCodeSvg(User $user, string $secret): string
    {
        $url = $this->engine->getQRCodeUrl(
            config('app.name', 'Portway'),
            $user->email,
            $secret,
        );

        $renderer = new ImageRenderer(
            new RendererStyle(220, 1),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($url);
    }

    public function verify(string $secret, string $code): bool
    {
        // A window of 1 accepts the previous, current, and next 30s
        // period (~90s of clock skew tolerance) — the standard TOTP
        // allowance. The previous, much wider window of 4 accepted
        // codes up to ~2 minutes old, needlessly widening the brute
        // force / replay window for a stolen or shoulder-surfed code.
        return (bool) $this->engine->verifyKey($secret, $code, 1);
    }

    /**
     * Confirms a pending secret, activates two-factor for the account,
     * and issues a fresh set of one-time recovery codes.
     *
     * @return array<int, string> the plaintext recovery codes (shown once)
     */
    public function confirm(User $user, string $secret): array
    {
        $codes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $codes;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * @return array<int, string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodes();

        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return $codes;
    }

    /**
     * @return array<int, string>
     */
    private function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => Str::lower(Str::random(10)).'-'.Str::lower(Str::random(10)))
            ->all();
    }
}
