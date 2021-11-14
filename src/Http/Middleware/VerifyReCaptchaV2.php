<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use Closure;
use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\Facades\Captchavel as CaptchavelFacade;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use LogicException;

use function app;
use function config;
use function is_numeric;
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
     * Application config.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected Repository $config;

    /**
     * Captchavel instance.
     *
     * @var \DarkGhostHunter\Captchavel\Captchavel
     */
    protected Captchavel $captchavel;

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
     * @throws \Illuminate\Validation\ValidationException|\JsonException
     */
    public function handle(
        Request $request,
        Closure $next,
        string $version,
        string $remember = 'null',
        string $input = Captchavel::INPUT,
        string ...$guards
    ): mixed {
        if ($version === Captchavel::SCORE) {
            throw new LogicException('Use the [recaptcha.score] middleware to capture score-driven reCAPTCHA.');
        }

        $this->config = config();
        $this->captchavel = CaptchavelFacade::getFacadeRoot();

        if ($this->shouldCheckReCaptcha($remember, $guards)) {
            $this->ensureChallengeIsPresent($request, $input = $this->normalizeInput($input));

            app()->instance(
                ReCaptchaResponse::class,
                $this->captchavel->getChallenge($request->input($input), $request->ip(), $version, $input)->wait()
            );

            if ($this->shouldCheckRemember($remember)) {
                $this->storeRememberInSession($remember);
            }
        }

        return $next($request);
    }

    /**
     * Check if the reCAPTCHA should be checked for this request.
     *
     * @param  string  $remember
     * @param  array  $guards
     * @return bool
     */
    protected function shouldCheckReCaptcha(string $remember, array $guards): bool
    {
        if ($this->captchavel->isDisabled()) {
            return false;
        }

        if ($this->captchavel->shouldFake()) {
            return false;
        }

        if ($this->shouldCheckRemember($remember) && $this->hasRemember()) {
            return false;
        }

        return $this->isGuest($guards);
    }

    /**
     * Check if the "remember" should be checked.
     *
     * @param  string  $remember
     * @return bool
     */
    protected function shouldCheckRemember(string $remember): bool
    {
        if ($remember === 'null') {
            $remember = $this->config->get('captchavel.remember.enabled', false);
        }

        return !($remember === false || $remember === 'false');
    }

    /**
     * Check if the request "remember" should be checked.
     *
     * @return bool
     */
    protected function hasRemember(): bool
    {
        $timestamp = session()->get($this->config->get('recaptcha.remember.key', '_recaptcha'));

        if ($timestamp === null) {
            return false;
        }

        // Check the session has a "forever" expiration, or is not expired.
        return !$timestamp || now()->timestamp < $timestamp;
    }

    /**
     * Stores the recaptcha remember expiration time in the session.
     *
     * @param  string|int  $offset
     * @return void
     */
    protected function storeRememberInSession(string|int $offset): void
    {
        if (! is_numeric($offset)) {
            $offset = $this->config->get('captchavel.remember.minutes', 10);
        }

        $offset = (int) $offset;

        // If the offset is over zero, we will set it as offset minutes.
        if ($offset) {
            $offset = now()->addMinutes($offset)->getTimestamp();
        }

        session()->put($this->config->get('captchavel.remember.key', '_recaptcha'), $offset);
    }
}
