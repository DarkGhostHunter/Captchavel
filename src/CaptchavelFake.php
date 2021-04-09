<?php

namespace DarkGhostHunter\Captchavel;

use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;

class CaptchavelFake extends Captchavel
{
    /**
     * Score to fake
     *
     * @var float|null
     */
    public ?float $score = null;

    /**
     * Resolves a reCAPTCHA challenge.
     *
     * @param  string|null  $challenge
     * @param  string  $ip
     * @param  string  $version
     *
     * @return \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse
     */
    public function getChallenge(?string $challenge = null, string $ip, string $version): ReCaptchaResponse
    {
        return (new ReCaptchaResponse(
            [
                'success' => true,
                'action' => null,
                'hostname' => null,
                'apk_package_name' => null,
                'challenge_ts' => now()->toAtomString(),
                'score' => $this->score,
            ]
        ))->setVersion(Captchavel::SCORE)->setAsResolved();
    }

    /**
     * Adds a fake score to return as a reCAPTCHA response.
     *
     * @param  float  $score
     *
     * @return void
     */
    public function fakeScore(float $score): void
    {
        $this->score = $score;
    }

    /**
     * Makes a fake Captchavel response made by a robot with "0" score.
     *
     * @return void
     */
    public function fakeRobots(): void
    {
        $this->score = 0;
    }

    /**
     * Makes a fake Captchavel response made by a human with "1.0" score.
     *
     * @return void
     */
    public function fakeHumans(): void
    {
        $this->score = 1.0;
    }
}
