<?php

namespace Bazuka\FilamentAddress;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentAddressServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-address';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile('filament-address')
            ->hasViews()
            ->hasMigration('create_dawa_addresses_table');
    }
}
