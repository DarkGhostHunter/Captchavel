<?php

namespace DarkGhostHunter\Captchavel\Exceptions;

use Exception;
use Throwable;

class InvalidRecaptchaException extends Exception implements CaptchavelException
{
    /**
     * The exception message
     *
     * @var string
     */
    protected $message = 'The reCAPTCHA token is empty';

    /**
     * Creates a new InvalidRecaptchaException instance.
     *
     * @param  null $token
     * @param  int $code
     * @param  \Throwable|null $previous
     */
    public function __construct($token = null, $code = 0, Throwable $previous = null)
    {
        if ($token !== null) {
            $this->message = 'The reCAPTCHA token received is invalid';
        }

        parent::__construct($this->message, $code, $previous);
    }
}