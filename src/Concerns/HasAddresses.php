<?php

namespace Bazuka\FilamentDawa\Concerns;

use Bazuka\FilamentDawa\Models\Address;
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
     * Create or update an address from a DAWA autocomplete suggestion.
     *
     * @param  array<string, mixed>  $suggestion
     */
    public function addAddress(array $suggestion, ?string $label = null): Address
    {
        $model = Address::getModel();
        $attributes = $model::attributesFromDawa($suggestion);

        /** @var Address $address */
        $address = $this->addresses()->updateOrCreate(
            ['dawa_id' => $attributes['dawa_id'], 'label' => $label],
            array_merge($attributes, ['label' => $label])
        );

        return $address;
    }
}
