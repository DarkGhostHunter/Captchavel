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

        [$shouldRemember, $offset] = $this->normalizeRemember($remember);

        $captchavel = CaptchavelFacade::getFacadeRoot();

        if ($captchavel->isEnabled() && $this->isGuest($guards) && !$shouldRemember && $this->hasNoRemember() && !$captchavel->shouldFake())) {
            $this->ensureChallengeIsPresent($request, $input = $this->normalizeInput($input));

            app()->instance(
                ReCaptchaResponse::class,
                $captchavel->getChallenge($request->input($input), $request->ip(), $version, $input)->wait()
            );

            if ($shouldRemember && $this->shouldStoreRemember()) {
                $this->storeRememberInSession($offset);
            }
        }

        return $next($request);
    }

    /**
     * Normalizes the "remember" parameter.
     *
     * @param  string  $remember
     * @return array
     */
    protected function normalizeRemember(string $remember): array
    {
        return $enabled = match($remember) {
            'null'  => [config('recaptcha.remember.enabled', false), config('recaptcha.remember.minutes', 10)],
            'false' => [false, 0],
            'true'  => [true, config('recaptcha.remember.minutes', 10)],
            default => [true, (int) $remember],
        }
    }

    /**
     * Checks if the remember is disabled or has expired.
     *
     * @return bool
     */
    protected function doesntRemember(): bool
    {
        $timestamp = session()->get(config('recaptcha.remember.key', '_recaptcha'));

        // If we didn't find any remember data in the session.
        if ($timestamp === null) {
            return true;
        }

        // If the expiration is not forever and has expired.
        return $timestamp !== 0 && now()->timestamp > $timestamp;
    }

    /**
     * Check if the reCAPTCHA challenge should be remembered.
     *
     * @return bool
     */
    protected function shouldStoreRemember(): bool
    {
        return session()->missing(config('recaptcha.remember.key', '_recaptcha')) 
            || config('recaptcha.remember.renew', false);
    }

    /**
     * Stores the recaptcha remember expiration time in the session.
     *
     * @param  int  $offset
     * @return void
     */
    protected function storeRememberInSession(int $offset): void
    {
        // If the offset is over zero, we will set it as offset minutes.
        if ($offset) {
            $offset = now()->addMinutes($offset)->timestamp;
        }

        session()->set(config('recaptcha.remember.key', '_recaptcha'), $offset);
    }
}
