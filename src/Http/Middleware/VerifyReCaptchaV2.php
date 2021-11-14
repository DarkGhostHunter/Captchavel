<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use Closure;
use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\Facades\Captchavel as CaptchavelFacade;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use LogicException;

use function app;
use function config;
use function now;
use function session;

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
     * @param  string  $remember
     * @param  string  $input
     * @param  string  ...$guards
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(
        Request $request,
        Closure $next,
        string $version,
        string $remember = 'null',
        string $input = Captchavel::INPUT,
        string ...$guards
    ): mixed
    {
        if ($version === Captchavel::SCORE) {
            throw new LogicException('Use the [recaptcha.score] middleware to capture score-driven reCAPTCHA.');
        }

        $remember = $this->normalizeRemember($remember);

        $captchavel = CaptchavelFacade::getFacadeRoot();

        if ($captchavel->isEnabled() && $this->isGuest($guards) && $this->doesntRemember($remember) && !$captchavel->shouldFake())) {
            $this->ensureChallengeIsPresent($request, $input = $this->normalizeInput($input));

            app()->instance(
                ReCaptchaResponse::class,
                $captchavel->getChallenge($request->input($input), $request->ip(), $version, $input)->wait()
            );

            if ($this->shouldStoreRemember($remember)) {
                $this->storeRememberInSession($remember);
            }
        }

        return $next($request);
    }

    /**
     * Normalizes the "remember" parameter.
     *
     * @param  string  $remember
     * @return bool|int
     */
    protected function normalizeRemember(string $remember): bool|int
    {
        return match($remember) {
            'null' => config('recaptcha.remember.enabled', false), // Get the config default.
            'false' => false, // Disable any check.
            default => (int) $remember; // Transform it to a minutes offset (zero means infinite).
        }
    }

    /**
     * Checks if the remember is disabled or has expired.
     *
     * @param  null|bool|int  $remember
     * @return bool
     */
    protected function doesntRemember(bool|int $remember): bool
    {
        // If the remember is explicitely disabled, no remember is done.
        if ($remember === false) {
            return true;
        }

        // If we didn't find any remember data in the session either.
        if (null === $timestamp = session()->get(config('recaptcha.remember.key', '_recaptcha')) {
            return true;
        }

        // If the expiration is not forever and has expired.
        return $timestamp !== 0 && now()->timestamp > $timestamp;
    }

    /**
     * Check if the reCAPTCHA challenge should be remembered.
     *
     * @param  bool|int $remember
     * @return bool
     */
    protected function shouldStoreRemember(bool|int $remember): bool
    {
        return $remember !== false
            && (! session()->has(config('recaptcha.remember.key', '_recaptcha')) || config('recaptcha.remember.renew', false));
    }

    /**
     * Stores the recaptcha remember expiration time in the session.
     *
     * @param  int  $offset
     * @return void
     */
    protected function storeRememberInSession(int $offset): void
    {
        session()->set(config('recaptcha.remember.key', '_recaptcha'), $offset ?: now()->addMinutes($offset)->timestamp);
    }
}
