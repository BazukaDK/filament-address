<?php

namespace Bazuka\FilamentAddress\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AddressService
{
    /**
     * How many search results bestAddressMatch() looks through, and how many of them it looks up in full.
     */
    private const MATCH_SEARCH_COUNT = 10;

    private const MATCH_LOOKUP_COUNT = 3;

    /**
     * Search for addresses. Returns an empty list when the API is unreachable or answers with an error.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function addressAutocomplete(string $address, int $suggestionCount = 10): array
    {
        return static::search($address, $suggestionCount) ?? [];
    }

    /**
     * Search for addresses. Unlike addressAutocomplete(), returns null when the API is unreachable or
     * answers with an error, so callers can tell "no results" from "lookup unavailable".
     *
     * @return array<int, array<string, mixed>>|null
     */
    public static function search(string $address, int $suggestionCount = 10): ?array
    {
        $data = static::get('adresser/soeg', [
            'tekst' => $address,
            'maksimum' => $suggestionCount,
        ])?->json();

        if (($data['status'] ?? null) !== 'ok') {
            return null;
        }

        return array_values(array_filter($data['fund'] ?? [], 'is_array'));
    }

    /**
     * Look up one address. Returns null when the id is not a valid address id, the API is unreachable
     * or the address is not found.
     *
     * @return array{id: string|null, full_address: string|null, street_name: string|null, street_number: string|null, floor: string|null, door: string|null, zip_code: string|null, city: string|null, supplementary_city: string|null, coordinates: array{lng: float, lat: float}|null}|null
     */
    public static function addressById(string $id): ?array
    {
        // The id can come from the browser, so only well-formed ids may become part of the request path.
        if (! Str::isUuid($id)) {
            return null;
        }

        $data = static::get("adresser/{$id}")?->json();

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
     * Find the address that matches a free-text address. A candidate is only accepted when its postal code
     * and house number agree with those in the text (or in $expected), so a near miss is never saved as a match.
     *
     * @param  array{postal_code?: string|null, house_number?: string|null}  $expected
     * @return array{id: string|null, full_address: string|null, street_name: string|null, street_number: string|null, floor: string|null, door: string|null, zip_code: string|null, city: string|null, supplementary_city: string|null, coordinates: array{lng: float, lat: float}|null}|null
     */
    public static function bestAddressMatch(string $address, array $expected = []): ?array
    {
        if (blank($address)) {
            return null;
        }

        $expected = [
            'postal_code' => filled($expected['postal_code'] ?? null) ? $expected['postal_code'] : static::postalCodeFrom($address),
            'house_number' => filled($expected['house_number'] ?? null) ? $expected['house_number'] : static::houseNumberFrom($address),
        ];

        $candidates = collect(static::addressAutocomplete($address, self::MATCH_SEARCH_COUNT))
            ->where('type', 'adresse')
            ->take(self::MATCH_LOOKUP_COUNT);

        foreach ($candidates as $candidate) {
            $match = static::addressById((string) ($candidate['id'] ?? ''));

            if ($match && static::agreesWith($match, $expected)) {
                return $match;
            }
        }

        return null;
    }

    /**
     * Sends a GET request to the API. Returns null instead of throwing when the API cannot be reached,
     * so a slow or broken address lookup never stops the caller from saving its data.
     *
     * @param  array<string, mixed>  $query
     */
    protected static function get(string $path, array $query = []): ?Response
    {
        try {
            return static::client()->get($path, [...$query, 'token' => config('filament-address.api_token')]);
        } catch (Throwable $exception) {
            Log::warning('Address lookup failed.', ['exception' => $exception->getMessage()]);

            return null;
        }
    }

    protected static function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('filament-address.base_url', 'https://adressevaelger.dk'), '/'))
            ->timeout((int) config('filament-address.timeout', 5))
            ->acceptJson();
    }

    /**
     * @param  array<string, mixed>  $match
     * @param  array{postal_code: string|null, house_number: string|null}  $expected
     */
    private static function agreesWith(array $match, array $expected): bool
    {
        if (filled($expected['postal_code']) && (string) $match['zip_code'] !== (string) $expected['postal_code']) {
            return false;
        }

        if (filled($expected['house_number']) && mb_strtolower((string) $match['street_number']) !== mb_strtolower(str_replace(' ', '', (string) $expected['house_number']))) {
            return false;
        }

        return true;
    }

    /**
     * The four-digit Danish postal code in a free-text address, e.g. "4000" in "Hersegade 18, 4000 Roskilde".
     */
    private static function postalCodeFrom(string $address): ?string
    {
        return preg_match('/\b(\d{4})\b(?=\s+\p{L})/u', $address, $matches) ? $matches[1] : null;
    }

    /**
     * The house number in a free-text address, e.g. "18B" in "Hersegade 18B, 1. th, 4000 Roskilde".
     */
    private static function houseNumberFrom(string $address): ?string
    {
        $streetLine = trim(Str::before($address, ','));

        return preg_match('/\p{L}[\p{L}.\'\s-]*\s(\d{1,3}\s?\p{L}?)$/u', $streetLine, $matches)
            ? str_replace(' ', '', $matches[1])
            : null;
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
}
