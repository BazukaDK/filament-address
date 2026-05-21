<?php

return [

    /*
     * The model used to store addresses. You may swap this for your own
     * model as long as it extends Bazuka\FilamentAddress\Models\Address.
     */
    'address_model' => Bazuka\FilamentAddress\Models\Address::class,

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

];
