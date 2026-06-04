<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Bridge\Laragear2fa\Support;

use Laragear\TwoFactor\Contracts\TwoFactorAuthenticatable;

/**
 * Tiny feature-detector for laragear/two-factor.
 *
 * The bridge is installable even when laragear is absent: in that case the
 * laragear-backed TOTP driver simply does not register. Detect laragear by its
 * stable public contract {@see TwoFactorAuthenticatable}.
 *
 * NOTE: that contract is an **interface**, and PHP's `class_exists()` returns
 * `false` for interfaces — so detection MUST use `interface_exists()` (a
 * `class_exists()` check here is the classic bug that silently disables the
 * bridge in every real install). A `class_exists()` fallback is kept only in
 * case a future laragear release ships the symbol as a class.
 */
final class LaragearBridge
{
    /** Is laragear/two-factor installed in this application? */
    public static function installed(): bool
    {
        return interface_exists(TwoFactorAuthenticatable::class)
            || class_exists(TwoFactorAuthenticatable::class);
    }
}
