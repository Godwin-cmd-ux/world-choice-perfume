<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bottle_stock', function (Blueprint $table) {
            $table->string('variant')->default('plain')->after('volume');
        });

        Schema::table('bottle_stock_movements', function (Blueprint $table) {
            $table->string('variant')->nullable()->after('volume');
        });

        Schema::table('bottle_stock', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'volume']);
            $table->unique(['branch_id', 'volume', 'variant']);
        });
    }

    public function down(): void
    {
        Schema::table('bottle_stock', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'volume', 'variant']);
            $table->unique(['branch_id', 'volume']);
        });

        Schema::table('bottle_stock', function (Blueprint $table) {
            $table->dropColumn('variant');
        });

        Schema::table('bottle_stock_movements', function (Blueprint $table) {
            $table->dropColumn('variant');
        });
    }
};