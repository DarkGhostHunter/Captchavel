<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use Closure;
use DarkGhostHunter\Captchavel\Captchavel;
use Illuminate\Http\Request;

class VerifyReCaptchaV2 extends BaseReCaptchaMiddleware
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $version
     * @param  string  $input
     *
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(Request $request, Closure $next, string $version, string $input = Captchavel::INPUT)
    {
        if ($this->isEnabled() && $this->isReal()) {
            $this->validateRequest($request, $input);
            $this->validateResponse($this->retrieveChallenge($request, $input, $version), $input);
        }

        return $next($request);
    }
}
