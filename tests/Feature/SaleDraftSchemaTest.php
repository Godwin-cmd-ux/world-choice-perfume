<?php

namespace Tests\Feature;

use App\Support\SaleDraftSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Saving a draft must not depend on someone remembering to run the migration
 * on the deployment. The runtime guard creates the table, and running it twice
 * (or after the migration) must be a no-op.
 */
class SaleDraftSchemaTest extends TestCase
{
    public function test_it_creates_the_sale_drafts_table_and_can_run_again(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
        });

        SaleDraftSchema::ensure();
        SaleDraftSchema::ensure();

        $this->assertTrue(Schema::hasTable('sale_drafts'));

        foreach ([
            'id',
            'branch_id',
            'created_by',
            'customer_label',
            'total',
            'item_count',
            'payload',
            'created_at',
            'updated_at',
        ] as $column) {
            $this->assertContains($column, Schema::getColumnListing('sale_drafts'));
        }
    }
}
