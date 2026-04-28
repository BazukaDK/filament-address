<?php

namespace Bazuka\FilamentDawa\Services;

use Illuminate\Support\Facades\Http;

class DawaService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function addressAutocomplete(string $address, int $suggestionCount = 10): array
    {
        $response = Http::timeout(5)
            ->get('https://api.dataforsyningen.dk/autocomplete', [
                'fuzzy' => '',
                'type' => 'adresse',
                'q' => $address,
                'per_side' => $suggestionCount,
            ]);

        return $response->json() ?? [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function bestAddressMatch(string $address): ?array
    {
        $results = static::addressAutocomplete($address, 1);

        return $results[0] ?? null;
    }
}
