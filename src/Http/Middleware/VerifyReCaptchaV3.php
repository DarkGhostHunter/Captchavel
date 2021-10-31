<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use Closure;
use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\Facades\Captchavel as CaptchavelFacade;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use Illuminate\Container\Container;
use Illuminate\Http\Request;

use function app;
use function config;

/**
 * @internal
 */
class VerifyReCaptchaV3
{
    use VerificationHelpers;
    use NormalizeInput;

    /**
     * The signature of the middleware.
     *
     * @var string
     */
    public const SIGNATURE = 'recaptcha.score';

    /**
     * Captchavel connector.
     *
     * @var \DarkGhostHunter\Captchavel\Captchavel
     */
    protected Captchavel $captchavel;

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $threshold
     * @param  string|null  $action
     * @param  string  $input
     * @param  string  ...$guards
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(
        Request $request,
        Closure $next,
        string $threshold = null,
        string $action = null,
        string $input = Captchavel::INPUT,
        string ...$guards,
    ): mixed {
        $this->captchavel = CaptchavelFacade::getFacadeRoot();

        $input = $this->normalizeInput($input);

        // Ensure responses are always faked as humans, unless disabled and real.
        if ($this->isAuth($guards) || ($this->captchavel->isDisabled() || $this->captchavel->shouldFake())) {
            $this->fakeResponseScore($request);
        } else {
            $this->ensureChallengeIsPresent($request, $input);
        }

        $this->process($this->response($request, $input, $action), $threshold);

        return $next($request);
    }

    /**
     * Fakes a score reCAPTCHA response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function fakeResponseScore(Request $request): void
    {
        // Swap the implementation to the Captchavel Fake.
        $this->captchavel = CaptchavelFacade::fake();

        // If we're faking scores, allow the user to fake it through the input.
        if ($this->captchavel->shouldFake()) {
            $this->captchavel->score ??= (float) $request->missing('is_robot');
        }
    }

    /**
     * Retrieves the response, still being a promise pending resolution.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $input
     * @param  string|null  $action
     * @return \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse
     */
    protected function response(Request $request, string $input, ?string $action): ReCaptchaResponse
    {
        return $this->captchavel->getChallenge(
            $request->input($input), $request->ip(), Captchavel::SCORE, $input, $this->normalizeAction($action)
        );
    }

    /**
     * Process the response from reCAPTCHA servers.
     *
     * @param  \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse  $response
     * @param  null|string  $threshold
     * @return void
     */
    protected function process(ReCaptchaResponse $response, ?string $threshold): void
    {
        $response->setThreshold($this->normalizeThreshold($threshold));

        Container::getInstance()->instance(ReCaptchaResponse::class, $response);
    }

    /**
     * Normalize the threshold string, or returns the default.
     *
     * @param  string|null  $threshold
     * @return float
     */
    protected function normalizeThreshold(?string $threshold): float
    {
        return strtolower($threshold) === 'null' ? config('captchavel.threshold') : (float) $threshold;
    }

    /**
     * Normalizes the action name, or returns null.
     *
     * @param  null|string  $action
     *
     * @return null|string
     */
    protected function normalizeAction(?string $action): ?string
    {
        return strtolower($action) === 'null' ? null : $action;
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     *
     * @return void
     */
    public function terminate(): void
    {
        if (app()->has(ReCaptchaResponse::class)) {
            app(ReCaptchaResponse::class)->terminate();
        }
    }
}
