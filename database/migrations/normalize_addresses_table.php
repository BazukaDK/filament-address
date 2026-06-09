<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('filament-address.table', 'addresses'), function (Blueprint $table) {
            $table->dropColumn(['municipality_code', 'access_address_id']);
            $table->string('supplementary_city')->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table(config('filament-address.table', 'addresses'), function (Blueprint $table) {
            $table->dropColumn('supplementary_city');
            $table->string('municipality_code', 10)->nullable();
            $table->string('access_address_id', 36)->nullable();
        });
    }
};
