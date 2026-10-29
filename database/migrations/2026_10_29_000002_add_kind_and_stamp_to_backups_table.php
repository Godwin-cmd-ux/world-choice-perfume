<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Induced backups live beside the nightly ones, not on top of them.
     *
     * Until now a backup was identified by its date and backup_date was UNIQUE,
     * so taking one on demand replaced the night's snapshot and only one could
     * exist per day. An induced backup is now identified by a stamp — the
     * automatic one by its date, an induced one by date plus time — which is
     * what lets a super admin induce one seconds after midnight without
     * touching what the scheduler just wrote, and several times a day.
     *
     * Existing rows become kind=automatic with stamp=backup_date.
     */
    public function up(): void
    {
        if (! Schema::hasTable('backups')) {
            return; // a database built from SYSTEM_SCHEMA.sql already has the new shape
        }

        if (! Schema::hasColumn('backups', 'kind')) {
            Schema::table('backups', function (Blueprint $table) {
                $table->string('kind')->default('automatic')->after('backup_date');
            });
        }

        if (! Schema::hasColumn('backups', 'stamp')) {
            Schema::table('backups', function (Blueprint $table) {
                $table->string('stamp')->nullable()->after('kind');
            });
        }

        // Backfill in PHP so this runs on both Postgres and the SQLite test DB.
        foreach (DB::table('backups')->select('id', 'backup_date')->whereNull('stamp')->get() as $row) {
            DB::table('backups')->where('id', $row->id)->update([
                'stamp' => (string) $row->backup_date,
            ]);
        }

        // The stamp is the identity now. backup_date is only a display/filter
        // column, so two backups may share a date.
        Schema::table('backups', function (Blueprint $table) {
            $table->unique('stamp');
        });

        try {
            Schema::table('backups', function (Blueprint $table) {
                $table->dropUnique(['backup_date']);
            });
        } catch (\Throwable $e) {
            // Some engines name the index differently; a database built from
            // SYSTEM_SCHEMA.sql never had the unique in the first place.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('backups')) {
            return;
        }

        try {
            Schema::table('backups', function (Blueprint $table) {
                $table->dropUnique(['stamp']);
            });
        } catch (\Throwable $e) {
            // nothing to drop
        }

        // Collapse back to one row per date before re-adding the old unique:
        // the induced rows are the ones that would violate it.
        DB::table('backups')->where('kind', 'induced')->delete();

        Schema::table('backups', function (Blueprint $table) {
            $table->unique('backup_date');
        });

        foreach (['stamp', 'kind'] as $column) {
            if (Schema::hasColumn('backups', $column)) {
                Schema::table('backups', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
