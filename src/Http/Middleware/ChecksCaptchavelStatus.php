<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

trait ChecksCaptchavelStatus
{
    /**
     * Determines if the reCAPTCHA verification should be enabled.
     *
     * @return bool
     */
    protected function isEnabled(): bool
    {
        return $this->config->get('captchavel.enable');
    }

    /**
     * Check if the reCAPTCHA response should be faked on-demand.
     *
     * @return bool
     */
    protected function isFake(): bool
    {
        return $this->config->get('captchavel.fake');
    }
}
