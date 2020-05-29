<?php

namespace DarkGhostHunter\Captchavel\Events;

use Illuminate\Http\Request;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;

class ReCaptchaResponseReceived
{
    /**
     * The HTTP Request.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * The reCAPTCHA Response.
     *
     * @var \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse $response
     */
    public function __construct(Request $request, ReCaptchaResponse $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
