<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use Closure;
use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\CaptchavelFake;
use DarkGhostHunter\Captchavel\Facades\Captchavel as CaptchavelFacade;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;

class VerifyReCaptchaV3
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
     * @param  string|null  $threshold
     * @param  string|null  $action
     * @param  string  $input
     *
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(Request $request,
        Closure $next,
        string $threshold = null,
        string $action = null,
        string $input = Captchavel::INPUT
    )
    {
        if ($this->isEnabled()) {
            if ($this->isFake()) {
                $this->fakeResponseScore($request);
            } else {
                $this->validateRequest($request, $input);
            }

            $this->processChallenge($request, $input, $threshold, $action);
        }

        return $next($request);
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
        if (! $this->captchavel instanceof CaptchavelFake) {
            $this->captchavel = CaptchavelFacade::fake();
        }

        // If the Captchavel has set an score to fake, use it, otherwise go default.
        if ($this->captchavel->score === null) {
            $request->filled('is_robot') ? $this->captchavel->fakeRobots() : $this->captchavel->fakeHumans();
        }
    }

    /**
     * Process the response from reCAPTCHA servers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $input
     * @param  null|string  $threshold
     * @param  null|string  $action
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function processChallenge(Request $request, string $input, ?string $threshold, ?string $action)
    {
        $response = $this->captchavel->getChallenge(
            $request->input($input),
            $request->ip(),
            Captchavel::SCORE
        )->setThreshold($this->normalizeThreshold($threshold));

        $this->validateResponse($response, $input, $this->normalizeAction($action));

        // After we get the response, we will register the instance as a shared
        // "singleton" for the current request lifetime. Obviously we will set
        // the threshold set by the developer or just use the config default.
        Container::getInstance()->instance(ReCaptchaResponse::class, $response);
    }

    /**
     * Normalize the threshold string.
     *
     * @param  string|null  $threshold
     *
     * @return float
     */
    protected function normalizeThreshold(?string $threshold): float
    {
        return $threshold === 'null' ? $this->config->get('captchavel.threshold') : (float)$threshold;
    }

    /**
     * Normalizes the action name, or returns null.
     *
     * @param  null|string  $action
     *
     * @return null|string
     */
    protected function normalizeAction(?string $action) : ?string
    {
        return strtolower($action) === 'null' ? null : $action;
    }
}
