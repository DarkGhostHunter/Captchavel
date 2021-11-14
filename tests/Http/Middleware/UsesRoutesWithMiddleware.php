<?php

namespace Tests\Http\Middleware;

use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;

trait UsesRoutesWithMiddleware
{
    protected function createsRoutes()
    {
        config(['captchavel.enable' => true]);

        $this->app['router']->post('v3/default', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.score');

        $this->app['router']->post('v3/threshold_1', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.score:1.0');

        $this->app['router']->post('v3/threshold_0', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.score:0');

        $this->app['router']->post('v3/action_foo', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.score:null,foo');

        $this->app['router']->post('v3/input_bar', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.score:null,null,bar');

        $this->app['router']->post('v2/checkbox', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:checkbox');

        $this->app['router']->post('v2/checkbox/input_bar', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:checkbox,null,bar');

        $this->app['router']->post('v2/checkbox/remember', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:checkbox,10');

        $this->app['router']->post('v2/invisible', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:invisible');

        $this->app['router']->post('v2/invisible/input_bar', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:invisible,null,bar');

        $this->app['router']->post('v2/invisible/remember', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:invisible,10');

        $this->app['router']->post('v2/android', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:android');

        $this->app['router']->post('v2/android/input_bar', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:android,null,bar');

        $this->app['router']->post('v2/android/remember', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:android,10');
    }
}
