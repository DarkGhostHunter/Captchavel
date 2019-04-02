<?php

namespace DarkGhostHunter\Captchavel\Exceptions;

use Exception;
use Throwable;

class InvalidRecaptchaException extends Exception
{
    protected $message = 'The reCAPTCHA token is empty';

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if (request()->input('_recaptcha') !== null) {
            $this->message = 'The reCAPTCHA token received is invalid';
        }

        parent::__construct($message, $code, $previous);
    }
}