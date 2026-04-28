<?php

namespace Bazuka\FilamentDawa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Address extends Model
{
    protected $table = 'dawa_addresses';

    protected $fillable = [
        'addressable_id',
        'addressable_type',
        'label',
        'dawa_id',
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
     * Map a DAWA autocomplete suggestion to Address attribute values.
     *
     * @param  array<string, mixed>  $suggestion
     * @return array<string, mixed>
     */
    public static function attributesFromDawa(array $suggestion): array
    {
        $data = $suggestion['data'];

        return [
            'dawa_id' => $data['id'],
            'formatted_address' => $suggestion['tekst'],
            'street_name' => $data['vejnavn'],
            'house_number' => $data['husnr'],
            'floor' => $data['etage'] ?? null,
            'door' => $data['dør'] ?? null,
            'postal_code' => $data['postnr'],
            'city' => $data['postnrnavn'],
            'municipality_code' => $data['kommunekode'],
            'longitude' => $data['x'] ?? null,
            'latitude' => $data['y'] ?? null,
            'access_address_id' => $data['adgangsadresseid'] ?? null,
        ];
    }

    /**
     * Resolve the configured address model class.
     *
     * @return class-string<static>
     */
    public static function getModel(): string
    {
        /** @var class-string<static> $model */
        $model = config('dawa.address_model', static::class);

        return $model;
    }
}
