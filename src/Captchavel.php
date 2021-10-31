<?php

namespace DarkGhostHunter\Captchavel;

use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;
use LogicException;

/**
 * @internal
 */
class Captchavel
{
    // Constants to identify each reCAPTCHA service.
    public const CHECKBOX = 'checkbox';
    public const INVISIBLE = 'invisible';
    public const ANDROID = 'android';
    public const SCORE = 'score';

    /**
     * reCAPTCHA v2 secret for testing on "localhost".
     *
     * @var string
     */
    public const TEST_V2_SECRET = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

    /**
     * reCAPTCHA v2 site key for testing on "localhost".
     *
     * @var string
     */
    public const TEST_V2_KEY = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';

    /**
     * The URL where the reCAPTCHA challenge should be verified.
     *
     * @var string
     */
    public const RECAPTCHA_ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * The name of the input for a reCAPTCHA frontend response.
     *
     * @var string
     */
    public const INPUT = 'g-recaptcha-response';

    /**
     * If Captchavel is enabled;
     *
     * @var bool|mixed
     */
    protected bool $enabled = false;

    /**
     * If this should fake responses.
     *
     * @var bool|mixed
     */
    protected bool $fake = false;

    /**
     * Create a new Captchavel instance.
     *
     * @param  \Illuminate\Http\Client\Factory  $http
     * @param  \Illuminate\Contracts\Config\Repository  $config
     */
    public function __construct(protected Factory $http, protected Repository $config)
    {
        $this->enabled = $this->config->get('captchavel.enable');
        $this->fake = $this->config->get('captchavel.fake');
    }

    /**
     * Check if Captchavel is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if Captchavel is disabled.
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return !$this->isEnabled();
    }

    /**
     * Check if the reCAPTCHA response should be faked on-demand.
     *
     * @return bool
     */
    public function shouldFake(): bool
    {
        return $this->fake;
    }

    /**
     * Returns the reCAPTCHA response.
     *
     * @return \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse
     */
    public function response(): ReCaptchaResponse
    {
        return app(ReCaptchaResponse::class);
    }

    /**
     * Resolves a reCAPTCHA challenge.
     *
     * @param  string|null  $challenge
     * @param  string  $ip
     * @param  string  $version
     * @param  string  $input
     * @param  string|null  $action
     * @return \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse
     */
    public function getChallenge(
        ?string $challenge,
        string $ip,
        string $version,
        string $input,
        string $action = null,
    ): ReCaptchaResponse
    {
        return new ReCaptchaResponse($this->request($challenge, $ip, $version), $input, $action);
    }

    /**
     * Creates a Pending Request or a Promise.
     *
     * @param  string  $challenge
     * @param  string  $ip
     * @param  string  $version
     * @return \GuzzleHttp\Promise\PromiseInterface<\Illuminate\Http\Client\Response>
     */
    protected function request(string $challenge, string $ip, string $version): PromiseInterface
    {
        return $this->http
            ->asForm()
            ->async()
            ->withOptions(['version' => 2.0])
            ->post(static::RECAPTCHA_ENDPOINT, [
                'secret'   => $this->secret($version),
                'response' => $challenge,
                'remoteip' => $ip,
            ]);
    }

    /**
     * Sets the correct credentials to use to retrieve the challenge results.
     *
     * @param  string  $version
     * @return string
     */
    protected function secret(string $version): string
    {
        return $this->config->get("captchavel.credentials.$version.secret")
            ?? throw new LogicException("The reCAPTCHA secret for [$version] doesn't exists or is not set.");
    }
}
