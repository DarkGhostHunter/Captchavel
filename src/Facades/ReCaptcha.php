<?php

namespace DarkGhostHunter\Captchavel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * The ReCaptcha facade
 *
 * @method bool                         isResolved()
 * @method bool                         isHuman()
 * @method bool                         isRobot()
 * @method \ReCaptcha\Response          response()
 * @method \Illuminate\Support\Carbon   getSince()
 *
 * @see \DarkGhostHunter\Captchavel\ReCaptcha
 */
class ReCaptcha extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'recaptcha';
    }
}
