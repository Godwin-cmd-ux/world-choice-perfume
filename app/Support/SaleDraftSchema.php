<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Creates the sale_drafts table when a deployment has the code but has not run
 * the migration yet. Kept next to the migration so the columns only have one
 * definition to maintain.
 */
class SaleDraftSchema
{
    public static function ensure(): void
    {
        if (Schema::hasTable('sale_drafts')) {
            return;
        }

        try {
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
        } catch (Throwable $e) {
            // Two first drafts can arrive together; the slower request sees
            // the table the faster one just created.
            if (!Schema::hasTable('sale_drafts')) {
                throw $e;
            }
        }
    }
}
