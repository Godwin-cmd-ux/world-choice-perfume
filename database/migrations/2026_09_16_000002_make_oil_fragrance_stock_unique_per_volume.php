<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Oil fragrances can be stocked in multiple bottle volumes (e.g. Reef 33 in
     * 500ml and 1000ml bottles). Track quantity per (branch, name, volume) so a
     * stock-out of one volume never touches the other volume's count.
     */
    public function up(): void
    {
        if (Schema::hasTable('oil_fragrance_stock')) {
            Schema::table('oil_fragrance_stock', function (Blueprint $table) {
                $table->dropUnique(['branch_id', 'name']);
                $table->unique(['branch_id', 'name', 'volume']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('oil_fragrance_stock')) {
            Schema::table('oil_fragrance_stock', function (Blueprint $table) {
                $table->dropUnique(['branch_id', 'name', 'volume']);
                $table->unique(['branch_id', 'name']);
            });
        }
    }
};
