<?php

namespace DarkGhostHunter\Captchavel\Http;

use LogicException;
use Illuminate\Support\Fluent;

/**
 * Class ReCaptchaResponse
 *
 * @package DarkGhostHunter\Captchavel\Http
 *
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
     * The threshold for reCAPTCHA v3.
     *
     * @var float
     */
    protected $threshold;

    /**
     * Sets the threshold to check the response.
     *
     * @param  float $threshold
     * @return $this
     */
    public function setThreshold(float $threshold)
    {
        $this->threshold = $threshold;

        return $this;
    }

    /**
     * Returns if the response was made by a Human.
     *
     * @throws \LogicException
     * @return bool
     */
    public function isHuman()
    {
        if ($this->score === null) {
            throw new LogicException('This is not a reCAPTCHA v3 response, or the score is absent.');
        }

        return $this->score >= $this->threshold;
    }

    /**
     * Returns if the response was made by a Robot.
     *
     * @return bool
     */
    public function isRobot()
    {
        return ! $this->isHuman();
    }

    /**
     * Returns if the challenge is valid.
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->success && empty($this->error_codes);
    }

    /**
     * Returns if the challenge is invalid.
     *
     * @return bool
     */
    public function isInvalid()
    {
        return ! $this->isValid();
    }

    /**
     * Check if the hostname is different to the one issued.
     *
     * @param  string|null  $string
     * @return bool
     */
    public function differentHostname(?string $string)
    {
        return $string && $this->hostname !== $string;
    }

    /**
     * Check if the APK name is different to the one issued.
     *
     * @param  string|null  $string
     * @return bool
     */
    public function differentApk(?string $string)
    {
        return $string && $this->apk_package_name !== $string;
    }

    /**
     * Check if the action name is different to the one issued.
     *
     * @param  null|string  $action
     * @return bool
     */
    public function differentAction(?string $action)
    {
        return $action && $this->action !== $action;
    }

    /**
     * Dynamically return an attribute as a property.
     *
     * @param $name
     * @return null|mixed
     */
    public function __get($name)
    {
        // Minor fix for getting the error codes
        return parent::__get($name === 'error_codes' ? 'error-codes' : $name);
    }
}
