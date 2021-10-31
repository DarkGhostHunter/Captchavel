<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use Closure;
use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\Facades\Captchavel as CaptchavelFacade;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use LogicException;

/**
 * @internal
 */
class VerifyReCaptchaV2
{
    use VerificationHelpers;
    use NormalizeInput;

    /**
     * The signature of the middleware.
     *
     * @var string
     */
    public const SIGNATURE = 'recaptcha';

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $version
     * @param  string  $input
     * @param  string  ...$guards
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(
        Request $request,
        Closure $next,
        string $version,
        string $input = Captchavel::INPUT,
        string ...$guards
    ): mixed
    {
        if ($version === Captchavel::SCORE) {
            throw new LogicException('Use the [recaptcha.score] middleware to capture score-driven reCAPTCHA.');
        }

        $captchavel = CaptchavelFacade::getFacadeRoot();

        if ($this->isGuest($guards) && $captchavel->isEnabled() && !$captchavel->shouldFake()) {
            $this->ensureChallengeIsPresent($request, $input = $this->normalizeInput($input));

            Container::getInstance()->instance(
                ReCaptchaResponse::class,
                $captchavel->getChallenge($request->input($input), $request->ip(), $version, $input)->wait()
            );
        }

        return $next($request);
    }
}
