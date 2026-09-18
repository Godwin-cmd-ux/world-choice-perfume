<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirror of the Supabase products change for the local SQLite database
     * (kept in sync because some Eloquent helpers read from it).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'fundamental_ingredient')) {
                $table->string('fundamental_ingredient')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'fundamental_ingredient')) {
                $table->dropColumn('fundamental_ingredient');
            }
        });
    }
};