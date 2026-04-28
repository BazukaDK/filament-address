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
            ->hasViews();
    }

    public function packageBooted(): void
    {
        $this->publishes([
            __DIR__.'/../database/migrations/create_dawa_addresses_table.php.stub' => database_path(
                'migrations/'.date('Y_m_d_His').'_create_dawa_addresses_table.php'
            ),
        ], 'filament-address-migrations');
    }
}
