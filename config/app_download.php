<?php

/*
|--------------------------------------------------------------------------
| Mobile app download link
|--------------------------------------------------------------------------
|
| The store listing behind the "Download Our App" button in the public
| navigation. It is kept here rather than typed into the markup because the
| button is rendered twice — desktop bar and mobile menu — and two copies of a
| store URL is how a visitor ends up sent to the wrong app.
|
| The default below is the EAS build artifact for the Android app, a direct
| .apk download, so the button works without any extra server configuration.
| Override it with APP_DOWNLOAD_URL in .env once a Play Store listing exists.
|
*/

$url = trim((string) env(
    'APP_DOWNLOAD_URL',
    'https://expo.dev/artifacts/eas/8P1vuU74KUmwZKF4m4h_8XmEnei5EU9Cu7J9N4MXgCg.apk'
));

return [

    'url' => $url !== '' ? $url : '#',

];
