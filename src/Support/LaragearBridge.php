<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Bridge\Laragear2fa\Support;

use Laragear\TwoFactor\Contracts\TwoFactorAuthenticatable;

/**
 * Tiny feature-detector for laragear/two-factor.
 *
 * The bridge is installable even when laragear is absent: in that case the
 * laragear-backed TOTP driver simply does not register. Check for the contract
 * class — it is the stable public surface of the library.
 */
final class LaragearBridge
{
    /** Is laragear/two-factor installed in this application? */
    public static function installed(): bool
    {
        return class_exists(TwoFactorAuthenticatable::class);
    }
}
