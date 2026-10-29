<?php

use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The nightly backup: every table dumped to seed_<date>.sql and filed in
// Cloudinary next to the schema it belongs to (see DatabaseBackupService).
// Midnight is Tanzania midnight, not the server's: Africa/Dar_es_Salaam.
Schedule::call(function () {
    app(DatabaseBackupService::class)->run();
})
    ->name('database-backup')
    ->dailyAt('00:00')
    ->timezone(DatabaseBackupService::TIMEZONE)
    ->withoutOverlapping(120);
