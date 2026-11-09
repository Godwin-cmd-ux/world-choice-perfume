<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The branch a client was assigned to when their record was created.
 *
 * Until now a client only belonged to a branch by having transacted there
 * (sales.branch_id). The New Customer form's "Assign to" picker gives the
 * record its own branch so a client added today already sits on that branch's
 * list, before their first sale.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'branch_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('whatsapp')
                    ->constrained('branches')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'branch_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
