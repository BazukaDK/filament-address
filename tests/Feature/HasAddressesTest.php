<?php

use Bazuka\FilamentAddress\Tests\Place;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('keeps the raw fields when the API is down, so saving never fails', function (): void {
    Http::fake(fn () => throw new ConnectionException('Connection timed out'));
    $place = Place::create();

    $address = $place->addRawAddress(['street_name' => 'Hersegade', 'house_number' => '18', 'postal_code' => '4000', 'city' => 'Roskilde']);

    expect($address)
        ->source_id->toBeNull()
        ->formatted_address->toBe('Hersegade 18, 4000 Roskilde')
        ->postal_code->toBe('4000');
});

it('normalizes raw fields right away when the API finds the address', function (): void {
    fakeAddressApi([searchResult(HERSEGADE_ID, 'Hersegade 18, 4000 Roskilde')]);
    $place = Place::create();

    $address = $place->addRawAddress(['street_name' => 'Hersegade', 'house_number' => '18', 'postal_code' => '4000', 'city' => 'Roskilde']);

    expect($address)->source_id->toBe(HERSEGADE_ID)->latitude->not->toBeNull();
});

it('updates the address of the same type instead of adding another', function (): void {
    Http::fake(fn () => throw new ConnectionException('Connection timed out'));
    $place = Place::create();

    $place->addManualAddress('Hersegade 18, 4000 Roskilde', 'billing');
    $place->addManualAddress('Algade 1, 4000 Roskilde', 'billing');
    $place->addManualAddress('Skomagergade 2, 4000 Roskilde', 'shipping');

    expect($place->addresses()->count())->toBe(2)
        ->and($place->addresses()->where('type', 'billing')->value('formatted_address'))->toBe('Algade 1, 4000 Roskilde');
});

it('ignores addresses without any text', function (): void {
    Http::fake();
    $place = Place::create();

    expect($place->addManualAddress('   '))->toBeNull()
        ->and($place->addRawAddress(['street_name' => null, 'city' => '']))->toBeNull()
        ->and($place->addAddress(['id' => 'not-an-id', 'titel' => '']))->toBeNull()
        ->and($place->addresses()->count())->toBe(0);

    Http::assertNothingSent();
});

it('keeps a selected address as manual text when it cannot be looked up', function (): void {
    Http::fake(fn () => throw new ConnectionException('Connection timed out'));
    $place = Place::create();

    $address = $place->addAddress(['id' => HERSEGADE_ID, 'titel' => 'Hersegade 18, 4000 Roskilde']);

    expect($address)->source_id->toBeNull()->formatted_address->toBe('Hersegade 18, 4000 Roskilde');
});

it('does not look up a selected address whose id was tampered with', function (): void {
    Http::fake();
    $place = Place::create();

    $address = $place->addAddress(['id' => '../../admin', 'titel' => 'Hersegade 18, 4000 Roskilde']);

    expect($address)->source_id->toBeNull();
    Http::assertNothingSent();
});
