<?php

namespace DarkGhostHunter\Captchavel\Http;

use DarkGhostHunter\Captchavel\Events\ReCaptchaScoredHuman;

trait CheckScore
{
    /**
     * The threshold to compare score-driven responses.
     *
     * @var float
     */
    protected float $threshold = 1.0;

    /**
     * Sets the threshold to compare score-driven responses.
     *
     * @param  float  $threshold
     * @return $this
     */
    public function setThreshold(float $threshold): static
    {
        $this->threshold = $threshold;

        return $this;
    }

    /**
     * Check if the request was made by a human.
     *
     * @return bool  If the response is V2, this always returns false.
     */
    public function isHuman(): bool
    {
        return $this->get('score', 1.0) >= $this->threshold;
    }

    /**
     * Check if the request was made by a robot.
     *
     * @return bool  If the response is V2, this always returns false.
     */
    public function isRobot(): bool
    {
        return ! $this->isHuman();
    }
}
