<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('category');
                $table->decimal('amount', 12, 2);
                $table->text('description');
                $table->date('date')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
