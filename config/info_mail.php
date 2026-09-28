<?php

return [

    /*
    |--------------------------------------------------------------------------
    | info@worldchoiceperfume.com
    |--------------------------------------------------------------------------
    |
    | The public mailbox behind Customer Care → Mails.
    |
    | Cloudflare Email Routing (or any other provider) POSTs every mail for
    | this address to `inbound_url` below, authenticated with
    | `inbound_webhook` — that is the EMAIL_RECEIVING_WEBHOOK value in .env.
    | Nothing is accepted without it, so the URL is not a public mailbox.
    |
    | Replies leave through the app's normal mail transport (config/mail.php)
    | with `address` as the From, which has to be a mailbox the provider has
    | verified for the domain — a Gmail SMTP login cannot send as info@.
    |
    */

    'address' => env('INFO_MAIL_ADDRESS', 'info@worldchoiceperfume.com'),

    'name' => env('INFO_MAIL_NAME', 'World Choice Perfumes'),

    // The address the mail provider must POST to. Pinned to the public domain
    // rather than APP_URL, which on Render is the internal onrender.com host.
    'inbound_url' => rtrim((string) env('INFO_MAIL_INBOUND_URL', 'https://worldchoiceperfume.com/api/inbound-emails'), '/'),

    'inbound_webhook' => env('EMAIL_RECEIVING_WEBHOOK'),

    // Mails listed per page in Customer Care → Mails.
    'per_page' => (int) env('INFO_MAILS_PER_PAGE', 30),

];
