<?php

if (!function_exists('recaptcha')) {

    /**
     * Returns the ReCaptcha service.
     *
     * @return bool|\DarkGhostHunter\Captchavel\ReCaptcha
     */
    function recaptcha() {
        return app('recaptcha');
    }
}