<?php

declare(strict_types=1);

use Padosoft\Rebel\Bridge\Laragear2fa\Testing\FakeTwoFactorValidator;
use Padosoft\Rebel\Bridge\Laragear2fa\Tests\Fixtures\User;

it('fake validator reports enabled correctly', function (): void {
    $user = User::create(['email' => 'a@fake.com']);
    $id = (string) $user->getAuthIdentifier();

    $fake = new FakeTwoFactorValidator(enabled: [$id]);

    expect($fake->isEnabled($user))->toBeTrue();

    $other = User::create(['email' => 'b@fake.com']);
    expect($fake->isEnabled($other))->toBeFalse();
});

it('fake validator validates TOTP codes (not single-use)', function (): void {
    $user = User::create(['email' => 'c@fake.com']);
    $id = (string) $user->getAuthIdentifier();

    $fake = new FakeTwoFactorValidator(enabled: [$id], validCodes: ['654321']);

    expect($fake->validate($user, '654321'))->toBeTrue()
        // Second call with same code still passes (TOTP per-period, not single-use).
        ->and($fake->validate($user, '654321'))->toBeTrue()
        ->and($fake->validate($user, '000000'))->toBeFalse();
});

it('fake validator consumes recovery codes single-use', function (): void {
    $user = User::create(['email' => 'd@fake.com']);
    $id = (string) $user->getAuthIdentifier();

    $fake = new FakeTwoFactorValidator(
        enabled: [$id],
        validRecoveryCodes: ['REC-1111', 'REC-2222'],
    );

    expect($fake->validate($user, 'REC-1111'))->toBeTrue()
        ->and($fake->validate($user, 'REC-1111'))->toBeFalse() // consumed
        ->and($fake->validate($user, 'REC-2222'))->toBeTrue();
});

it('fake validator returns false when useRecoveryCodes is false even for a valid recovery code', function (): void {
    $user = User::create(['email' => 'e@fake.com']);
    $id = (string) $user->getAuthIdentifier();

    $fake = new FakeTwoFactorValidator(enabled: [$id], validRecoveryCodes: ['REC-ONLY']);

    expect($fake->validate($user, 'REC-ONLY', false))->toBeFalse();
});
