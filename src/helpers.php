<?php

if (!function_exists('recaptcha')) {

    /**
     * Returns the ReCaptcha service.
     *
     * @return bool|\DarkGhostHunter\Captchavel\RecaptchaResponseHolder
     */
    function recaptcha() {
        return app('recaptcha');
    }
}