<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\CaptchavelFake;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use Illuminate\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

abstract class BaseReCaptchaMiddleware
{
    /**
     * Captchavel connector.
     *
     * @var \DarkGhostHunter\Captchavel\Captchavel|\DarkGhostHunter\Captchavel\CaptchavelFake
     */
    protected Captchavel $captchavel;

    /**
     * Application Config repository.
     *
     * @var \Illuminate\Config\Repository
     */
    protected Repository $config;

    /**
     * BaseReCaptchaMiddleware constructor.
     *
     * @param  \DarkGhostHunter\Captchavel\Captchavel  $captchavel
     * @param  \Illuminate\Config\Repository  $config
     */
    public function __construct(Captchavel $captchavel, Repository $config)
    {
        $this->config = $config;
        $this->captchavel = $captchavel;
    }

    /**
     * Determines if the reCAPTCHA verification should be enabled.
     *
     * @return bool
     */
    protected function isEnabled(): bool
    {
        return $this->config->get('captchavel.enable');
    }

    /**
     * Check if the reCAPTCHA response should be faked on-demand.
     *
     * @return bool
     */
    protected function isFake(): bool
    {
        return $this->config->get('captchavel.fake');
    }

    /**
     * Check if the reCAPTCHA response must be real.
     *
     * @return bool
     */
    protected function isReal(): bool
    {
        return !$this->isFake();
    }

    /**
     * Validate if this Request has the reCAPTCHA challenge string.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $input
     *
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateRequest(Request $request, string $input): void
    {
        $value = $request->input($input);

        if (!is_string($value) || blank($value)) {
            throw $this->validationException($input, 'The reCAPTCHA challenge is missing or has not been completed.');
        }
    }

    /**
     * Retrieves the Captchavel response from reCAPTCHA servers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $input
     * @param  string  $version
     *
     * @return \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse
     */
    protected function retrieveChallenge(Request $request, string $input, string $version): ReCaptchaResponse
    {
        return $this->captchavel->getChallenge($request->input($input), $request->ip(), $version);
    }

    /**
     * Fakes a score reCAPTCHA response.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return void
     */
    protected function fakeResponseScore(Request $request): void
    {
        if ($this->captchavel instanceof CaptchavelFake && null === $this->captchavel->score) {
            $request->filled('is_robot')
                ? $this->captchavel->fakeRobots()
                : $this->captchavel->fakeHumans();
        }
    }

    /**
     * Validate the Hostname and APK name from the response.
     *
     * @param  \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse  $response
     * @param  string  $input
     * @param  string|null  $action
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateResponse(
        ReCaptchaResponse $response,
        $input = Captchavel::INPUT,
        ?string $action = null
    ): void {
        if ($response->isDifferentHostname($this->config->get('captchavel.hostname'))) {
            throw $this->validationException(
                'hostname',
                "The hostname [{$response->hostname}] of the response is invalid."
            );
        }

        if ($response->isDifferentApk($this->config->get('captchavel.apk_package_name'))) {
            throw $this->validationException(
                'apk_package_name',
                "The apk_package_name [{$response->apk_package_name}] of the response is invalid."
            );
        }

        if ($response->isDifferentAction($action)) {
            throw $this->validationException(
                'action',
                "The action [{$response->action}] of the response is invalid."
            );
        }

        if ($response->isInvalid()) {
            throw $this->validationException(
                $input,
                "The reCAPTCHA challenge is invalid or was not completed."
            );
        }
    }

    /**
     * Creates a new Validation Exception instance.
     *
     * @param  string  $input
     * @param  string  $message
     *
     * @return \Illuminate\Validation\ValidationException
     */
    protected function validationException(string $input, string $message): ValidationException
    {
        return ValidationException::withMessages([$input => trans($message)])->redirectTo(back()->getTargetUrl());
    }
}
