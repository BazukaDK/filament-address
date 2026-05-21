<?php

namespace Bazuka\FilamentAddress\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Address extends Model
{
    public function getTable(): string
    {
        return config('filament-address.table', 'addresses');
    }

    protected $fillable = [
        'addressable_id',
        'addressable_type',
        'label',
        'source_id',
        'formatted_address',
        'street_name',
        'house_number',
        'floor',
        'door',
        'postal_code',
        'city',
        'municipality_code',
        'longitude',
        'latitude',
        'access_address_id',
    ];

    protected function casts(): array
    {
        return [
            'longitude' => 'float',
            'latitude' => 'float',
        ];
    }

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Map an address lookup response (from AddressService::addressById) to Address attribute values.
     *
     * @param  array<string, mixed>  $adresse
     * @return array<string, mixed>
     */
    public static function attributesFromSuggestion(array $adresse): array
    {
        $husnummer = $adresse['husnummer'] ?? [];

        return [
            'source_id' => $adresse['id_lokalid'] ?? null,
            'formatted_address' => $adresse['adressebetegnelse'] ?? null,
            'street_name' => $husnummer['vejnavn'] ?? null,
            'house_number' => $husnummer['husnummertekst'] ?? null,
            'floor' => $adresse['etagebetegnelse'] ?? null,
            'door' => $adresse['doerbetegnelse'] ?? null,
            'postal_code' => $husnummer['postnummer']['postnr'] ?? null,
            'city' => $husnummer['postnummer']['navn'] ?? null,
            'municipality_code' => $husnummer['navngivenvejkommunedel']['kommune'] ?? null,
            'longitude' => null,
            'latitude' => null,
            'access_address_id' => $husnummer['id_lokalid'] ?? null,
        ];
    }

    public static function cleanText(string $text): string
    {
        $parts = array_filter(array_map('trim', explode(',', $text)));

        return implode(', ', $parts);
    }

    /**
     * Resolve the configured address model class.
     *
     * @return class-string<static>
     */
    public static function getModel(): string
    {
        /** @var class-string<static> $model */
        $model = config('filament-address.address_model', static::class);

        return $model;
    }
}
