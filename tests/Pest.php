<?php

use Bazuka\FilamentAddress\Tests\TestCase;
use Illuminate\Support\Facades\Http;

uses(TestCase::class)->in('Feature');

const HERSEGADE_ID = '0a3f50a0-1234-4a5b-8c9d-000000000018';
const HERSEGADE_NEIGHBOUR_ID = '0a3f50a0-1234-4a5b-8c9d-000000000020';

/**
 * A search result as the API returns it.
 *
 * @return array{id: string, type: string, titel: string}
 */
function searchResult(string $id, string $titel, string $type = 'adresse'): array
{
    return ['id' => $id, 'type' => $type, 'titel' => $titel];
}

/**
 * An address lookup as the API returns it.
 *
 * @return array<string, mixed>
 */
function addressLookup(string $id, string $street, string $number, string $postalCode, string $city): array
{
    return [
        'status' => 'ok',
        'adresse' => [
            'id_lokalid' => $id,
            'adressebetegnelse' => "{$street} {$number}, {$postalCode} {$city}",
            'etagebetegnelse' => null,
            'doerbetegnelse' => null,
            'husnummer' => [
                'vejnavn' => $street,
                'husnummertekst' => $number,
                'postnummer' => ['postnr' => $postalCode, 'navn' => $city],
                'supplerendebynavn' => null,
                'adgangspunkt' => ['koordinater' => ['x' => 695000.0, 'y' => 6170000.0]],
            ],
        ],
    ];
}

/**
 * Fakes the API: a search answering with $results, and lookups for Hersegade 18 and 20 in Roskilde.
 *
 * @param  list<array{id: string, type: string, titel: string}>  $results
 */
function fakeAddressApi(array $results): void
{
    Http::fake([
        'adressevaelger.dk/adresser/soeg*' => Http::response(['status' => 'ok', 'fund' => $results]),
        'adressevaelger.dk/adresser/'.HERSEGADE_ID.'*' => Http::response(addressLookup(HERSEGADE_ID, 'Hersegade', '18', '4000', 'Roskilde')),
        'adressevaelger.dk/adresser/'.HERSEGADE_NEIGHBOUR_ID.'*' => Http::response(addressLookup(HERSEGADE_NEIGHBOUR_ID, 'Hersegade', '20', '4000', 'Roskilde')),
    ]);
}
