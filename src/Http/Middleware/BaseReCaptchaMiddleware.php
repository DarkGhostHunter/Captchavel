<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use Illuminate\Http\Request;
use DarkGhostHunter\Captchavel\Captchavel;
use Illuminate\Config\Repository as Config;
use Illuminate\Validation\ValidationException;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use DarkGhostHunter\Captchavel\Events\CaptchavelSuccessEvent;
use DarkGhostHunter\Captchavel\Events\ReCaptchaResponseReceived;
use DarkGhostHunter\Captchavel\Facades\Captchavel as CaptchavelFacade;

abstract class BaseReCaptchaMiddleware
{
    /**
     * Captchavel instance
     *
     * @var \DarkGhostHunter\Captchavel\Captchavel
     */
    protected $captchavel;

    /**
     * Config Repository
     *
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * VerifyReCaptchaV2 constructor.
     *
     * @param  \DarkGhostHunter\Captchavel\Captchavel  $captchavel
     * @param  \Illuminate\Config\Repository  $config
     */
    public function __construct(Captchavel $captchavel, Config $config)
    {
        $this->captchavel = $captchavel;
        $this->config = $config;
    }

    /**
     * Determines if the reCAPTCHA verification should be enabled.
     *
     * @return bool
     */
    protected function isEnabled()
    {
        return $this->config->get('captchavel.enable');
    }

    /**
     * Check if the reCAPTCHA response can be faked on-demand.
     *
     * @return bool
     */
    protected function isFake()
    {
        return $this->config->get('captchavel.fake');
    }

    /**
     * Check if the reCAPTCHA response must be real.
     *
     * @return bool
     */
    protected function isReal()
    {
        return ! $this->isFake();
    }

    /**
     * Validate if this Request has the reCAPTCHA challenge string.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $name
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateRequest($request, string $name)
    {
        if (! is_string($request->get($name))) {
            throw $this->validationException($name, 'The reCAPTCHA challenge is missing or has not been completed.');
        }
    }

    /**
     * Retrieves the Captchavel response from reCAPTCHA servers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $input
     * @param  int  $version
     * @param  string|null  $variant
     * @return \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse
     */
    protected function retrieve(Request $request, string $input, int $version, string $variant = null)
    {
        return $this->captchavel
            ->useCredentials($version, $variant)
            ->retrieve($request->input($input), $request->ip());
    }

    /**
     * Fakes a v3 reCAPTCHA response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function fakeResponseScore($request)
    {
        // We will first check if the Captchavel instance was not already faked. If it has been,
        // we will hands out the control to the developer. Otherwise, we will manually create a
        // fake Captchavel instance and look for the "is_robot" parameter to set a fake score.
        if ($this->captchavel instanceof Captchavel && $this->captchavel->isNotResolved()) {
            $this->captchavel = CaptchavelFacade::fake();

            $request->has('is_robot') ? $this->captchavel->asRobot() : $this->captchavel->asHuman();
        }


    }

    /**
     * Validate the Hostname and APK name from the response.
     *
     * @param  \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse  $response
     * @param  string  $input
     * @param  string|null  $action
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateResponse(ReCaptchaResponse $response, $input = Captchavel::INPUT, ?string $action = null)
    {
        if ($response->differentHostname($this->config->get('captchavel.hostname'))) {
            throw $this->validationException('hostname',
                "The hostname [{$response->hostname}] of the response is invalid.");
        }

        if ($response->differentApk($this->config->get('captchavel.apk_package_name'))) {
            throw $this->validationException('apk_package_name',
                "The apk_package_name [{$response->apk_package_name}] of the response is invalid.");
        }

        if ($response->differentAction($action)) {
            throw $this->validationException('action',
                "The action [{$response->action}] of the response is invalid.");
        }

        if ($response->isInvalid()) {
            throw $this->validationException($input,
                "The reCAPTCHA challenge is invalid or was not completed.");
        }
    }

    /**
     * Creates a new Validation Exception instance.
     *
     * @param  string  $input
     * @param  string  $message
     * @return \Illuminate\Validation\ValidationException
     */
    protected function validationException($input, $message)
    {
        return ValidationException::withMessages([$input => trans($message)])->redirectTo(back()->getTargetUrl());
    }

    /**
     * Dispatch an event with the request and the Captchavel Response
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse  $response
     */
    protected function dispatch(Request $request, ReCaptchaResponse $response)
    {
        event(new ReCaptchaResponseReceived($request, $response));
    }
}
