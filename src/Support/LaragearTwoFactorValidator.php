<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Bridge\Laragear2fa\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Laragear\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Laragear\TwoFactor\TwoFactorAuthentication;
use Padosoft\Rebel\Bridge\Laragear2fa\Contracts\TwoFactorValidator;

/**
 * Production {@see TwoFactorValidator} backed by laragear/two-factor.
 *
 * This class is only bound into the container when laragear/two-factor is installed
 * (feature-detected via {@see LaragearBridge::installed()}). At that point the user
 * model is expected to implement {@see TwoFactorAuthenticatable} (i.e. use the
 * {@see TwoFactorAuthentication} trait).
 *
 * If the subject does not implement {@see TwoFactorAuthenticatable} we fail closed
 * (isEnabled → false, validate → false) so step-up is not accidentally skipped.
 */
final class LaragearTwoFactorValidator implements TwoFactorValidator
{
    public function isEnabled(Authenticatable $user): bool
    {
        if (! $user instanceof TwoFactorAuthenticatable) {
            return false;
        }

        try {
            return $user->hasTwoFactorEnabled();
        } catch (\Throwable) {
            return false;
        }
    }

    public function validate(Authenticatable $user, string $code, bool $useRecoveryCodes = true): bool
    {
        if (! $user instanceof TwoFactorAuthenticatable) {
            return false;
        }

        try {
            // validateTwoFactorCode checks hasTwoFactorEnabled() internally as well as
            // TOTP validation and optionally recovery-code redemption (single-use,
            // marked consumed by laragear's trait).
            return $user->validateTwoFactorCode($code, $useRecoveryCodes);
        } catch (\Throwable) {
            return false;
        }
    }
}
