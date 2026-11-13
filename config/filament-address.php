<?php

use Bazuka\FilamentAddress\Models\Address;

return [

    /*
     * The model used to store addresses. You may swap this for your own
     * model as long as it extends Bazuka\FilamentAddress\Models\Address.
     */
    'address_model' => Address::class,

    /*
     * The database table name used to store addresses. Change this before
     * running migrations if "addresses" conflicts with an existing table.
     */
    'table' => 'addresses',

    /*
     * The API token for the Adressevælger API.
     * Obtain a token from https://adressevaelger.dk or your administrator.
     */
    'api_token' => env('FILAMENT_ADDRESS_API_TOKEN', ''),

    /*
     * The address API. The token above is only ever sent from the server, never to the browser.
     */
    'base_url' => env('FILAMENT_ADDRESS_BASE_URL', 'https://adressevaelger.dk'),

    /*
     * Seconds to wait for the address API before giving up. A failed lookup never stops a save:
     * the address is kept as entered and normalized later.
     */
    'timeout' => 5,

    /*
     * When filament-address:normalize runs every day (24-hour "HH:MM", app timezone).
     * Set to null to leave scheduling to your own application.
     */
    'normalize_at' => env('FILAMENT_ADDRESS_NORMALIZE_AT', '02:00'),

    /*
     * Days before an address that could not be matched is tried again, so the
     * addresses that never match don't block the rest of the queue.
     */
    'normalize_retry_after_days' => 7,

];
