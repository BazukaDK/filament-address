<?php

namespace Bazuka\FilamentAddress;

use Bazuka\FilamentAddress\Commands\NormalizeAddressesCommand;
use Illuminate\Console\Scheduling\Schedule;
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
            ->hasMigration('create_addresses_table')
            ->hasCommand(NormalizeAddressesCommand::class);
    }

    public function packageBooted(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(NormalizeAddressesCommand::class)->dailyAt('02:00');
        });
    }
}
