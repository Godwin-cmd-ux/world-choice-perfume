<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The unit-cost feature was withdrawn: drop the columns it added to the
     * local products table (they may or may not exist depending on whether
     * the earlier migration ran).
     */
    public function up(): void
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

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'unit_cost')) {
                $table->decimal('unit_cost', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('products', 'costing_volume')) {
                $table->integer('costing_volume')->nullable();
            }
        });
    }
};
