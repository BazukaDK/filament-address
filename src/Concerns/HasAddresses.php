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
     * nightly normalization command can fill them in later. Blank text is ignored.
     */
    public function addManualAddress(string $formatted, ?string $type = null): ?Address
    {
        $formatted = trim($formatted);

        if ($formatted === '') {
            return null;
        }

        return $this->saveAddress($type, [
            ...$this->emptyAddressAttributes(),
            'formatted_address' => $formatted,
        ]);
    }

    /**
     * Create or update an address from raw field data, attempting API normalization immediately.
     * Falls back to storing the raw fields if the lookup fails; the nightly command will retry.
     * Returns null when the fields hold no address at all.
     *
     * @param  array{street_name?: string|null, house_number?: string|null, postal_code?: string|null, city?: string|null}  $data
     */
    public function addRawAddress(array $data, ?string $type = null): ?Address
    {
        $model = Address::getModel();

        $formatted = $model::formatFromFields($data);

        if ($formatted === '') {
            return null;
        }

        $suggestion = AddressService::bestAddressMatch($formatted, [
            'postal_code' => $data['postal_code'] ?? null,
            'house_number' => $data['house_number'] ?? null,
        ]);

        if ($suggestion && ! empty($suggestion['id'])) {
            return $this->saveAddress($type, [
                ...$model::attributesFromSuggestion($suggestion),
                'normalization_attempted_at' => null,
            ]);
        }

        return $this->saveAddress($type, [
            ...$this->emptyAddressAttributes(),
            'formatted_address' => $formatted,
            'street_name' => $data['street_name'] ?? null,
            'house_number' => $data['house_number'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'city' => $data['city'] ?? null,
        ]);
    }

    /**
     * Create or update an address from a search fund item (type=adresse). When the address can't be
     * looked up, its text is kept as a manual address. Returns null when there is nothing to keep.
     *
     * @param  array<string, mixed>  $suggestion
     */
    public function addAddress(array $suggestion, ?string $type = null): ?Address
    {
        $model = Address::getModel();

        $address = AddressService::addressById((string) ($suggestion['id'] ?? ''));

        if (! $address) {
            return $this->addManualAddress((string) ($suggestion['titel'] ?? ''), $type);
        }

        return $this->saveAddress($type, [
            ...$model::attributesFromSuggestion($address),
            'normalization_attempted_at' => null,
        ]);
    }

    /**
     * Updates the model's address of the given type, or creates it when there is none.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function saveAddress(?string $type, array $attributes): Address
    {
        $existing = $this->addresses()->where('type', $type)->first();

        if ($existing) {
            $existing->update($attributes);

            return $existing->fresh();
        }

        /** @var Address $address */
        $address = $this->addresses()->create([...$attributes, 'type' => $type]);

        return $address;
    }

    /**
     * Attributes that clear every structured field, so the nightly command normalizes the address again.
     *
     * @return array<string, null>
     */
    private function emptyAddressAttributes(): array
    {
        return [
            'source_id' => null,
            'normalization_attempted_at' => null,
            'street_name' => null,
            'house_number' => null,
            'floor' => null,
            'door' => null,
            'postal_code' => null,
            'city' => null,
            'supplementary_city' => null,
            'longitude' => null,
            'latitude' => null,
        ];
    }
}
