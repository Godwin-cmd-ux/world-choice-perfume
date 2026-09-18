<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The 2026_09_07 migration that introduced customer_care/seller was later
     * edited to also add graphic_designer to the users.role enum, but the
     * database had already recorded it as ran — so databases migrated before
     * that edit still reject graphic_designer (login inserts fail the CHECK
     * constraint). This rebuilds the enum idempotently.
     */
    public function up(): void
    {
        $schemaSql = DB::select("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'users'")[0]->sql ?? '';
        if (str_contains($schemaSql, 'graphic_designer')) {
            return; // already fixed
        }

        Schema::disableForeignKeyConstraints();

        // Prevent SQLite from rewriting other tables' FKs to users_old.
        DB::statement('PRAGMA legacy_alter_table = ON');

        DB::statement('ALTER TABLE users RENAME TO users_old');

        DB::statement('DROP INDEX IF EXISTS users_email_unique');
        DB::statement('DROP INDEX IF EXISTS users_supabase_id_index');
        DB::statement('DROP INDEX IF EXISTS users_branch_id_index');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->enum('role', ['super_admin', 'branch_admin', 'cashier', 'stock_manager', 'customer_care', 'seller', 'graphic_designer'])->default('cashier');
            $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'blocked'])->default('pending');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('profile_picture')->nullable();
            $table->string('company_secret_code')->nullable();
            $table->boolean('otp_verified')->default(false);
            $table->string('supabase_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        DB::statement("
            INSERT INTO users (id, name, email, email_verified_at, password, phone, role, status, branch_id, profile_picture, company_secret_code, otp_verified, supabase_id, remember_token, created_at, updated_at)
            SELECT id, name, email, email_verified_at, password, phone, role, status, branch_id, profile_picture, company_secret_code, otp_verified, CAST(supabase_id AS TEXT), remember_token, created_at, updated_at
            FROM users_old
        ");

        DB::statement('DROP INDEX IF EXISTS users_email_unique');
        DB::statement('DROP INDEX IF EXISTS users_supabase_id_index');
        DB::statement('DROP INDEX IF EXISTS users_branch_id_index');

        DB::statement('DROP TABLE users_old');

        DB::statement('PRAGMA legacy_alter_table = OFF');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // No safe downgrade — reverting would break graphic_designer rows.
    }
};
