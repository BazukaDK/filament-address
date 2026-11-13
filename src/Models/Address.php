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
        'type',
        'source_id',
        'normalization_attempted_at',
        'formatted_address',
        'street_name',
        'house_number',
        'floor',
        'door',
        'postal_code',
        'city',
        'supplementary_city',
        'longitude',
        'latitude',
    ];

    protected function casts(): array
    {
        return [
            'longitude' => 'float',
            'latitude' => 'float',
            'normalization_attempted_at' => 'datetime',
        ];
    }

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Map an address lookup response (from AddressService::addressById) to Address attribute values.
     *
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>
     */
    public static function attributesFromSuggestion(array $address): array
    {
        return [
            'source_id' => $address['id'] ?? null,
            'formatted_address' => $address['full_address'] ?? null,
            'street_name' => $address['street_name'] ?? null,
            'house_number' => $address['street_number'] ?? null,
            'floor' => $address['floor'] ?? null,
            'door' => $address['door'] ?? null,
            'postal_code' => $address['zip_code'] ?? null,
            'city' => $address['city'] ?? null,
            'supplementary_city' => $address['supplementary_city'] ?? null,
            'longitude' => $address['coordinates']['lng'] ?? null,
            'latitude' => $address['coordinates']['lat'] ?? null,
        ];
    }

    /**
     * Build a formatted address string from raw field data.
     *
     * @param  array{street_name?: string|null, house_number?: string|null, postal_code?: string|null, city?: string|null}  $data
     */
    public static function formatFromFields(array $data): string
    {
        $streetLine = trim(($data['street_name'] ?? '').' '.($data['house_number'] ?? ''));
        $cityLine = trim(($data['postal_code'] ?? '').' '.($data['city'] ?? ''));

        return implode(', ', array_filter([$streetLine, $cityLine]));
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
