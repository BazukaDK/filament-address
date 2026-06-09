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
    public function addManualAddress(string $formatted, ?string $type = null): Address
    {
        $model = Address::getModel();

        $existing = $this->addresses()->where('type', $type)->first();

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
                'supplementary_city' => null,
                'longitude' => null,
                'latitude' => null,
            ]);

            return $existing->fresh();
        }

        /** @var Address $address */
        $address = $this->addresses()->create([
            'formatted_address' => $formatted,
            'type' => $type,
        ]);

        return $address;
    }

    /**
     * Create or update an address from raw field data, attempting API normalization immediately.
     * Falls back to storing the raw fields if the lookup fails; the nightly command will retry.
     *
     * @param  array{street_name?: string|null, house_number?: string|null, postal_code?: string|null, city?: string|null}  $data
     */
    public function addRawAddress(array $data, ?string $type = null): Address
    {
        $model = Address::getModel();

        $formatted = $model::formatFromFields($data);

        $suggestion = AddressService::bestAddressMatch($formatted);

        if ($suggestion && ! empty($suggestion['id'])) {
            $attributes = $model::attributesFromSuggestion($suggestion);
            $existing = $this->addresses()->where('type', $type)->first();

            if ($existing) {
                $existing->update(array_merge($attributes, ['type' => $type]));

                return $existing->fresh();
            }

            /** @var Address $address */
            $address = $this->addresses()->create(array_merge($attributes, ['type' => $type]));

            return $address;
        }

        $rawAttributes = [
            'formatted_address' => $formatted ?: 'N/A',
            'source_id' => null,
            'street_name' => $data['street_name'] ?? null,
            'house_number' => $data['house_number'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'city' => $data['city'] ?? null,
            'supplementary_city' => null,
            'longitude' => null,
            'latitude' => null,
        ];

        $existing = $this->addresses()->where('type', $type)->first();

        if ($existing) {
            $existing->update($rawAttributes);

            return $existing->fresh();
        }

        /** @var Address $address */
        $address = $this->addresses()->create(array_merge($rawAttributes, ['type' => $type]));

        return $address;
    }

    /**
     * Create or update an address from a search fund item (type=adresse).
     *
     * @param  array<string, mixed>  $suggestion
     */
    public function addAddress(array $suggestion, ?string $type = null): Address
    {
        $model = Address::getModel();

        $address = AddressService::addressById($suggestion['id']);

        if (! $address) {
            return $this->addManualAddress($suggestion['titel'] ?? '', $type);
        }

        $attributes = $model::attributesFromSuggestion($address);
        $existing = $this->addresses()->where('type', $type)->first();

        if ($existing) {
            $existing->update(array_merge($attributes, ['type' => $type]));

            return $existing->fresh();
        }

        /** @var Address $address */
        $address = $this->addresses()->create(array_merge($attributes, ['type' => $type]));

        return $address;
    }
}
