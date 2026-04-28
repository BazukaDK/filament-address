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
