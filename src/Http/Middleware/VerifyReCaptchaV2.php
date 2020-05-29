<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use Closure;
use DarkGhostHunter\Captchavel\Captchavel;

class VerifyReCaptchaV2 extends BaseReCaptchaMiddleware
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $variant
     * @param  string  $input
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle($request, Closure $next, $variant, $input = Captchavel::INPUT)
    {
        if ($this->isEnabled() && $this->isReal()) {
            $this->validateRequest($request, $input);
            $this->processChallenge($request, $variant, $input);
        }

        return $next($request);
    }

    /**
     * Process a real challenge and response from reCAPTCHA servers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $variant
     * @param  string  $input
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function processChallenge($request, $variant, $input)
    {
        $this->dispatch($request, $response = $this->retrieve($request, $input, 2, $variant));

        $this->validateResponse($response, $input);
    }
}
