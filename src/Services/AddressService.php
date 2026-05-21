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
     * @return array<string, mixed>|null
     */
    public static function addressById(string $id): ?array
    {
        $response = Http::timeout(5)
            ->get("https://adressevaelger.dk/adresser/{$id}", [
                'token' => config('filament-address.api_token'),
            ]);

        $data = $response->json();

        return ($data['status'] ?? null) === 'ok' ? ($data['adresse'] ?? null) : null;
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
