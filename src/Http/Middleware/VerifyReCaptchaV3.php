<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use Closure;
use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\CaptchavelFake;
use DarkGhostHunter\Captchavel\Facades\Captchavel as CaptchavelFacade;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use Illuminate\Container\Container;
use Illuminate\Http\Request;

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
            if ($this->isReal()) {
                $this->validateRequest($request, $input);
            } else {
                $this->ensureFakeCaptchavel();
                $this->fakeResponseScore($request);
                $this->prepareRequestForFaking($request, $input);
            }

            $this->processChallenge($request, $threshold, $action, $input);
        }

        return $next($request);
    }

    /**
     * Ensure we're using Captchavel Fake.
     *
     * @return void
     */
    protected function ensureFakeCaptchavel(): void
    {
        if (! $this->captchavel instanceof CaptchavelFake) {
            $this->captchavel = CaptchavelFacade::fake();
        }
    }

    /**
     * Prepare the Request to with a fake challenge input.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $input
     */
    protected function prepareRequestForFaking(Request $request, string $input)
    {
        if ($request->missing($input)) {
            $request->merge([$input => 'fake_challenge_input']);
        }
    }

    /**
     * Process the response from reCAPTCHA servers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  null|string  $threshold
     * @param  null|string  $action
     * @param  string  $input
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function processChallenge(Request $request, ?string $threshold, ?string $action, string $input)
    {
        $response = $this->retrieveChallenge($request, $input, Captchavel::SCORE)
            ->setThreshold($this->normalizeThreshold($threshold));

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
