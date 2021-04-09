<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use Closure;
use DarkGhostHunter\Captchavel\Captchavel;
use Illuminate\Config\Repository;
use Illuminate\Http\Request;

class VerifyReCaptchaV2
{
    use ChecksCaptchavelStatus;
    use ValidatesRequestAndResponse;

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
        if ($this->isEnabled()) {
            $this->validateRequest($request, $input);
            $this->validateResponse(
                $this->captchavel->getChallenge($request->input($input), $request->ip(), $version),
                $input
            );
        }

        return $next($request);
    }
}
