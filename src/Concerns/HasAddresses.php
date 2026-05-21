<?php

namespace Bazuka\FilamentAddress\Concerns;

use Bazuka\FilamentAddress\Models\Address;
use Bazuka\FilamentAddress\Services\AddressService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasAddresses
{
    /**
     * All addresses attached to this model.
     */
    public function addresses(): MorphMany
    {
        $model = Address::getModel();

        return $this->morphMany($model, 'addressable');
    }

    /**
     * The primary (first) address attached to this model.
     */
    public function address(): MorphOne
    {
        $model = Address::getModel();

        return $this->morphOne($model, 'addressable');
    }

    /**
     * Create or update a manually entered address, clearing all structured fields so the
     * nightly normalization command can fill them in later.
     */
    public function addManualAddress(string $formatted, ?string $label = null): Address
    {
        $model = Address::getModel();

        $existing = $this->addresses()->where('label', $label)->first();

        if ($existing) {
            $existing->update([
                'formatted_address' => $formatted,
                'source_id' => null,
                'street_name' => null,
                'house_number' => null,
                'floor' => null,
                'door' => null,
                'postal_code' => null,
                'city' => null,
                'municipality_code' => null,
                'longitude' => null,
                'latitude' => null,
                'access_address_id' => null,
            ]);

            return $existing->fresh();
        }

        /** @var Address $address */
        $address = $this->addresses()->create([
            'formatted_address' => $formatted,
            'label' => $label,
        ]);

        return $address;
    }

    /**
     * Create or update an address from a search fund item (type=adresse).
     *
     * @param  array<string, mixed>  $suggestion
     */
    public function addAddress(array $suggestion, ?string $label = null): Address
    {
        $model = Address::getModel();

        $adresse = AddressService::addressById($suggestion['id']);

        if (! $adresse) {
            return $this->addManualAddress($suggestion['titel'] ?? '', $label);
        }

        $attributes = $model::attributesFromSuggestion($adresse);

        /** @var Address $address */
        $address = $this->addresses()->updateOrCreate(
            ['source_id' => $attributes['source_id'], 'label' => $label],
            array_merge($attributes, ['label' => $label])
        );

        return $address;
    }
}
