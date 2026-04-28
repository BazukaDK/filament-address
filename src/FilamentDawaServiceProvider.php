<?php

namespace Bazuka\FilamentDawa;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentDawaServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-dawa';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews();
    }
}
