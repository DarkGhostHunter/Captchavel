<?php

namespace DarkGhostHunter\Captchavel;

use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;

class CaptchavelFake extends Captchavel
{
    /**
     * Sets a fake response.
     *
     * @param  \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse  $response
     * @return $this
     */
    public function setResponse(ReCaptchaResponse $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Sets the correct credentials to use to retrieve the challenge results.
     *
     * @param  int  $version
     * @param  string|null  $variant
     * @return $this
     */
    public function useCredentials(int $version, string $variant = null)
    {
        return $this;
    }

    /**
     * Retrieves the Response Challenge.
     *
     * @param  string  $challenge
     * @param  string  $ip
     * @return \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse
     */
    public function retrieve(?string $challenge, string $ip)
    {
        return $this->response;
    }

    /**
     * Makes the fake Captchavel response with a fake score.
     *
     * @param  float $score
     * @return $this
     */
    public function fakeScore(float $score)
    {
        return $this->setResponse(new ReCaptchaResponse([
            'success' => true,
            'score' => $score,
            'action' => null,
            'hostname' => null,
            'apk_package_name' => null,
        ]));
    }

    /**
     * Makes a fake Captchavel response made by a robot with "0" score.
     *
     * @return $this
     */
    public function fakeRobot()
    {
        return $this->fakeScore(0);
    }

    /**
     * Makes a fake Captchavel response made by a human with "1.0" score.
     *
     * @return $this
     */
    public function fakeHuman()
    {
        return $this->fakeScore(1);
    }
}
