<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_number')->unique();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->string('status')->default('pending');
                $table->decimal('total', 12, 2)->default(0);
                $table->text('delivery_notes')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->integer('quantity');
                $table->decimal('unit_price', 12, 2);
                $table->decimal('total', 12, 2);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
