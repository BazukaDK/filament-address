<?php

namespace Bazuka\FilamentDawa\Forms\Components;

use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Model;

class DawaInput extends Field
{
    use HasPlaceholder;

    protected string $view = 'filament-dawa::forms.components.dawa-input';

    protected int $suggestionCount = 10;

    protected function setUp(): void
    {
        parent::setUp();

        // Do not try to set this as a plain attribute on the model.
        $this->dehydrated(false);

        // When editing, populate the display value from the existing address relationship.
        $this->afterStateHydrated(function (DawaInput $component, ?Model $record): void {
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

            // State is an array only when the user selected a new suggestion.
            // A plain string means the existing address was left unchanged.
            if (! is_array($state) || empty($state)) {
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
