<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bottle_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('volume'); // 6ml, 12ml, 30ml, 50ml, 100ml
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'volume']);
        });

        Schema::create('bottle_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('volume');
            $table->enum('type', ['stock_in', 'stock_out', 'broken']);
            $table->integer('quantity');
            $table->string('reason')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bottle_stock_movements');
        Schema::dropIfExists('bottle_stock');
    }
};
