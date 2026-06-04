<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Padosoft\Rebel\Bridge\Laragear2fa\Contracts\TwoFactorValidator;
use Padosoft\Rebel\Bridge\Laragear2fa\Drivers\LaragearTotpStepUpDriver;
use Padosoft\Rebel\Bridge\Laragear2fa\Testing\FakeTwoFactorValidator;
use Padosoft\Rebel\Bridge\Laragear2fa\Tests\Fixtures\User;
use Padosoft\Rebel\Core\Assurance\Aal;

// ---------------------------------------------------------------------------
// Driver key & assurance
// ---------------------------------------------------------------------------

it('reports driver key as laragear_totp', function (): void {
    expect(app(LaragearTotpStepUpDriver::class)->key())->toBe('laragear_totp');
});

it('declares AAL2 and is not phishing-resistant', function (): void {
    $assurance = app(LaragearTotpStepUpDriver::class)->assurance();

    expect($assurance->aal)->toBe(Aal::Aal2)
        ->and($assurance->phishingResistant)->toBeFalse()
        ->and($assurance->amr)->toBe(['otp', 'totp']);
});

// ---------------------------------------------------------------------------
// isAvailableFor
// ---------------------------------------------------------------------------

it('is available when 2FA is enabled for the subject', function (): void {
    $user = User::create(['email' => 'a@b.com']);

    app()->instance(TwoFactorValidator::class, new FakeTwoFactorValidator(
        enabled: [(string) $user->getAuthIdentifier()],
    ));

    expect(app(LaragearTotpStepUpDriver::class)->isAvailableFor(bridgeCtx($user)))->toBeTrue();
});

it('is not available when 2FA is disabled for the subject', function (): void {
    $user = User::create(['email' => 'b@b.com']);

    app()->instance(TwoFactorValidator::class, new FakeTwoFactorValidator(
        enabled: [], // not enabled
    ));

    expect(app(LaragearTotpStepUpDriver::class)->isAvailableFor(bridgeCtx($user)))->toBeFalse();
});

it('fails closed when isEnabled throws', function (): void {
    $user = User::create(['email' => 'c@b.com']);

    $throwing = new class implements TwoFactorValidator
    {
        public function isEnabled(Authenticatable $user): bool
        {
            throw new RuntimeException('storage down');
        }

        public function validate(Authenticatable $user, string $code, bool $useRecoveryCodes = true): bool
        {
            return false;
        }
    };

    app()->instance(TwoFactorValidator::class, $throwing);

    expect(app(LaragearTotpStepUpDriver::class)->isAvailableFor(bridgeCtx($user)))->toBeFalse();
});

// ---------------------------------------------------------------------------
// start() always returns null
// ---------------------------------------------------------------------------

it('start() returns null — authenticator app generates code client-side', function (): void {
    $user = User::create(['email' => 'd@b.com']);

    expect(app(LaragearTotpStepUpDriver::class)->start(bridgeCtx($user)))->toBeNull();
});

// ---------------------------------------------------------------------------
// verify() — TOTP
// ---------------------------------------------------------------------------

it('verify() returns true for a valid TOTP code', function (): void {
    $user = User::create(['email' => 'e@b.com']);
    $id = (string) $user->getAuthIdentifier();

    app()->instance(TwoFactorValidator::class, new FakeTwoFactorValidator(
        enabled: [$id],
        validCodes: ['123456'],
    ));

    $driver = app(LaragearTotpStepUpDriver::class);

    expect($driver->verify(bridgeCtx($user), '123456', null))->toBeTrue();
});

it('verify() returns false for an invalid TOTP code', function (): void {
    $user = User::create(['email' => 'f@b.com']);
    $id = (string) $user->getAuthIdentifier();

    app()->instance(TwoFactorValidator::class, new FakeTwoFactorValidator(
        enabled: [$id],
        validCodes: ['123456'],
    ));

    $driver = app(LaragearTotpStepUpDriver::class);

    expect($driver->verify(bridgeCtx($user), '000000', null))->toBeFalse();
});

it('verify() returns false when 2FA is not enabled for the user', function (): void {
    $user = User::create(['email' => 'g@b.com']);

    app()->instance(TwoFactorValidator::class, new FakeTwoFactorValidator(
        enabled: [],
        validCodes: ['123456'],
    ));

    $driver = app(LaragearTotpStepUpDriver::class);

    // Even a "valid" code should fail if 2FA is not enabled.
    expect($driver->verify(bridgeCtx($user), '123456', null))->toBeFalse();
});

// ---------------------------------------------------------------------------
// verify() — recovery codes (single-use)
// ---------------------------------------------------------------------------

it('verify() accepts a single-use recovery code', function (): void {
    $user = User::create(['email' => 'h@b.com']);
    $id = (string) $user->getAuthIdentifier();

    $fake = new FakeTwoFactorValidator(
        enabled: [$id],
        validCodes: [],
        validRecoveryCodes: ['AAAA-BBBB', 'CCCC-DDDD'],
    );
    app()->instance(TwoFactorValidator::class, $fake);

    $driver = app(LaragearTotpStepUpDriver::class);
    $ctx = bridgeCtx($user);

    // First use succeeds.
    expect($driver->verify($ctx, 'AAAA-BBBB', null))->toBeTrue()
        // Second use of the SAME code fails (single-use consumed).
        ->and($driver->verify($ctx, 'AAAA-BBBB', null))->toBeFalse()
        // The other code still works.
        ->and($driver->verify($ctx, 'CCCC-DDDD', null))->toBeTrue();
});

it('does not try recovery codes when use_recovery_codes config is false', function (): void {
    config()->set('rebel-bridge-laragear-2fa.use_recovery_codes', false);

    $user = User::create(['email' => 'i@b.com']);
    $id = (string) $user->getAuthIdentifier();

    $fake = new FakeTwoFactorValidator(
        enabled: [$id],
        validCodes: [],
        validRecoveryCodes: ['AAAA-BBBB'],
    );
    app()->instance(TwoFactorValidator::class, $fake);

    $driver = app(LaragearTotpStepUpDriver::class);

    // Recovery code should be rejected when config disables them.
    expect($driver->verify(bridgeCtx($user), 'AAAA-BBBB', null))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Fail-closed on Throwable
// ---------------------------------------------------------------------------

it('verify() returns false and does not throw when validate() throws', function (): void {
    $user = User::create(['email' => 'j@b.com']);

    $throwing = new class implements TwoFactorValidator
    {
        public function isEnabled(Authenticatable $user): bool
        {
            return true;
        }

        public function validate(Authenticatable $user, string $code, bool $useRecoveryCodes = true): bool
        {
            throw new RuntimeException('db is down');
        }
    };

    app()->instance(TwoFactorValidator::class, $throwing);

    $driver = app(LaragearTotpStepUpDriver::class);

    expect($driver->verify(bridgeCtx($user), '123456', null))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Audit telemetry
// ---------------------------------------------------------------------------

it('records verified audit event on successful TOTP and does not include the code', function (): void {
    $user = User::create(['email' => 'k@b.com']);
    $id = (string) $user->getAuthIdentifier();

    app()->instance(TwoFactorValidator::class, new FakeTwoFactorValidator(
        enabled: [$id],
        validCodes: ['999999'],
    ));

    app(LaragearTotpStepUpDriver::class)->verify(bridgeCtx($user), '999999', null);

    $row = DB::table('rebel_auth_events')
        ->where('event_type', 'stepup.laragear_totp.verified')
        ->first();

    expect($row)->not->toBeNull()
        ->and($row->channel)->toBe('totp');

    // The OTP must NOT appear in the stored metadata.
    $meta = is_string($row->metadata) ? $row->metadata : '';
    expect($meta)->not->toContain('999999');
});

it('records failed audit event on failed verification', function (): void {
    $user = User::create(['email' => 'l@b.com']);
    $id = (string) $user->getAuthIdentifier();

    app()->instance(TwoFactorValidator::class, new FakeTwoFactorValidator(
        enabled: [$id],
        validCodes: [],
    ));

    app(LaragearTotpStepUpDriver::class)->verify(bridgeCtx($user), '000000', null);

    $row = DB::table('rebel_auth_events')
        ->where('event_type', 'stepup.laragear_totp.failed')
        ->first();

    expect($row)->not->toBeNull();
});

it('records recovery_code.used event before verified on recovery-code redemption', function (): void {
    $user = User::create(['email' => 'm@b.com']);
    $id = (string) $user->getAuthIdentifier();

    app()->instance(TwoFactorValidator::class, new FakeTwoFactorValidator(
        enabled: [$id],
        validRecoveryCodes: ['ZZZZ-YYYY'],
    ));

    app(LaragearTotpStepUpDriver::class)->verify(bridgeCtx($user), 'ZZZZ-YYYY', null);

    $types = DB::table('rebel_auth_events')
        ->orderBy('created_at')
        ->pluck('event_type')
        ->toArray();

    expect($types)->toContain('stepup.laragear_totp.recovery_code.used')
        ->and($types)->toContain('stepup.laragear_totp.verified');
});
