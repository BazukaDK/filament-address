<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('filament-address.table', 'addresses');

        if (Schema::hasColumn($table, 'label')) {
            Schema::table($table, function (Blueprint $table) {
                $table->renameColumn('label', 'type');
            });
        }
    }

    public function down(): void
    {
        $table = config('filament-address.table', 'addresses');

        if (Schema::hasColumn($table, 'type')) {
            Schema::table($table, function (Blueprint $table) {
                $table->renameColumn('type', 'label');
            });
        }
    }
};
