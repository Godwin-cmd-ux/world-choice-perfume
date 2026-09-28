<?php

use App\Http\Controllers\Api\InfoMailWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
|
| Machine endpoints only — no session, no CSRF, no signed-in user. The mail
| webhook authenticates with the EMAIL_RECEIVING_WEBHOOK secret instead of
| a login, so nothing here is reachable with a password.
|
*/

// Where mail sent to info@worldchoiceperfume.com is delivered.
// Cloudflare Email Routing → a Worker → this URL. See config/info_mail.php.
Route::post('/inbound-emails', [InfoMailWebhookController::class, 'store'])
    ->name('api.inbound-emails.store');
