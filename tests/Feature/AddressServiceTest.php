<?php

use Bazuka\FilamentAddress\Services\AddressService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('returns no results instead of throwing when the API cannot be reached', function (): void {
    Http::fake(fn () => throw new ConnectionException('Connection timed out'));

    expect(AddressService::addressAutocomplete('Hersegade 18'))->toBe([])
        ->and(AddressService::search('Hersegade 18'))->toBeNull()
        ->and(AddressService::addressById(HERSEGADE_ID))->toBeNull()
        ->and(AddressService::bestAddressMatch('Hersegade 18, 4000 Roskilde'))->toBeNull();
});

it('returns an empty list when a search has no results', function (): void {
    Http::fake(['*' => Http::response(['status' => 'ok', 'fund' => []])]);

    expect(AddressService::search('Nowhere 1'))->toBe([]);
});

it('returns null when the API answers with an error, so it can be told apart from no results', function (): void {
    Http::fake(['*' => Http::response('Bad gateway', 502)]);

    expect(AddressService::search('Nowhere 1'))->toBeNull();
});

it('does not look up ids that are not address ids', function (string $id): void {
    Http::fake();

    expect(AddressService::addressById($id))->toBeNull();

    Http::assertNothingSent();
})->with([
    'path traversal' => ['../adresser/soeg?tekst=x'],
    'empty' => [''],
    'plain text' => ['not-an-id'],
]);

it('uses the configured base url', function (): void {
    config(['filament-address.base_url' => 'https://example.test/']);
    Http::fake(['*' => Http::response(['status' => 'ok', 'fund' => []])]);

    AddressService::search('Hersegade 18');

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://example.test/adresser/soeg'));
});

it('matches an address whose postal code and house number agree with the text', function (): void {
    fakeAddressApi([
        searchResult('vej-id', 'Hersegade, 4000 Roskilde', 'vejnavn'),
        searchResult(HERSEGADE_ID, 'Hersegade 18, 4000 Roskilde'),
    ]);

    expect(AddressService::bestAddressMatch('Hersegade 18, 4000 Roskilde'))
        ->id->toBe(HERSEGADE_ID)
        ->street_number->toBe('18');
});

it('skips a suggestion with another house number and takes the next one that agrees', function (): void {
    fakeAddressApi([
        searchResult(HERSEGADE_NEIGHBOUR_ID, 'Hersegade 20, 4000 Roskilde'),
        searchResult(HERSEGADE_ID, 'Hersegade 18, 4000 Roskilde'),
    ]);

    expect(AddressService::bestAddressMatch('Hersegade 18, 4000 Roskilde'))->id->toBe(HERSEGADE_ID);
});

it('finds no match rather than a wrong one', function (string $text, array $expected): void {
    fakeAddressApi([searchResult(HERSEGADE_NEIGHBOUR_ID, 'Hersegade 20, 4000 Roskilde')]);

    expect(AddressService::bestAddressMatch($text, $expected))->toBeNull();
})->with([
    'other house number in the text' => ['Hersegade 18, 4000 Roskilde', []],
    'other postal code in the text' => ['Hersegade 20, 4100 Ringsted', []],
    'other house number in the fields' => ['Hersegade, 4000 Roskilde', ['house_number' => '18']],
]);

it('compares house numbers with letters regardless of case and spacing', function (): void {
    Http::fake([
        'adressevaelger.dk/adresser/soeg*' => Http::response(['status' => 'ok', 'fund' => [searchResult(HERSEGADE_ID, 'Hersegade 18B, 4000 Roskilde')]]),
        'adressevaelger.dk/adresser/*' => Http::response(addressLookup(HERSEGADE_ID, 'Hersegade', '18B', '4000', 'Roskilde')),
    ]);

    expect(AddressService::bestAddressMatch('Hersegade 18 b, 4000 Roskilde'))->id->toBe(HERSEGADE_ID);
});

it('never sends the token anywhere but to the API', function (): void {
    fakeAddressApi([]);

    AddressService::search('Hersegade 18');

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://adressevaelger.dk/')
        && $request['token'] === 'secret-token');
});
