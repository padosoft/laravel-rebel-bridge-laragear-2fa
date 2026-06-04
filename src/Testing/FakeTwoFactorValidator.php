<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Bridge\Laragear2fa\Testing;

use Illuminate\Contracts\Auth\Authenticatable;
use Padosoft\Rebel\Bridge\Laragear2fa\Contracts\TwoFactorValidator;

/**
 * Deterministic {@see TwoFactorValidator} for tests.
 *
 * Configure which users have 2FA enabled and which codes (TOTP or recovery) should
 * pass. Recovery codes are tracked as single-use: once a code is consumed it is
 * removed from the set and a second redemption returns false.
 *
 * Usage example:
 *
 *   $fake = new FakeTwoFactorValidator(
 *       enabled: ['1'],
 *       validCodes: ['123456'],
 *       validRecoveryCodes: ['AAAA-BBBB'],
 *   );
 *   app()->instance(TwoFactorValidator::class, $fake);
 */
final class FakeTwoFactorValidator implements TwoFactorValidator
{
    /**
     * @param  list<string>  $enabled  User auth-identifier strings that have 2FA enabled.
     * @param  list<string>  $validCodes  TOTP codes that should pass validation (not consumed).
     * @param  list<string>  $validRecoveryCodes  Recovery codes available for single-use redemption.
     */
    public function __construct(
        /** @var list<string> */
        public array $enabled = [],
        /** @var list<string> */
        public array $validCodes = [],
        /** @var list<string> */
        public array $validRecoveryCodes = [],
    ) {}

    public function isEnabled(Authenticatable $user): bool
    {
        $id = $user->getAuthIdentifier();

        return in_array(is_scalar($id) ? (string) $id : null, $this->enabled, true);
    }

    public function validate(Authenticatable $user, string $code, bool $useRecoveryCodes = true): bool
    {
        if (! $this->isEnabled($user)) {
            return false;
        }

        // TOTP codes are not single-use in the fake (mirrors laragear's per-period replay).
        if (in_array($code, $this->validCodes, true)) {
            return true;
        }

        if ($useRecoveryCodes) {
            $key = array_search($code, $this->validRecoveryCodes, true);

            if ($key !== false) {
                // Consume: remove from the list (single-use).
                array_splice($this->validRecoveryCodes, (int) $key, 1);

                return true;
            }
        }

        return false;
    }
}
