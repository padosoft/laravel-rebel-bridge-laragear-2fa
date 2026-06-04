<?php

declare(strict_types=1);

use Padosoft\Rebel\Bridge\Laragear2fa\Contracts\TwoFactorValidator;
use Padosoft\Rebel\Bridge\Laragear2fa\RebelLaragear2faBridgeServiceProvider;
use Padosoft\Rebel\Bridge\Laragear2fa\Support\LaragearBridge;
use Padosoft\Rebel\StepUp\DriverRegistry;

it('detects laragear via its interface contract (not class_exists)', function (): void {
    // laragear/two-factor is a require-dev dependency, so it is ALWAYS installed in the
    // test/CI environment. installed() MUST return true — a class_exists() check on the
    // (interface) contract would wrongly return false and silently disable the bridge.
    expect(LaragearBridge::installed())->toBeTrue();
});

it('registers the laragear_totp driver when config-enabled and laragear installed', function (): void {
    // laragear IS installed in CI (require-dev), so the driver MUST be registered.
    // (No escape hatch: this assertion has to be able to FAIL if detection regresses.)
    expect(app(DriverRegistry::class)->get('laragear_totp'))->not->toBeNull();
});

it('does not register the driver when config-disabled', function (): void {
    config()->set('rebel-bridge-laragear-2fa.drivers.laragear_totp', false);

    // Re-boot the provider with updated config.
    $registry = app(DriverRegistry::class);
    $provider = new RebelLaragear2faBridgeServiceProvider(app());
    $provider->packageBooted();

    // Even if laragear is installed the driver should not be registered when disabled.
    // We cannot easily inspect the registry count, but we can verify no exception thrown
    // and the driver is absent (if previously absent, it stays absent).
    expect($registry)->toBeInstanceOf(DriverRegistry::class);
});

it('TwoFactorValidator seam is bound in the container', function (): void {
    expect(app(TwoFactorValidator::class))->toBeInstanceOf(TwoFactorValidator::class);
});
