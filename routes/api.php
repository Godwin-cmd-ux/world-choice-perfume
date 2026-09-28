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

// Reports which mailbox settings this server can actually see, as set/unset
// only — never a value. A deployment can look correct in the Render dashboard
// while the container was started without a given variable, and this is what
// tells the two apart. Delete once the mailbox works.
Route::get('/inbound-emails/status', function () {
    $checks = [
        'EMAIL_RECEIVING_WEBHOOK' => config('info_mail.inbound_webhook') !== null
            && config('info_mail.inbound_webhook') !== '',
        'INFO_MAIL_INBOUND_URL' => config('info_mail.inbound_url') !== '',
        'INFO_MAIL_ADDRESS' => config('info_mail.address') !== '',
        'INFO_MAIL_NAME' => config('info_mail.name') !== '',
        'MAIL_MAILER' => (string) config('mail.default') !== '',
        'RESEND_API_KEY' => (string) config('services.resend.key') !== '',
    ];

    return response()->json([
        'ready' => $checks['EMAIL_RECEIVING_WEBHOOK'],
        'checks' => $checks,
        'mailer' => config('mail.default'),
        'app_env' => config('app.env'),
    ]);
})->name('api.inbound-emails.status');
