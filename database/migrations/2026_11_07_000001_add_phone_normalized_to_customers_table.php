<?php

use App\Services\PhoneNumberService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Duplicate customers were created because 0682601154 and +255682601154 were
 * two different strings. This adds the derived column both are written to, so
 * the database itself refuses a second customer with the same number.
 *
 * Existing rows are kept exactly as they are; only the new derived column is
 * filled. Where old rows already share a number, the first keeps the canonical
 * value and the others stay null, so the unique index can still be installed
 * without deleting or merging anyone.
 */
return new class extends Migration
{
    private const INDEX = 'customers_phone_normalized_unique';

    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        if (! Schema::hasColumn('customers', 'phone_normalized')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('phone_normalized', 32)->nullable();
            });
        }

        $phones = new PhoneNumberService();
        $claimed = [];

        DB::table('customers')
            ->select('id', 'phone')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($phones, &$claimed) {
                foreach ($rows as $row) {
                    $normalized = $phones->normalize($row->phone ?? null);

                    if ($normalized === null || isset($claimed[$normalized])) {
                        continue;
                    }

                    $claimed[$normalized] = (int) $row->id;

                    DB::table('customers')
                        ->where('id', $row->id)
                        ->update(['phone_normalized' => $normalized]);
                }
            });

        // A unique index is only installable when no two rows carry the same
        // normalized number. Older duplicates are reported, never merged.
        $collisions = DB::table('customers')
            ->whereNotNull('phone_normalized')
            ->select('phone_normalized')
            ->groupBy('phone_normalized')
            ->havingRaw('count(*) > 1')
            ->get();

        if ($collisions->isNotEmpty()) {
            Log::warning('customers.phone_normalized unique index skipped: existing duplicate phone numbers.', [
                'numbers' => $collisions->pluck('phone_normalized')->all(),
            ]);

            return;
        }

        try {
            Schema::table('customers', function (Blueprint $table) {
                $table->unique('phone_normalized', self::INDEX);
            });
        } catch (\Throwable $e) {
            Log::warning('customers.phone_normalized unique index could not be created: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers') || ! Schema::hasColumn('customers', 'phone_normalized')) {
            return;
        }

        try {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropUnique(self::INDEX);
            });
        } catch (\Throwable $e) {
            // The index may never have been installed (existing duplicates).
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('phone_normalized');
        });
    }
};
