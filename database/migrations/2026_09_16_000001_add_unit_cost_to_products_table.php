<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unit cost = supplier cost per unit at stock-in. For oil fragrances the
        // unit is the costing volume bottle (500ml/1000ml); for brand perfumes it
        // is per piece. Hidden from customers; used for profit calculations.
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'unit_cost')) {
                $table->decimal('unit_cost', 12, 2)->default(0)->after('sex_category');
            }
            if (!Schema::hasColumn('products', 'costing_volume')) {
                // 500/1000 for oil fragrance, null for brand perfume (per piece)
                $table->integer('costing_volume')->nullable()->after('unit_cost');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'costing_volume')) {
                $table->dropColumn('costing_volume');
            }
            if (Schema::hasColumn('products', 'unit_cost')) {
                $table->dropColumn('unit_cost');
            }
        });
    }
};
