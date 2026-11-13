<?php

use Bazuka\FilamentAddress\Commands\NormalizeAddressesCommand;
use Bazuka\FilamentAddress\Models\Address;
use Bazuka\FilamentAddress\Tests\Place;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

function manualAddress(string $text): Address
{
    return Place::create()->addManualAddress($text);
}

it('normalizes manual addresses it can match', function (): void {
    fakeAddressApi([searchResult(HERSEGADE_ID, 'Hersegade 18, 4000 Roskilde')]);
    $address = manualAddress('Hersegade 18, 4000 Roskilde');

    $this->artisan(NormalizeAddressesCommand::class)->assertSuccessful();

    expect($address->fresh())
        ->source_id->toBe(HERSEGADE_ID)
        ->normalization_attempted_at->toBeNull();
});

it('marks addresses it could not match and waits before trying them again', function (): void {
    fakeAddressApi([]);
    $address = manualAddress('Ukendt vej 1, 9999 Ingensteds');

    $this->artisan(NormalizeAddressesCommand::class)->assertSuccessful();
    expect($address->fresh()->normalization_attempted_at)->not->toBeNull();

    Http::fake();
    $this->artisan(NormalizeAddressesCommand::class)->expectsOutput('No addresses to normalize.');
    Http::assertNothingSent();

    $this->travel(8)->days();
    fakeAddressApi([]);
    $this->artisan(NormalizeAddressesCommand::class)->expectsOutput('Normalized: 0. Could not match: 1.');
});

it('reaches new addresses even when the limit is full of addresses that never match', function (): void {
    fakeAddressApi([searchResult(HERSEGADE_ID, 'Hersegade 18, 4000 Roskilde')]);
    $stuck = collect(range(1, 3))->map(fn (int $number): Address => tap(
        manualAddress("Ukendt vej {$number}, 9999 Ingensteds"),
        fn (Address $address) => $address->update(['normalization_attempted_at' => now()->subDays(30)]),
    ));
    $new = manualAddress('Hersegade 18, 4000 Roskilde');

    $this->artisan(NormalizeAddressesCommand::class, ['--limit' => 1])->assertSuccessful();

    expect($new->fresh()->source_id)->toBe(HERSEGADE_ID)
        ->and($stuck->first()->fresh()->source_id)->toBeNull();
});

it('keeps going when the API is down', function (): void {
    Http::fake(fn () => throw new ConnectionException('Connection timed out'));
    manualAddress('Hersegade 18, 4000 Roskilde');
    manualAddress('Algade 1, 4000 Roskilde');

    $this->artisan(NormalizeAddressesCommand::class)
        ->expectsOutput('Normalized: 0. Could not match: 2.')
        ->assertSuccessful();
});

function normalizeEvents(): array
{
    return collect(app(Schedule::class)->events())
        ->filter(fn (Event $event): bool => str_contains((string) $event->command, 'filament-address:normalize'))
        ->values()
        ->all();
}

it('schedules the command once a day on one server at the configured time', function (): void {
    $events = normalizeEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0]->expression)->toBe('0 2 * * *')
        ->and($events[0]->onOneServer)->toBeTrue()
        ->and($events[0]->withoutOverlapping)->toBeTrue();
});

it('leaves scheduling to the app when no time is configured', function (): void {
    config(['filament-address.normalize_at' => null]);
    app()->forgetInstance(Schedule::class);

    expect(normalizeEvents())->toBeEmpty();
});
