<?php

namespace DarkGhostHunter\Captchavel;

use DarkGhostHunter\Captchavel\Http\Middleware\VerifyReCaptchaV2;
use DarkGhostHunter\Captchavel\Http\Middleware\VerifyReCaptchaV3;
use LogicException;
use Stringable;

use function config;
use function implode;
use function max;
use function rtrim;

class ReCaptcha implements Stringable
{
    /**
     * Create a new middleware helper instance.
     *
     * @param  string  $version
     * @param  string  $input
     * @param  string  $threshold
     * @param  string  $action
     * @param  string  $remember
     * @param  string[]  $guards
     */
    public function __construct(
        protected string $version,
        protected string $input = Captchavel::INPUT,
        protected string $threshold = 'null',
        protected string $action = 'null',
        protected string $remember = 'null',
        protected array $guards = [],
    )
    {
        //
    }

    /**
     * Create a new helper instance for checkbox challenges.
     *
     * @return static
     */
    public static function checkbox(): static
    {
        return new static(Captchavel::CHECKBOX);
    }

    /**
     * Create a new helper instance for invisible challenges.
     *
     * @return static
     */
    public static function invisible(): static
    {
        return new static(Captchavel::INVISIBLE);
    }

    /**
     * Create a new helper instance for android challenges.
     *
     * @return static
     */
    public static function android(): static
    {
        return new static(Captchavel::ANDROID);
    }

    /**
     * Create a new helper instance for score challenges.
     *
     * @param  float|null  $threshold
     * @return static
     */
    public static function score(float $threshold = null): static
    {
        return (new static(Captchavel::SCORE))
            ->threshold($threshold ?? config('captchavel.threshold', 0.5));
    }

    /**
     * Sets the input for the reCAPTCHA challenge on this route.
     *
     * @param  string  $name
     * @return $this
     */
    public function input(string $name): static
    {
        $this->input = $name;

        return $this;
    }

    /**
     * Bypass the check on users authenticated in the given guards.
     *
     * @param  string  ...$guards
     * @return $this
     */
    public function except(string ...$guards): static
    {
        $this->guards = $guards;

        return $this;
    }

    /**
     * Remembers any successful challenge made to bypass checking it on this route.
     *
     * @param  int|null  $minutes
     * @return static
     */
    public function remember(int $minutes = null): static
    {
        $this->ensureVersionIsCorrect(true);

        $this->minutes = (string) ($minutes ?? config('recaptcha.remember.minutes', 10));

        return $this;
    }

    /**
     * Doesn't remembers any successful challenge to bypass checking it on this route.
     *
     * @return static
     */
    public function dontRemember(): static
    {
        $this->ensureVersionIsCorrect(true);

        $this->minutes = 'false';

        return $this;
    }

    /**
     * Sets the threshold for the score-driven challenge.
     *
     * @param  float  $threshold
     * @return $this
     */
    public function threshold(float $threshold): static
    {
        $this->ensureVersionIsCorrect(false);

        $this->threshold = number_format(max(0, min(1, $threshold)), 1);

        return $this;
    }

    /**
     * Sets the action for the
     *
     * @param  string  $action
     * @return $this
     */
    public function action(string $action): static
    {
        $this->ensureVersionIsCorrect(false);

        $this->action = $action;

        return $this;
    }

    /**
     * Throws an exception if this middleware version should be score or not.
     * 
     * @param  bool  $score
     * @return void
     */
    protected ensureVersionIsCorrect(bool $score): void
    {
        if ($score ? $this->version === Captchavel::SCORE : $this->version !== Captchavel::SCORE) {
            $function = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[1]['function'];

            throw new LogicException("You cannot set [$function] for a [$this->version] middleware.");
        }
    }

    /**
     * Transforms the middleware helper into a string.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->__toString();
    }

    /**
     * Returns the string representation of the instance.
     *
     * @return string
     */
    public function __toString(): string
    {
        $string = $this->version === Captchavel::SCORE
            ? VerifyReCaptchaV3::SIGNATURE . ':' . implode(',', [$this->threshold, $this->action, $this->remember])
            : VerifyReCaptchaV2::SIGNATURE . ':' . $this->version;

        return rtrim($string . ',' . implode(',', [$this->input, implode(',', $this->guards)]), ',');
    }
}