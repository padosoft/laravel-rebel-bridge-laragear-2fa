<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Step-up drivers
    |--------------------------------------------------------------------------
    | Controls which laragear-backed step-up drivers are registered into the Rebel
    | step-up DriverRegistry. A driver is only registered when BOTH this flag is
    | true AND the underlying library is installed.
    |
    | laragear_totp: Registers the TOTP/recovery-code step-up driver (AAL2).
    |                Requires laragear/two-factor installed and the
    |                TwoFactorAuthentication trait on the User model.
    |                Set to false to completely disable TOTP step-up.
    */
    'drivers' => [
        'laragear_totp' => env('REBEL_LARAGEAR_DRIVER_TOTP', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Recovery codes
    |--------------------------------------------------------------------------
    | When true, the laragear_totp driver accepts a valid recovery code as an
    | alternative to a TOTP code. Recovery codes are single-use: laragear marks
    | each code consumed on first redemption.
    |
    | Set to false for environments where you want to restrict step-up strictly
    | to live TOTP codes (e.g. high-security contexts where recovery codes are
    | managed out-of-band).
    */
    'use_recovery_codes' => env('REBEL_LARAGEAR_USE_RECOVERY_CODES', true),

];
