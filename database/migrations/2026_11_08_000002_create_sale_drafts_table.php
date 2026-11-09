<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unfinished sales, parked until the staff member can finish them.
 *
 * A draft is deliberately its own table rather than a flagged row in sales:
 * every revenue, stock and receipt query in the app reads sales and must never
 * see an unfinished sale. Completing a draft creates the real sale through the
 * normal sale path (stock deducted, payment recorded) and deletes the draft,
 * so a completed sale cannot remain a draft.
 *
 * payload holds the whole form (customer, sale type, items, empty bottles and
 * payments) as posted, so resuming the draft restores exactly what was typed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sale_drafts')) {
            Schema::create('sale_drafts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->string('customer_label')->nullable();
                $table->decimal('total', 12, 2)->default(0);
                $table->unsignedInteger('item_count')->default(0);
                $table->json('payload');
                $table->timestamps();
                $table->index(['branch_id', 'updated_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_drafts');
    }
};
