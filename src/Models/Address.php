<?php

namespace Bazuka\FilamentDawa\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'dawa_addresses';

    protected $fillable = [
        'dawa_id',
        'tekst',
        'vejnavn',
        'husnr',
        'etage',
        'dør',
        'postnr',
        'postnrnavn',
        'kommunekode',
        'longitude',
        'latitude',
        'adgangsadresse_id',
    ];

    protected function casts(): array
    {
        return [
            'longitude' => 'float',
            'latitude' => 'float',
        ];
    }

    /**
     * Create or update an Address from a DAWA autocomplete suggestion.
     *
     * @param  array<string, mixed>  $suggestion  A single item from the DAWA autocomplete response
     */
    public static function fromDawa(array $suggestion): static
    {
        $data = $suggestion['data'];

        /** @var static $address */
        $address = static::updateOrCreate(
            ['dawa_id' => $data['id']],
            [
                'tekst' => $suggestion['tekst'],
                'vejnavn' => $data['vejnavn'],
                'husnr' => $data['husnr'],
                'etage' => $data['etage'] ?? null,
                'dør' => $data['dør'] ?? null,
                'postnr' => $data['postnr'],
                'postnrnavn' => $data['postnrnavn'],
                'kommunekode' => $data['kommunekode'],
                'longitude' => $data['x'] ?? null,
                'latitude' => $data['y'] ?? null,
                'adgangsadresse_id' => $data['adgangsadresseid'] ?? null,
            ]
        );

        return $address;
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
