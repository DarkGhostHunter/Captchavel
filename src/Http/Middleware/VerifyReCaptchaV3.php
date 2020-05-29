<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use Closure;
use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;

class VerifyReCaptchaV3 extends BaseReCaptchaMiddleware
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $threshold
     * @param  string|null  $action
     * @param  string  $input
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle($request, Closure $next, $threshold = null, $action = null, $input = Captchavel::INPUT)
    {
        if ($this->isEnabled()) {
            if ($this->isReal()) {
                $this->validateRequest($request, $input);
            } else {
                $this->fakeResponseScore($request);

                // We will disable the action name since it will be verified if we don't null it.
                $action = null;
            }

            $this->processChallenge($request, $threshold, $action, $input);
        }

        return $next($request);
    }

    /**
     * Process the response from reCAPTCHA servers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  null|string  $threshold
     * @param  null|string  $action
     * @param  string  $input
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function processChallenge($request, $threshold, $action, $input)
    {
        $response = $this->retrieve($request, $input, 3);

        $response->setThreshold($this->normalizeThreshold($threshold));

        $this->dispatch($request, $response);

        $this->validateResponse($response, $input, $this->normalizeAction($action));

        // After we get the response, we will register the instance as a shared ("singleton").
        // Obviously we will set the threshold set by the developer or just use the default.
        // The Response should not be available until the middleware runs, so this is ok.
        app()->instance(ReCaptchaResponse::class, $response);
    }

    /**
     * Normalize the threshold string.
     *
     * @param string|null $threshold
     * @return array|float|mixed
     */
    protected function normalizeThreshold($threshold)
    {
        return $threshold === 'null' ? $this->config->get('captchavel.threshold') : (float)$threshold;
    }

    /**
     * Normalizes the action name, or returns null.
     *
     * @param  null|string  $action
     * @return null|string
     */
    protected function normalizeAction($action)
    {
        return strtolower($action) === 'null' ? null : $action;
    }
}
