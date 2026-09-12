<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oil_fragrance_stock', function (Blueprint $table) {
            $table->integer('volume')->nullable()->after('name');
        });

        Schema::table('oil_fragrance_movements', function (Blueprint $table) {
            $table->integer('volume')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('oil_fragrance_stock', function (Blueprint $table) {
            $table->dropColumn('volume');
        });

        Schema::table('oil_fragrance_movements', function (Blueprint $table) {
            $table->dropColumn('volume');
        });
    }
};