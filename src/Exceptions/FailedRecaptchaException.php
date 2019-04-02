<?php

namespace DarkGhostHunter\Captchavel\Exceptions;

use Exception;
use Throwable;

class FailedRecaptchaException extends Exception
{
    public function __construct(array $errorCodes)
    {
        $this->message = "The Google reCAPTCHA library returned the following errors: \n" .
        implode("\n- ", $errorCodes);
    }
}