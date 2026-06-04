<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Bridge\Laragear2fa;

use Illuminate\Contracts\Config\Repository;
use Padosoft\Rebel\Bridge\Laragear2fa\Contracts\TwoFactorValidator;
use Padosoft\Rebel\Bridge\Laragear2fa\Drivers\LaragearTotpStepUpDriver;
use Padosoft\Rebel\Bridge\Laragear2fa\Support\LaragearBridge;
use Padosoft\Rebel\Bridge\Laragear2fa\Support\LaragearTwoFactorValidator;
use Padosoft\Rebel\Bridge\Laragear2fa\Testing\FakeTwoFactorValidator;
use Padosoft\Rebel\StepUp\DriverRegistry;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Bridges laragear/two-factor into Laravel Rebel:
 *  - registers the {@see LaragearTotpStepUpDriver} into the Rebel step-up
 *    {@see DriverRegistry} (only when config-enabled AND laragear is installed);
 *  - binds the production {@see LaragearTwoFactorValidator} behind the
 *    {@see TwoFactorValidator} seam (only when laragear is installed).
 *
 * When laragear is absent the service provider still boots cleanly: the driver is
 * simply not registered. Tests can bind a {@see FakeTwoFactorValidator}
 * directly so they run fully offline.
 */
final class RebelLaragear2faBridgeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-rebel-bridge-laragear-2fa')
            ->hasConfigFile('rebel-bridge-laragear-2fa');
    }

    public function packageBooted(): void
    {
        $this->bindValidator();
        $this->registerStepUpDrivers();
    }

    private function bindValidator(): void
    {
        if (! LaragearBridge::installed()) {
            return;
        }

        $this->app->singleton(TwoFactorValidator::class, LaragearTwoFactorValidator::class);
    }

    private function registerStepUpDrivers(): void
    {
        $config = $this->app->make(Repository::class);

        if ($config->get('rebel-bridge-laragear-2fa.drivers.laragear_totp', true) !== true) {
            return;
        }

        if (! LaragearBridge::installed()) {
            return;
        }

        $registry = $this->app->make(DriverRegistry::class);
        $registry->register($this->app->make(LaragearTotpStepUpDriver::class));
    }
}
