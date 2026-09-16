<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirror of the Supabase products changes for the local SQLite database
     * (kept in sync because some Eloquent helpers read from it).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'sex_category')) {
                $table->string('sex_category')->nullable();
            }
            if (!Schema::hasColumn('products', 'unit_cost')) {
                $table->decimal('unit_cost', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('products', 'costing_volume')) {
                $table->integer('costing_volume')->nullable();
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
