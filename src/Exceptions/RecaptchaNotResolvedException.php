<?php

namespace DarkGhostHunter\Captchavel\Exceptions;

use Exception;

class RecaptchaNotResolvedException extends Exception implements CaptchavelException
{
    /**
     * The exception message
     *
     * @var string
     */
    protected $message = 'The reCAPTCHA has not been verified in this Request.';
}