<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('address')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('profile_picture')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone')->nullable()->after('password');
                $table->string('role')->default('cashier')->after('phone');
                $table->string('status')->default('pending')->after('role');
                $table->foreignId('branch_id')->nullable()->after('status')->constrained('branches')->nullOnDelete();
                $table->string('profile_picture')->nullable()->after('branch_id');
                $table->string('company_secret_code')->nullable()->after('profile_picture');
                $table->boolean('otp_verified')->default(false)->after('company_secret_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'branch_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn(['phone', 'role', 'status', 'branch_id', 'profile_picture', 'company_secret_code', 'otp_verified']);
            });
        }
        Schema::dropIfExists('branches');
    }
};
