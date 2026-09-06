<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if the stock_manager value already works (idempotent)
        try {
            DB::table('users')->where('role', 'stock_manager')->count();
            return; // already fixed
        } catch (\Exception $e) {
            // CHECK constraint exists — need to fix
        }

        Schema::disableForeignKeyConstraints();

        // Rename existing table
        DB::statement('ALTER TABLE users RENAME TO users_old');

        // Recreate with expanded enum
        Schema::create('users', function ($table) {
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
            $table->rememberToken();
            $table->timestamps();
        });

        // Copy data — use CAST to bypass CHECK on role during transfer
        DB::statement("
            INSERT INTO users (id, name, email, email_verified_at, password, phone, role, status, branch_id, profile_picture, company_secret_code, otp_verified, remember_token, created_at, updated_at)
            SELECT id, name, email, email_verified_at, password, phone, role, status, branch_id, profile_picture, company_secret_code, otp_verified, remember_token, created_at, updated_at
            FROM users_old
        ");

        DB::statement('DROP TABLE users_old');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::statement('ALTER TABLE users RENAME TO users_new');

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->enum('role', ['super_admin', 'branch_admin', 'cashier'])->default('cashier');
            $table->enum('status', ['pending', 'approved', 'rejected', 'active'])->default('pending');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('profile_picture')->nullable();
            $table->string('company_secret_code')->nullable();
            $table->boolean('otp_verified')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        DB::statement("
            INSERT INTO users (id, name, email, email_verified_at, password, phone, role, status, branch_id, profile_picture, company_secret_code, otp_verified, remember_token, created_at, updated_at)
            SELECT id, name, email, email_verified_at, password, phone, role, status, branch_id, profile_picture, company_secret_code, otp_verified, remember_token, created_at, updated_at
            FROM users_new
        ");

        DB::statement('DROP TABLE users_new');

        Schema::enableForeignKeyConstraints();
    }
};
