<?php

namespace Bazuka\FilamentAddress\Forms\Components;

use Bazuka\FilamentAddress\Services\AddressService;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Renderless;

class AddressInput extends Field
{
    use HasPlaceholder;

    protected string $view = 'filament-address::forms.components.address-input';

    protected int $suggestionCount = 10;

    protected ?string $addressType = null;

    /**
     * Searches per minute one session may make, so the field can't be used to drain the API token's quota.
     */
    public const SEARCHES_PER_MINUTE = 120;

    private const MAX_QUERY_LENGTH = 200;

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
                $record->addManualAddress((string) ($state['tekst'] ?? ''), $this->addressType);

                return;
            }

            $record->addAddress($state, $this->addressType);
        });
    }

    /**
     * Address suggestions for the browser. The search runs on the server, so the API token is never sent
     * to the browser, and only the fields the dropdown needs are returned.
     *
     * @return array{ok: bool, suggestions: list<array{id: mixed, type: mixed, titel: mixed}>}
     */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function searchAddresses(string $query): array
    {
        $query = trim(mb_substr($query, 0, self::MAX_QUERY_LENGTH));

        if (mb_strlen($query) < 2) {
            return ['ok' => true, 'suggestions' => []];
        }

        $rateLimitKey = 'filament-address-search:'.(session()->getId() ?: request()->ip());

        if (! RateLimiter::attempt($rateLimitKey, self::SEARCHES_PER_MINUTE, fn (): bool => true)) {
            return ['ok' => false, 'suggestions' => []];
        }

        $suggestions = AddressService::search($query, $this->getSuggestionCount());

        if ($suggestions === null) {
            return ['ok' => false, 'suggestions' => []];
        }

        return [
            'ok' => true,
            'suggestions' => array_map(fn (array $suggestion): array => [
                'id' => $suggestion['id'] ?? null,
                'type' => $suggestion['type'] ?? null,
                'titel' => $suggestion['titel'] ?? '',
            ], $suggestions),
        ];
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
