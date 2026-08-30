<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Clear conflicting OS env vars so .env file takes precedence
// These may be inherited from the parent shell session
$conflictingVars = [
    'APP_NAME', 'APP_ENV', 'APP_KEY', 'APP_DEBUG', 'APP_URL',
    'MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD',
    'MAIL_SCHEME', 'MAIL_ENCRYPTION', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME',
    'DB_CONNECTION', 'SESSION_DRIVER', 'LOG_CHANNEL', 'LOG_LEVEL',
    'VITE_APP_NAME', 'BCRYPT_ROUNDS',
];
foreach ($conflictingVars as $var) {
    putenv($var);
    unset($_SERVER[$var]);
    unset($_ENV[$var]);
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
