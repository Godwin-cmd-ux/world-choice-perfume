<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotency: inspect the actual CHECK constraint in the schema
        // (a SELECT with WHERE does not trigger CHECK, so it can't detect this)
        $schemaSql = DB::select("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'users'")[0]->sql ?? '';
        if (str_contains($schemaSql, 'customer_care') && str_contains($schemaSql, 'graphic_designer')) {
            return; // already fixed
        }

        Schema::disableForeignKeyConstraints();

        DB::statement('ALTER TABLE users RENAME TO users_old');

        // SQLite attaches existing index names to the renamed table; they must
        // be dropped or Schema::create fails with "index already exists".
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

        // Drop leftover indexes from users_old before removing it
        DB::statement('DROP INDEX IF EXISTS users_email_unique');
        DB::statement('DROP INDEX IF EXISTS users_supabase_id_index');
        DB::statement('DROP INDEX IF EXISTS users_branch_id_index');

        DB::statement('DROP TABLE users_old');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::statement('DROP TABLE IF EXISTS users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->enum('role', ['super_admin', 'branch_admin', 'cashier', 'stock_manager'])->default('cashier');
            $table->enum('status', ['pending', 'approved', 'rejected', 'active'])->default('pending');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('profile_picture')->nullable();
            $table->string('company_secret_code')->nullable();
            $table->boolean('otp_verified')->default(false);
            $table->string('supabase_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // users_new is intentionally dropped without copying back — reverting
        // would break customer_care/seller rows that no longer fit the enum.
        DB::statement('DROP TABLE IF EXISTS users_new');

        Schema::enableForeignKeyConstraints();
    }
};
