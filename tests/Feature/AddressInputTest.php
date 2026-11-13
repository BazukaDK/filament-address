<?php

use Bazuka\FilamentAddress\Forms\Components\AddressInput;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

it('searches on the server and returns only what the dropdown needs', function (): void {
    Http::fake(['*' => Http::response(['status' => 'ok', 'fund' => [
        ['id' => HERSEGADE_ID, 'type' => 'adresse', 'titel' => 'Hersegade 18, 4000 Roskilde', 'internal' => 'x'],
    ]])]);

    $result = AddressInput::make('address')->suggestionCount(5)->searchAddresses('Hersegade 18');

    expect($result)->toBe(['ok' => true, 'suggestions' => [
        ['id' => HERSEGADE_ID, 'type' => 'adresse', 'titel' => 'Hersegade 18, 4000 Roskilde'],
    ]]);
    Http::assertSent(fn (Request $request): bool => $request['maksimum'] === 5);
});

it('lets the browser call the search', function (): void {
    $method = new ReflectionMethod(AddressInput::class, 'searchAddresses');

    expect($method->getAttributes(ExposedLivewireMethod::class))->not->toBeEmpty();
});

it('reports when the lookup is unavailable, so the user can enter the address by hand', function (): void {
    Http::fake(fn () => throw new ConnectionException('Connection timed out'));

    expect(AddressInput::make('address')->searchAddresses('Hersegade 18'))->toBe(['ok' => false, 'suggestions' => []]);
});

it('does not search for less than two characters', function (): void {
    Http::fake();

    expect(AddressInput::make('address')->searchAddresses(' H '))->toBe(['ok' => true, 'suggestions' => []]);
    Http::assertNothingSent();
});

it('limits how often one session can search', function (): void {
    Http::fake(['*' => Http::response(['status' => 'ok', 'fund' => []])]);
    $input = AddressInput::make('address');

    foreach (range(1, AddressInput::SEARCHES_PER_MINUTE) as $ignored) {
        $input->searchAddresses('Hersegade');
    }

    expect($input->searchAddresses('Hersegade'))->toBe(['ok' => false, 'suggestions' => []]);
    RateLimiter::clear('filament-address-search:'.session()->getId());
});

it('keeps the API token out of the page', function (): void {
    $view = file_get_contents(__DIR__.'/../../resources/views/forms/components/address-input.blade.php');

    expect($view)->not->toContain('api_token')->not->toContain('adressevaelger.dk');
});
