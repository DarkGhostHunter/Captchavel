<?php

namespace DarkGhostHunter\Captchavel\Exceptions;

use Exception;

class FailedRecaptchaException extends Exception implements CaptchavelException
{
    public function __construct(array $errorCodes)
    {
        $this->message = 'The Google reCAPTCHA library returned the following errors:' .
        implode("\n- ", $errorCodes);

        parent::__construct();
    }
}