<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('filament-address.table', 'addresses'), function (Blueprint $table) {
            $table->id();

            // Polymorphic owner — attach to any model via HasAddresses trait
            $table->morphs('addressable');

            // Optional label to distinguish multiple addresses on one model (e.g. 'billing', 'shipping')
            $table->string('label')->nullable();

            // External API identifier — null for manually entered addresses pending normalization
            $table->string('source_id', 36)->nullable()->index();

            // Full formatted address string (e.g. "Hersegade 18, 1. th, 4000 Roskilde")
            $table->string('formatted_address');

            // Street
            $table->string('street_name')->nullable();
            $table->string('house_number', 10)->nullable();
            $table->string('floor', 10)->nullable();
            $table->string('door', 10)->nullable();

            // Postal
            $table->string('postal_code', 10)->nullable();
            $table->string('city')->nullable();

            // Municipality
            $table->string('municipality_code', 10)->nullable();

            // Coordinates (WGS84)
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();

            // DAWA access address reference
            $table->string('access_address_id', 36)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('filament-address.table', 'addresses'));
    }
};
