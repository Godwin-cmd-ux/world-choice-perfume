<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('branch_stock')) {
            Schema::create('branch_stock', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->integer('quantity')->default(0);
                $table->decimal('buying_cost', 12, 2)->default(0);
                $table->decimal('selling_price', 12, 2)->default(0);
                $table->string('supplier')->nullable();
                $table->date('date_received')->nullable();
                $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['branch_id', 'product_id']);
            });
        }

        if (!Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('type');
                $table->integer('quantity');
                $table->decimal('unit_cost', 12, 2)->nullable();
                $table->decimal('unit_price', 12, 2)->nullable();
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('branch_stock');
    }
};
