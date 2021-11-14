<?php

namespace DarkGhostHunter\Captchavel\Blade\Directives;

use function config;
use function now;
use function session;

class Challenged
{
    /**
     * Check if the reCAPTCHA challenge was remembered and not expired.
     *
     * @return bool
     */
    public static function directive(): bool
    {
        $timestamp = session()->get(config()->get('recaptcha.remember.key', '_recaptcha'));

        return $timestamp !== null && (!$timestamp || now()->getTimestamp() < $timestamp);
    }
}
