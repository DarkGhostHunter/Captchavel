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
        if (static::$resolvedInstance instanceof CaptchavelFake) {
            return static::$resolvedInstance;
        }

        static::swap($fake = static::$app->make(CaptchavelFake::class));

        return $fake;
    }

    /**
     * Makes the fake Captchavel response with a fake score.
     *
     * @param  float  $score
     * @return \DarkGhostHunter\Captchavel\CaptchavelFake
     */
    public static function fakeScore(float $score)
    {
        return static::fake()->fakeScore($score);
    }

    /**
     * Makes a fake Captchavel response made by a robot with "0" score.
     *
     * @return \DarkGhostHunter\Captchavel\CaptchavelFake
     */
    public static function fakeRobot()
    {
        return static::fake()->fakeRobot();
    }

    /**
     * Makes a fake Captchavel response made by a human with "1.0" score.
     *
     * @return \DarkGhostHunter\Captchavel\CaptchavelFake
     */
    public static function fakeHuman()
    {
        return static::fake()->fakeHuman();
    }
}
