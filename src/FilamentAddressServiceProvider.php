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
            ->hasTranslations()
            ->hasMigrations(['create_addresses_table', 'normalize_addresses_table', 'rename_label_to_type_in_addresses_table', 'add_normalization_attempted_at_to_addresses_table'])
            ->hasCommand(NormalizeAddressesCommand::class);
    }

    public function packageBooted(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $time = config('filament-address.normalize_at');

            if (blank($time)) {
                return;
            }

            // onOneServer() keeps the run single when several app servers share the schedule.
            $schedule->command(NormalizeAddressesCommand::class)
                ->dailyAt($time)
                ->withoutOverlapping()
                ->onOneServer();
        });
    }
}
