<?php

namespace DarkGhostHunter\Captchavel\Facades;

use DarkGhostHunter\Captchavel\CaptchavelFake;
use Illuminate\Support\Facades\Facade;

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
    protected static function getFacadeAccessor(): string
    {
        return \DarkGhostHunter\Captchavel\Captchavel::class;
    }

    /**
     * Returns a new Captchavel service to fake responses.
     *
     * @return \DarkGhostHunter\Captchavel\CaptchavelFake
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function fake(): CaptchavelFake
    {
        $instance = static::getFacadeRoot();

        if ($instance instanceof CaptchavelFake) {
            return $instance;
        }

        static::swap($fake = static::getFacadeApplication()->make(CaptchavelFake::class));

        return $fake;
    }

    /**
     * Makes the fake Captchavel response with a fake score.
     *
     * @param  float  $score
     *
     * @return void
     */
    public static function fakeScore(float $score)
    {
        static::fake()->fakeScore($score);
    }

    /**
     * Makes a fake Captchavel response made by a robot with "0" score.
     *
     * @return void
     */
    public static function fakeRobot()
    {
        static::fake()->fakeRobots();
    }

    /**
     * Makes a fake Captchavel response made by a human with "1.0" score.
     *
     * @return void
     */
    public static function fakeHuman()
    {
        static::fake()->fakeHumans();
    }
}
