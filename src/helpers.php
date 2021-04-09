<?php

if (!function_exists('captchavel')) {
    /**
     * Returns the site key for the given reCAPTCHA challenge mechanism.
     *
     * @param  string  $mode
     *
     * @return string
     * @throws \LogicException
     */
    function captchavel(string $mode): string
    {
        if (blank($key = config("captchavel.credentials.{$mode}.key"))) {
            throw new RuntimeException("The reCAPTCHA site key for [$mode] doesn't exist.");
        }

        return $key;
    }
}
