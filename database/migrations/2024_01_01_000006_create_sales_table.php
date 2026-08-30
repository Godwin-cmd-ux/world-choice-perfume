<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->string('sale_number')->unique();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->string('payment_status')->default('paid');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sale_items')) {
            Schema::create('sale_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->integer('quantity');
                $table->decimal('unit_price', 12, 2);
                $table->decimal('unit_cost', 12, 2);
                $table->decimal('total', 12, 2);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
