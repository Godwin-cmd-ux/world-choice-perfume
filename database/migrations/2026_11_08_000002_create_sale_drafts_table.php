<?php

use App\Support\SaleDraftSchema;
use Illuminate\Database\Migrations\Migration;
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
        SaleDraftSchema::ensure();
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_drafts');
    }
};
