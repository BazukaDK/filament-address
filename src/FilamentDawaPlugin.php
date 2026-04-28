<?php

namespace Bazuka\FilamentDawa;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentDawaPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-dawa';
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
