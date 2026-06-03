<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Bridge\Laragear2fa;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Service provider for the laravel-rebel-bridge-laragear-2fa package (initial skeleton).
 * The full implementation will arrive in its roadmap macro-task.
 */
final class RebelLaragear2faBridgeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('laravel-rebel-bridge-laragear-2fa');
    }
}
