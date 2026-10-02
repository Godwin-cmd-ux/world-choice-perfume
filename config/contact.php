<?php

/*
|--------------------------------------------------------------------------
| Public contact details
|--------------------------------------------------------------------------
|
| The number a visitor is told to call. It is kept here rather than typed into
| the markup because the site prints it in two places and dials it from both:
| a `tel:` link that disagrees with the number written beside it puts a
| caller through to a stranger, and nothing on the page would look wrong.
|
| `dial` is the same number reduced to the leading + and its digits, which
| is all a `tel:` href accepts. Browsers drop the call on some handsets when
| the spaces and brackets of the printed form are left in the link, so the
| number that is shown and the number that is dialled are derived from this
| one value instead of being written out twice.
|
*/

$phone = (string) env('CONTACT_PHONE', '+255 710 603 637');

// Same number is used for calls and WhatsApp unless a separate one is set.
$whatsapp = (string) env('CONTACT_WHATSAPP', $phone);

// `wa.me` is addressed by country code and digits only. Spaces, a leading
// plus and brackets all stop it resolving, so the link is derived from the
// value that is printed rather than written out a second time.
$whatsappDigits = preg_replace('/\D+/', '', $whatsapp);

if ($whatsappDigits !== '' && str_starts_with($whatsappDigits, '0')) {
    $whatsappDigits = '255'.ltrim($whatsappDigits, '0');
}

return [

    'phone' => $phone,

    'dial' => preg_replace('/[^\d+]/', '', $phone),

    'whatsapp' => $whatsapp,

    'whatsapp_link' => $whatsappDigits !== '' ? 'https://wa.me/'.$whatsappDigits : null,

    // Business working hours as printed on the public pages. Kept beside the
    // contact details so the homepage and the footer cannot show two
    // different sets of hours.
    'hours' => (string) env('CONTACT_HOURS', 'Mon – Sat: 9:00 AM – 9:00 PM'),

];
