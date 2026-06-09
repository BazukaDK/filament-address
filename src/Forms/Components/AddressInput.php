<?php

namespace Bazuka\FilamentAddress\Forms\Components;

use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Model;

class AddressInput extends Field
{
    use HasPlaceholder;

    protected string $view = 'filament-address::forms.components.address-input';

    protected int $suggestionCount = 10;

    protected ?string $addressType = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);

        $this->afterStateHydrated(function (AddressInput $component, ?Model $record): void {
            if (! $record || ! method_exists($record, 'addresses')) {
                return;
            }

            $address = $record->addresses()->where('type', $component->addressType)->first();
            $component->state($address?->formatted_address);
        });

        $this->saveRelationshipsUsing(function (?Model $record): void {
            if (! $record || ! method_exists($record, 'addAddress')) {
                return;
            }

            $state = $this->getState();

            // State is an array when the user made a new selection.
            // A plain string means the existing address was left unchanged.
            if (! is_array($state) || empty($state)) {
                return;
            }

            if ($state['manual'] ?? false) {
                $record->addManualAddress($state['tekst'], $this->addressType);

                return;
            }

            $record->addAddress($state, $this->addressType);
        });
    }

    public function addressType(?string $type): static
    {
        $this->addressType = $type;

        return $this;
    }

    public function getAddressType(): ?string
    {
        return $this->addressType;
    }

    public function suggestionCount(int $count): static
    {
        $this->suggestionCount = $count;

        return $this;
    }

    public function getSuggestionCount(): int
    {
        return $this->suggestionCount;
    }
}
