<?php

namespace DarkGhostHunter\Captchavel;

use Illuminate\Support\Carbon;
use ReCaptcha\Response;

class RecaptchaResponseHolder
{
    /**
     * The reCAPTCHA response
     *
     * @var \ReCaptcha\Response
     */
    protected $response;

    /**
     * The reCAPTCHA threshold to check if Human or Robot
     *
     * @var float
     */
    protected $threshold;

    /**
     * The Carbon instance of the Response resolving timestamp
     *
     * @var \Illuminate\Support\Carbon|null
     */
    protected $since;

    /**
     * Sets the Recaptcha
     *
     * @param  \ReCaptcha\Response  $response
     * @return \DarkGhostHunter\Captchavel\RecaptchaResponseHolder
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Returns if the reCAPTCHA has been resolved by the servers
     *
     * @return bool
     */
    public function isResolved()
    {
        return !is_null($this->response);
    }

    /**
     * Returns the threshold
     *
     * @return float
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * Sets the Threshold
     *
     * @param  float  $threshold
     * @return \DarkGhostHunter\Captchavel\RecaptchaResponseHolder
     */
    public function setThreshold(float $threshold)
    {
        $this->threshold = $threshold;

        return $this;
    }

    /**
     * Return if the Response was made by a Human
     *
     * @return bool
     */
    public function isHuman()
    {
        return $this->response->getScore() >= $this->threshold;
    }

    /**
     * Return if the Response was made by a Robot
     *
     * @return bool
     */
    public function isRobot()
    {
        return !$this->isHuman();
    }

    /**
     * Return the underlying reCAPTCHA response
     *
     * @return \ReCaptcha\Response
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * Return the reCAPTCHA Response timestamp as a Carbon instance
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getSince()
    {
        return $this->since ?? $this->since = Carbon::parse($this->response->getChallengeTs());
    }
}
