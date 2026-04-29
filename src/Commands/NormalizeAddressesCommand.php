<?php

namespace Bazuka\FilamentAddress\Commands;

use Bazuka\FilamentAddress\Models\Address;
use Bazuka\FilamentAddress\Services\AddressService;
use Illuminate\Console\Command;

class NormalizeAddressesCommand extends Command
{
    protected $signature = 'filament-address:normalize {--limit=100 : Maximum number of addresses to process}';

    protected $description = 'Normalize manually entered addresses using the DAWA API';

    public function handle(): int
    {
        $model = Address::getModel();

        $addresses = $model::whereNull('dawa_id')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($addresses->isEmpty()) {
            $this->info('No addresses to normalize.');

            return self::SUCCESS;
        }

        $normalized = 0;
        $failed = 0;

        foreach ($addresses as $address) {
            $suggestion = AddressService::bestAddressMatch($address->formatted_address);

            if ($suggestion && ! empty($suggestion['data']['id'])) {
                $address->update($model::attributesFromSuggestion($suggestion));
                $normalized++;
            } else {
                $failed++;
            }
        }

        $this->info("Normalized: {$normalized}. Could not match: {$failed}.");

        return self::SUCCESS;
    }
}
