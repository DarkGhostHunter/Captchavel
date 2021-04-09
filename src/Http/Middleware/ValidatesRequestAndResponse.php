<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait ValidatesRequestAndResponse
{
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
}
