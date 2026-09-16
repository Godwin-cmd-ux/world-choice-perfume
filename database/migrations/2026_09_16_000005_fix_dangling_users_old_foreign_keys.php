<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Several tables' foreign keys were rewritten by SQLite to reference
     * "users_old" (a table dropped long ago by the role-enum rebuild
     * migrations — RENAME rewrites every referencing table's foreign keys,
     * and dropping users_old left them dangling). Any statement whose
     * foreign-key logic touches those tables fails with
     * "no such table: main.users_old".
     *
     * Every affected table is rebuilt with its foreign keys pointing back at
     * "users". Detection is dynamic, so this repairs whatever is broken and
     * is a no-op on healthy or fresh databases.
     */
    public function up(): void
    {
        $affectedTables = collect(DB::select(
            "SELECT name FROM sqlite_master
             WHERE type = 'table' AND sql LIKE '%users_old%'
               AND name NOT LIKE '%_repair'"
        ))->pluck('name')->all();

        if (empty($affectedTables)) {
            return;
        }

        Schema::disableForeignKeyConstraints();
        // Keep other tables' FK references untouched during the repair rename.
        DB::statement('PRAGMA legacy_alter_table = ON');

        foreach ($affectedTables as $table) {
            $createSql = DB::select(
                "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?",
                [$table]
            )[0]->sql ?? null;

            if (!$createSql) {
                continue;
            }

            $repairedSql = str_replace(
                ['"users_old"', 'users_old'],
                ['"users"', '"users"'],
                $createSql
            );

            // Build the same table under a temporary name. The first quoted
            // occurrence of the table name in its CREATE TABLE sql is the
            // table name itself.
            $tempName = "{$table}_repair";
            $tempSql = preg_replace(
                '/'.preg_quote('"'.$table.'"', '/').'/',
                '"'.$tempName.'"',
                $repairedSql,
                1
            );
            DB::statement("DROP TABLE IF EXISTS \"{$tempName}\"");
            DB::statement($tempSql);

            // Copy the data across, preserving ids and timestamps.
            $columns = collect(DB::select("PRAGMA table_info(\"{$table}\")"))
                ->map(fn ($c) => '"'.$c->name.'"')
                ->implode(', ');
            DB::statement(
                "INSERT INTO \"{$tempName}\" ({$columns}) SELECT {$columns} FROM \"{$table}\""
            );

            // Swap the tables in. legacy_alter_table is ON, so no other
            // table's foreign keys are rewritten by this rename.
            DB::statement("DROP TABLE \"{$table}\"");
            DB::statement("ALTER TABLE \"{$tempName}\" RENAME TO \"{$table}\"");
        }

        DB::statement('PRAGMA legacy_alter_table = OFF');
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Nothing to undo — the repaired schema is the intended one.
    }
};
