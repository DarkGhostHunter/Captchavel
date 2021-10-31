<?php

namespace DarkGhostHunter\Captchavel;

use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use DarkGhostHunter\Captchavel\Http\ReCaptchaV3Response;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\Response;

use function json_encode;
use function now;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
class CaptchavelFake extends Captchavel
{
    /**
     * Score to fake
     *
     * @var float|null
     */
    public ?float $score = null;

    /**
     * @inheritDoc
     */
    public function getChallenge(
        ?string $challenge,
        string $ip,
        string $version,
        string $input,
        string $action = null,
    ): ReCaptchaResponse
    {
        return new ReCaptchaResponse(
            new FulfilledPromise(
                new Response(
                    new GuzzleResponse(
                        200,
                        ['Content-type' => 'application/json'],
                        json_encode([
                            'success'          => true,
                            'action'           => null,
                            'hostname'         => null,
                            'apk_package_name' => null,
                            'challenge_ts'     => now()->toAtomString(),
                            'score'            => $this->score ?? 1.0,
                        ], JSON_THROW_ON_ERROR)
                    )
                )
            ),
            $input,
        );
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
    public function fakeRobot(): void
    {
        $this->fakeScore(0);
    }

    /**
     * Makes a fake Captchavel response made by a human with "1.0" score.
     *
     * @return void
     */
    public function fakeHuman(): void
    {
        $this->fakeScore(1.0);
    }
}
