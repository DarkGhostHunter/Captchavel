<?php

namespace DarkGhostHunter\Captchavel\Facades;

use Illuminate\Support\Facades\Facade;
use DarkGhostHunter\Captchavel\CaptchavelFake;
use DarkGhostHunter\Captchavel\Captchavel as BaseCaptchavel;

/**
 * @method static \DarkGhostHunter\Captchavel\Captchavel getFacadeRoot()
 */
class Captchavel extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BaseCaptchavel::class;
    }

    /**
     * Returns a new Captchavel service to fake responses.
     *
     * @return \DarkGhostHunter\Captchavel\CaptchavelFake
     */
    public static function fake()
    {
        static::swap($fake = static::$app->make(CaptchavelFake::class));

        return $fake;
    }
}
