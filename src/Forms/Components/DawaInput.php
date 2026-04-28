<?php

namespace Bazuka\FilamentDawa\Forms\Components;

use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;

class DawaInput extends Field
{
    use HasPlaceholder;

    protected string $view = 'filament-dawa::forms.components.dawa-input';

    protected int $suggestionCount = 10;

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
