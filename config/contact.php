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

return [

    'phone' => $phone,

    'dial' => preg_replace('/[^\d+]/', '', $phone),

];
