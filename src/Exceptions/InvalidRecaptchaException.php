<?php

namespace DarkGhostHunter\Captchavel\Exceptions;

use Exception;

class InvalidRecaptchaException extends Exception
{
    protected $message = 'The reCAPTCHA token received is invalid';
}