<?php

namespace DarkGhostHunter\Captchavel\Exceptions;

use Exception;

class InvalidCaptchavelMiddlewareMethod extends Exception
{
    protected $message = 'Captchavel does not work in GET routes.';
}