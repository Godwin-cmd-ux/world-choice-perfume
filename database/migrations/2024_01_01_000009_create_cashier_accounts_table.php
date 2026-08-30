<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('expected_cash', 12, 2)->default(0);
            $table->decimal('actual_cash', 12, 2)->nullable();
            $table->decimal('difference', 12, 2)->nullable();
            $table->enum('status', ['pending', 'balanced', 'loss', 'surplus'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['cashier_id', 'date']);
        });

        Schema::create('discrepancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_account_id')->constrained('cashier_accounts')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
            $table->enum('reason', ['approved_expense', 'refund', 'discount', 'genuine_shortage', 'surplus', 'damaged_stock', 'missing_stock']);
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->index(['cashier_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discrepancies');
        Schema::dropIfExists('cashier_accounts');
    }
};
