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

];
