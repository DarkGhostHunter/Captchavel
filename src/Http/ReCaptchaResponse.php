<?php

namespace DarkGhostHunter\Captchavel\Http;

use DarkGhostHunter\Captchavel\Captchavel;
use Illuminate\Support\Fluent;
use RuntimeException;

/**
 * @property-read null|string $hostname
 * @property-read null|string $challenge_ts
 * @property-read null|string $apk_package_name
 * @property-read null|float $score
 * @property-read null|string $action
 * @property-read array $error_codes
 * @property-read bool $success
 */
class ReCaptchaResponse extends Fluent
{
    /**
     * Default reCAPTCHA version.
     *
     * @var string|null
     */
    public ?string $version = null;

    /**
     * The threshold for reCAPTCHA v3.
     *
     * @var float
     */
    protected float $threshold = 1.0;

    /**
     * Check if the response from reCAPTCHA servers has been received.
     *
     * @var mixed
     */
    protected bool $resolved = false;

    /**
     * Sets the threshold to check the response.
     *
     * @param  float $threshold
     * @return $this
     */
    public function setThreshold(float $threshold): ReCaptchaResponse
    {
        $this->threshold = $threshold;

        return $this;
    }

    /**
     * Sets the reCAPTCHA response as resolved.
     *
     * @return $this
     */
    public function setAsResolved(): ReCaptchaResponse
    {
        $this->resolved = true;

        return $this;
    }

    /**
     * Check if the reCAPTCHA response has been resolved.
     *
     * @return bool
     */
    public function isResolved(): bool
    {
        return $this->resolved;
    }

    /**
     * Check if the reCAPTCHA response has not been resolved for the request.
     *
     * @return bool
     */
    public function isNotResolved():bool
    {
        return ! $this->isResolved();
    }

    /**
     * Returns if the response was made by a Human.
     *
     * @throws \LogicException
     * @return bool
     */
    public function isHuman(): bool
    {
        if ($this->isNotResolved()) {
            throw new RuntimeException('There is no reCAPTCHA v3 response resolved for this request');
        }

        if ($this->version !== Captchavel::SCORE) {
            throw new RuntimeException('This is not a reCAPTCHA v3 response');
        }

        if ($this->score === null) {
            throw new RuntimeException('This is reCAPTCHA v3 response has no score');
        }

        return $this->score >= $this->threshold;
    }

    /**
     * Returns if the response was made by a Robot.
     *
     * @return bool
     */
    public function isRobot(): bool
    {
        return ! $this->isHuman();
    }

    /**
     * Returns if the challenge is valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->success && empty($this->error_codes);
    }

    /**
     * Returns if the challenge is invalid.
     *
     * @return bool
     */
    public function isInvalid(): bool
    {
        return ! $this->isValid();
    }

    /**
     * Check if the hostname is different to the one issued.
     *
     * @param  string|null  $string
     * @return bool
     */
    public function isDifferentHostname(?string $string): bool
    {
        return $string && $this->hostname !== $string;
    }

    /**
     * Check if the APK name is different to the one issued.
     *
     * @param  string|null  $string
     * @return bool
     */
    public function isDifferentApk(?string $string): bool
    {
        return $string && $this->apk_package_name !== $string;
    }

    /**
     * Check if the action name is different to the one issued.
     *
     * @param  null|string  $action
     * @return bool
     */
    public function isDifferentAction(?string $action): bool
    {
        return $action && $this->action !== $action;
    }

    /**
     * Dynamically return an attribute as a property.
     *
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        // Minor fix for getting the error codes
        return parent::__get($key === 'error_codes' ? 'error-codes' : $key);
    }

    /**
     * Sets the version for this reCAPTCHA response.
     *
     * @param  string  $version
     *
     * @return $this
     */
    public function setVersion(string $version): ReCaptchaResponse
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Checks if the reCAPTCHA challenge is for a given version.
     *
     * @return bool
     */
    public function isCheckbox(): bool
    {
        return $this->version === Captchavel::CHECKBOX;
    }

    /**
     * Checks if the reCAPTCHA challenge is for a given version.
     *
     * @return bool
     */
    public function isInvisible(): bool
    {
        return $this->version === Captchavel::INVISIBLE;
    }

    /**
     * Checks if the reCAPTCHA challenge is for a given version.
     *
     * @return bool
     */
    public function isAndroid(): bool
    {
        return $this->version === Captchavel::ANDROID;
    }

    /**
     * Checks if the reCAPTCHA challenge is for a given version.
     *
     * @return bool
     */
    public function isScore(): bool
    {
        return $this->version === Captchavel::SCORE;
    }

}
