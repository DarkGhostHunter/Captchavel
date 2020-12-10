<?php

if (! function_exists('captchavel')) {
    /**
     * Returns the site key for the given reCAPTCHA challenge mechanism.
     *
     * @param  string $credentials
     * @return string
     * @throws \LogicException
     */
    function captchavel(string $credentials)
    {
        if (in_array($credentials, ['3', 'v3', 'score']) && $key = config('captchavel.credentials.v3.key')) {
            return $key;
        }

        if ($key = config("captchavel.credentials.v2.$credentials.key")) {
            return $key;
        }

        throw new LogicException("The reCAPTCHA site key for [$credentials] doesn't exist.");
    }
}
