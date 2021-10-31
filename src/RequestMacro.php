<?php

namespace DarkGhostHunter\Captchavel;

use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;

use function app;

/**
 * @internal
 */
class RequestMacro
{
    /**
     * Check if the reCAPTCHA response is equal or above threshold score.
     *
     * @return bool
     */
    public static function isHuman(): bool
    {
        return app(ReCaptchaResponse::class)->isHuman();
    }

    /**
     * Check if the reCAPTCHA response is below threshold score.
     *
     * @return bool
     */
    public static function isRobot(): bool
    {
        return !static::isHuman();
    }
}
