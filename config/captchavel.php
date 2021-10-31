<?php

use DarkGhostHunter\Captchavel\Captchavel;

return [

    /*
    |--------------------------------------------------------------------------
    | Main switch
    |--------------------------------------------------------------------------
    |
    | This switch enables the main Captchavel v2 middleware to detect all the
    | challenges incoming. You should activate it on production environments
    | and deactivate it on local environments unless you to test responses.
    |
    */

    'enable' => env('CAPTCHAVEL_ENABLE', false),

    /*
    |--------------------------------------------------------------------------
    | Fake on local development
    |--------------------------------------------------------------------------
    |
    | Sometimes you may want to fake success or failed responses from reCAPTCHA
    | servers in local development. To do this, simply enable the environment
    | variable and then issue as a checkbox parameter is_robot to any form.
    |
    | For v2 middleware, faking means bypassing checks.
    |
    */

    'fake' => env('CAPTCHAVEL_FAKE', false),

    /*
    |--------------------------------------------------------------------------
    | Constraints
    |--------------------------------------------------------------------------
    |
    | These default constraints allows further verification of the incoming
    | response from reCAPTCHA servers. Hostname and APK Package Name are
    | required if these are not verified in your reCAPTCHA admin panel.
    |
    */

    'hostname'         => env('RECAPTCHA_HOSTNAME'),
    'apk_package_name' => env('RECAPTCHA_APK_PACKAGE_NAME'),

    /*
    |--------------------------------------------------------------------------
    | Threshold
    |--------------------------------------------------------------------------
    |
    | For reCAPTCHA v3, which is a score-driven interaction, this default
    | threshold is the slicing point between bots and humans. If a score
    | is below this threshold it means the request was made by a bot.
    |
    */

    'threshold' => 0.5,

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    |
    | The following is the array of credentials for each version and variant
    | of the reCAPTCHA services. You shouldn't need to edit this unless you
    | know what you're doing. On reCAPTCHA v2, it comes with testing keys.
    |
    */

    'credentials' => [
        Captchavel::CHECKBOX => [
            'secret' => env('RECAPTCHA_CHECKBOX_SECRET', Captchavel::TEST_V2_SECRET),
            'key'    => env('RECAPTCHA_CHECKBOX_KEY', Captchavel::TEST_V2_KEY),
        ],
        Captchavel::INVISIBLE => [
            'secret' => env('RECAPTCHA_INVISIBLE_SECRET', Captchavel::TEST_V2_SECRET),
            'key'    => env('RECAPTCHA_INVISIBLE_KEY', Captchavel::TEST_V2_KEY),
        ],
        Captchavel::ANDROID => [
            'secret' => env('RECAPTCHA_ANDROID_SECRET', Captchavel::TEST_V2_SECRET),
            'key'    => env('RECAPTCHA_ANDROID_KEY', Captchavel::TEST_V2_KEY),
        ],
        Captchavel::SCORE => [
            'secret' => env('RECAPTCHA_SCORE_SECRET'),
            'key'    => env('RECAPTCHA_SCORE_KEY'),
        ],
    ],
];
