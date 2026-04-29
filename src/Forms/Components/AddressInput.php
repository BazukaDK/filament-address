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

    protected function setUp(): void
    {
        parent::setUp();

        // Do not try to set this as a plain attribute on the model.
        $this->dehydrated(false);

        // When editing, populate the display value from the existing address relationship.
        $this->afterStateHydrated(function (AddressInput $component, ?Model $record): void {
            if (! $record || ! method_exists($record, 'address')) {
                return;
            }

            $component->state($record->address?->formatted_address);
        });

        // After the record is saved, persist the selected suggestion via HasAddresses.
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
                $record->addManualAddress($state['tekst']);

                return;
            }

            $record->addAddress($state);
        });
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
