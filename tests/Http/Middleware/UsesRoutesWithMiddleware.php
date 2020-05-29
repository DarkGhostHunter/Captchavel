<?php

namespace Tests\Http\Middleware;

use Illuminate\Support\Facades\Route;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;

trait UsesRoutesWithMiddleware
{
    protected function createsRoutes()
    {
        config(['captchavel.enable' => true]);

        Route::post('v3/default', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.v3');

        Route::post('v3/threshold_1', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.v3:1.0');

        Route::post('v3/threshold_0', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.v3:0');

        Route::post('v3/action_foo', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.v3:null,foo');

        Route::post('v3/input_bar', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.v3:null,null,bar');

        Route::post('v2/checkbox', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.v2:checkbox');

        Route::post('v2/checkbox/input_bar', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.v2:checkbox,bar');

        Route::post('v2/invisible', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.v2:invisible');

        Route::post('v2/invisible/input_bar', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.v2:invisible,bar');

        Route::post('v2/android', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.v2:android');

        Route::post('v2/android/input_bar', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.v2:android,bar');
    }
}
