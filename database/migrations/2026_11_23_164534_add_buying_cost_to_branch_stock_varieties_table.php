<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('branch_stock_varieties')) {
            return;
        }

        Schema::table('branch_stock_varieties', function (Blueprint $table) {
            if (!Schema::hasColumn('branch_stock_varieties', 'buying_cost')) {
                $table->decimal('buying_cost', 12, 2)->default(0)->after('selling_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('branch_stock_varieties')) {
            return;
        }

        Schema::table('branch_stock_varieties', function (Blueprint $table) {
            if (Schema::hasColumn('branch_stock_varieties', 'buying_cost')) {
                $table->dropColumn('buying_cost');
            }
        });
    }
};
