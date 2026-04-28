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
            ->hasConfigFile('dawa')
            ->hasViews();
    }

    public function packageBooted(): void
    {
        $this->publishes([
            __DIR__.'/../database/migrations/create_dawa_addresses_table.php.stub' => database_path(
                'migrations/'.date('Y_m_d_His').'_create_dawa_addresses_table.php'
            ),
        ], 'filament-dawa-migrations');
    }
}
