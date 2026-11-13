<?php

namespace Bazuka\FilamentAddress\Commands;

use Bazuka\FilamentAddress\Models\Address;
use Bazuka\FilamentAddress\Services\AddressService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class NormalizeAddressesCommand extends Command
{
    protected $signature = 'filament-address:normalize {--limit=100 : Maximum number of addresses to process}';

    protected $description = 'Normalize manually entered addresses using the Adressevælger API';

    public function handle(): int
    {
        $model = Address::getModel();
        $retryAfterDays = (int) config('filament-address.normalize_retry_after_days', 7);

        // Addresses never tried come first; those that failed wait before they are tried again,
        // so addresses that never match can't keep the others from being normalized.
        $addresses = $model::query()
            ->whereNull('source_id')
            ->where(fn (Builder $query) => $query
                ->whereNull('normalization_attempted_at')
                ->orWhere('normalization_attempted_at', '<=', now()->subDays($retryAfterDays)))
            ->orderByRaw('normalization_attempted_at is not null')
            ->orderBy('normalization_attempted_at')
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($addresses->isEmpty()) {
            $this->info('No addresses to normalize.');

            return self::SUCCESS;
        }

        $normalized = 0;
        $failed = 0;

        foreach ($addresses as $address) {
            $suggestion = AddressService::bestAddressMatch($address->formatted_address, [
                'postal_code' => $address->postal_code,
                'house_number' => $address->house_number,
            ]);

            if ($suggestion && ! empty($suggestion['id'])) {
                $address->update([...$model::attributesFromSuggestion($suggestion), 'normalization_attempted_at' => null]);
                $normalized++;
            } else {
                $address->update(['normalization_attempted_at' => now()]);
                $failed++;
            }
        }

        $this->info("Normalized: {$normalized}. Could not match: {$failed}.");

        return self::SUCCESS;
    }
}
