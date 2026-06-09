<?php

namespace Bazuka\FilamentAddress\Services;

use Illuminate\Support\Facades\Http;

class AddressService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function addressAutocomplete(string $address, int $suggestionCount = 10): array
    {
        $response = Http::timeout(5)
            ->get('https://adressevaelger.dk/adresser/soeg', [
                'tekst' => $address,
                'maksimum' => $suggestionCount,
                'token' => config('filament-address.api_token'),
            ]);

        $data = $response->json();

        return ($data['status'] ?? null) === 'ok' ? ($data['fund'] ?? []) : [];
    }

    /**
     * @return array{id: string|null, full_address: string|null, street_name: string|null, street_number: string|null, floor: string|null, door: string|null, zip_code: string|null, city: string|null, supplementary_city: string|null, coordinates: array{lng: float, lat: float}|null}|null
     */
    public static function addressById(string $id): ?array
    {
        $response = Http::timeout(5)
            ->get("https://adressevaelger.dk/adresser/{$id}", [
                'token' => config('filament-address.api_token'),
            ]);

        $data = $response->json();

        if (($data['status'] ?? null) !== 'ok') {
            return null;
        }

        $address = $data['adresse'] ?? null;

        if (! $address) {
            return null;
        }

        $utm = $address['husnummer']['adgangspunkt']['koordinater'] ?? null;
        $address['coordinates'] = $utm ? static::utm32nToWgs84($utm['x'], $utm['y']) : null;

        return static::normalizeAddress($address);
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array{id: string|null, full_address: string|null, street_name: string|null, street_number: string|null, floor: string|null, door: string|null, zip_code: string|null, city: string|null, supplementary_city: string|null, coordinates: array{lng: float, lat: float}|null}
     */
    private static function normalizeAddress(array $address): array
    {
        $houseNumber = $address['husnummer'] ?? [];

        return [
            'id' => $address['id_lokalid'] ?? null,
            'full_address' => $address['adressebetegnelse'] ?? null,
            'street_name' => $houseNumber['vejnavn'] ?? null,
            'street_number' => $houseNumber['husnummertekst'] ?? null,
            'floor' => $address['etagebetegnelse'] ?? null,
            'door' => $address['doerbetegnelse'] ?? null,
            'zip_code' => $houseNumber['postnummer']['postnr'] ?? null,
            'city' => $houseNumber['postnummer']['navn'] ?? null,
            'supplementary_city' => $houseNumber['supplerendebynavn']['navn'] ?? null,
            'coordinates' => $address['coordinates'] ?? null,
        ];
    }

    /**
     * Convert EPSG:25832 (UTM zone 32N) easting/northing to WGS84 decimal degrees.
     *
     * @return array{lng: float, lat: float}
     */
    private static function utm32nToWgs84(float $easting, float $northing): array
    {
        $a = 6378137.0;
        $e2 = 0.00669437999014;
        $k0 = 0.9996;

        $x = $easting - 500000.0;
        $y = $northing;

        $e1 = (1 - sqrt(1 - $e2)) / (1 + sqrt(1 - $e2));

        $M = $y / $k0;
        $mu = $M / ($a * (1 - $e2 / 4 - 3 * $e2 ** 2 / 64 - 5 * $e2 ** 3 / 256));

        $phi1 = $mu
            + (3 * $e1 / 2 - 27 * $e1 ** 3 / 32) * sin(2 * $mu)
            + (21 * $e1 ** 2 / 16 - 55 * $e1 ** 4 / 32) * sin(4 * $mu)
            + (151 * $e1 ** 3 / 96) * sin(6 * $mu)
            + (1097 * $e1 ** 4 / 512) * sin(8 * $mu);

        $N1 = $a / sqrt(1 - $e2 * sin($phi1) ** 2);
        $T1 = tan($phi1) ** 2;
        $C1 = $e2 * cos($phi1) ** 2 / (1 - $e2);
        $R1 = $a * (1 - $e2) / (1 - $e2 * sin($phi1) ** 2) ** 1.5;
        $D = $x / ($N1 * $k0);

        $lat = $phi1 - ($N1 * tan($phi1) / $R1) * (
            $D ** 2 / 2
            - (5 + 3 * $T1 + 10 * $C1 - 4 * $C1 ** 2 - 9 * $e2) * $D ** 4 / 24
            + (61 + 90 * $T1 + 298 * $C1 + 45 * $T1 ** 2 - 252 * $e2 - 3 * $C1 ** 2) * $D ** 6 / 720
        );

        $lon0 = deg2rad(9.0);
        $lon = $lon0 + (
            $D
            - (1 + 2 * $T1 + $C1) * $D ** 3 / 6
            + (5 - 2 * $C1 + 28 * $T1 - 3 * $C1 ** 2 + 8 * $e2 + 24 * $T1 ** 2) * $D ** 5 / 120
        ) / cos($phi1);

        return [
            'lng' => rad2deg($lon),
            'lat' => rad2deg($lat),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function bestAddressMatch(string $address): ?array
    {
        $results = static::addressAutocomplete($address, 1);

        $first = collect($results)->firstWhere('type', 'adresse');

        if (! $first) {
            return null;
        }

        return static::addressById($first['id']);
    }
}
