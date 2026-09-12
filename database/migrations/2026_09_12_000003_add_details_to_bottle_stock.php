<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bottle_stock', function (Blueprint $table) {
            $table->string('has_logo')->nullable()->after('quantity');
            $table->string('logo_color')->nullable()->after('has_logo');
            $table->string('has_box')->nullable()->after('logo_color');
            $table->string('box_color')->nullable()->after('has_box');
        });
    }

    public function down(): void
    {
        Schema::table('bottle_stock', function (Blueprint $table) {
            $table->dropColumn(['has_logo', 'logo_color', 'has_box', 'box_color']);
        });
    }
};