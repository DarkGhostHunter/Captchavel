<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use ReCaptcha\Response;
use Illuminate\Http\Request;
use DarkGhostHunter\Captchavel\Exceptions\InvalidRecaptchaException;

class TransparentRecaptcha extends CheckRecaptcha
{
    /**
     * Return if the Request has a valid reCAPTCHA token
     *
     * @param  \Illuminate\Http\Request $request
     * @return bool
     * @throws \Throwable
     */
    protected function hasValidRequest(Request $request)
    {
        // In our transparent recaptcha we will only ask to have the "_recaptcha" input
        // set in the frontend, even if its empty. This allows the developer to add a
        // placeholder input with the "_recaptcha" key instead of using his script.
        $isValid = !$this->validator->make($request->only('_recaptcha'), [
            '_recaptcha' => 'nullable',
        ])->fails();

        return throw_unless($isValid, InvalidRecaptchaException::class, $request->only('_recaptcha'));
    }
    /**
     * Resolves a reCAPTCHA Request into a reCAPTCHA Response
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  float  $threshold
     * @return \DarkGhostHunter\Captchavel\ReCaptcha
     */
    protected function resolve(Request $request, float $threshold)
    {
        return $this->reCaptcha->setResponse(
            new Response(
                true,
                [],
                null,
                now()->toIso8601ZuluString(),
                null,
                (int)$request->has('is_robot'),
                $this->sanitizeAction($request->getRequestUri()))
        );
    }
}
