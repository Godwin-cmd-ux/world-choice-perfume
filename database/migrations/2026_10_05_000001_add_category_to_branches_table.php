<?php

use App\Support\BranchCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supabase's branches table gained a `category` column (autonomous /
     * products_based) and the Super Admin branch form now writes it to both
     * stores. The local SQLite mirror was never given the column, so every
     * branch create/update died on `no such column: category` after the
     * Supabase write had already gone through. This adds it, defaulting
     * existing rows to the baseline.
     */
    public function up(): void
    {
        if (Schema::hasColumn('branches', 'category')) {
            return; // already added
        }

        Schema::table('branches', function (Blueprint $table) {
            $table->string('category')->default(BranchCategory::AUTONOMOUS)->after('is_active');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('branches', 'category')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
