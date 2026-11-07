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
| The link below is the EAS build artifact for the Android app, a direct .apk
| download, so the button works without any extra server configuration.
| Change it here when a new build is published — no environment variable is
| involved.
|
*/

return [

    'url' => 'https://expo.dev/artifacts/eas/-50Cb2LWGbfUcbWWzrh7xm_SVKvK8hMQmXfPhjzTZcI.apk',

];
