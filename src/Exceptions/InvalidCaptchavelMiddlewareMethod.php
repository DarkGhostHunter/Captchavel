<?php

namespace DarkGhostHunter\Captchavel\Exceptions;

use Exception;

class InvalidCaptchavelMiddlewareMethod extends Exception implements CaptchavelException
{
    /**
     * The exception message
     *
     * @var string
     */
    protected $message = 'Captchavel does not work in GET routes.';
}