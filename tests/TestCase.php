<?php

namespace Bazuka\FilamentAddress\Tests;

use Bazuka\FilamentAddress\FilamentAddressServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * The package's migrations, in the order they were released.
     */
    private const MIGRATIONS = [
        'create_addresses_table',
        'normalize_addresses_table',
        'rename_label_to_type_in_addresses_table',
        'add_normalization_attempted_at_to_addresses_table',
    ];

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            SchemasServiceProvider::class,
            FormsServiceProvider::class,
            FilamentServiceProvider::class,
            FilamentAddressServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('filament-address.api_token', 'secret-token');
    }

    protected function defineDatabaseMigrations(): void
    {
        foreach (self::MIGRATIONS as $migration) {
            (require __DIR__."/../database/migrations/{$migration}.php")->up();
        }

        Schema::create('places', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
    }
}
