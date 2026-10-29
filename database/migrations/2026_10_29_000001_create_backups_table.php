<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The log of nightly backups, one row per day.
     *
     * Until now a backup existed only as two files in Cloudinary, so the app
     * had no way to show what had been taken or to delete one: it would have
     * had to trust the filenames in a bucket listing. This table is the record
     * the Super Admin's Backups page reads, and the row that a delete removes
     * after the files are gone from Cloudinary.
     *
     * backup_date is UNIQUE so a re-run on the same night updates that day's
     * row instead of leaving two.
     */
    public function up(): void
    {
        if (Schema::hasTable('backups')) {
            return; // already created (a Supabase install built from SYSTEM_SCHEMA.sql)
        }

        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->date('backup_date')->unique();
            $table->text('folder');
            $table->text('schema_url')->nullable();
            $table->bigInteger('schema_bytes')->nullable();
            $table->text('seed_url')->nullable();
            $table->bigInteger('seed_bytes')->nullable();
            $table->integer('tables_count')->default(0);
            $table->integer('rows_count')->default(0);
            $table->decimal('seconds', 8, 2)->nullable();
            $table->string('status')->default('complete');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
