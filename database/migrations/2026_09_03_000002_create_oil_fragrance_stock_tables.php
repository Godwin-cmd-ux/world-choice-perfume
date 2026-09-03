<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oil_fragrance_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('name'); // fragrance name
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'name']);
        });

        Schema::create('oil_fragrance_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['stock_in', 'stock_out']);
            $table->integer('quantity');
            $table->string('reason')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oil_fragrance_movements');
        Schema::dropIfExists('oil_fragrance_stock');
    }
};
