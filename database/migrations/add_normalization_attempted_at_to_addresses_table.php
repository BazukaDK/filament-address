<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('filament-address.table', 'addresses'), function (Blueprint $table) {
            // When normalization last failed to find a match, so the address waits before it is tried again.
            $table->timestamp('normalization_attempted_at')->nullable()->after('source_id');
        });
    }

    public function down(): void
    {
        Schema::table(config('filament-address.table', 'addresses'), function (Blueprint $table) {
            $table->dropColumn('normalization_attempted_at');
        });
    }
};
