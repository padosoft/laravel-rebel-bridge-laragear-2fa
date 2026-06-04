<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Bridge\Laragear2fa\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Padosoft\Rebel\Bridge\Laragear2fa\Support\LaragearBridge;
use Padosoft\Rebel\Bridge\Laragear2fa\Testing\FakeTwoFactorValidator;

/**
 * Thin seam wrapping laragear/two-factor's validation surface.
 *
 * The bridge never calls laragear's classes directly: it always goes through this
 * contract. This makes the step-up driver fully testable without laragear installed
 * — swap in a {@see FakeTwoFactorValidator} in tests and CI runs offline.
 *
 * The real laragear-backed implementation is feature-detected at boot time
 * ({@see LaragearBridge::installed()}) and
 * only bound when laragear is present.
 */
interface TwoFactorValidator
{
    /**
     * Does this user have laragear two-factor authentication enabled (and confirmed)?
     */
    public function isEnabled(Authenticatable $user): bool;

    /**
     * Validate a TOTP code or a recovery code for the given user.
     *
     * Recovery codes are single-use; each call to this method that matches a recovery
     * code MUST mark it consumed so it cannot be redeemed again. Whether TOTP replay
     * protection is provided depends on laragear's internal cache integration.
     *
     * Returns true on success, false on any failure. MUST NOT throw.
     *
     * @param  bool  $useRecoveryCodes  When true, fall back to recovery codes after a
     *                                  failed TOTP check. Set to false to restrict to
     *                                  TOTP-only validation.
     */
    public function validate(Authenticatable $user, string $code, bool $useRecoveryCodes = true): bool;
}
