<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mode
    |--------------------------------------------------------------------------
    |
    | Captchavel works without needing any specific configuration in your view.
    | You can set the mode to "manual" to disable any response modification,
    | letting you have total control about the frontend with the scripts.
    |
    */

    'mode' => env('CAPTCHAVEL_MODE', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Enable on Local Environment
    |--------------------------------------------------------------------------
    |
    | Having reCAPTCHA on local environment is usually not a good idea unless
    | you want to make some manual-human tests. For these moments, you can
    | enable reCAPTCHA setting this to true until you are sure it works.
    |
    */

    'enable_local' => env('CAPTCHAVEL_LOCAL', false),

    /*
    |--------------------------------------------------------------------------
    | Site Key and Secret
    |--------------------------------------------------------------------------
    |
    | Google reCAPTCHA issues two keys: a Site Key to show in your responses,
    | and a Secret you should hold privately, since this Secret checks the
    | reCAPTCHA behaviour. Check the reCAPTCHA Admin panel to make them.
    |
    */

    'key' => env('RECAPTCHA_V3_KEY'),
    'secret' => env('RECAPTCHA_V3_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Threshold
    |--------------------------------------------------------------------------
    |
    | The response from reCAPTCHA contains a score of interactivity. You can
    | set the default threshold number to differentiate between humans and
    | robots, so you can make actions depending on who made the Request.
    |
    */

    'threshold' => 0.5,

    /*
    |--------------------------------------------------------------------------
    | Request Method
    |--------------------------------------------------------------------------
    |
    | The underlying Google reCAPTCHA library for PHP admits a custom Request
    | Method for your application. That means, you can delegate an specific
    | class to handle how to send and to receive the reCaptcha response.
    |
    */

    'request_method' => null,
];