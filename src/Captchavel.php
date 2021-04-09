<?php

namespace DarkGhostHunter\Captchavel;

use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use LogicException;
use RuntimeException;

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
     * Laravel HTTP Client factory.
     *
     * @var \Illuminate\Http\Client\Factory
     */
    protected Factory $http;

    /**
     * Config Repository.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected Repository $config;

    /**
     * Create a new Captchavel instance.
     *
     * @param  \Illuminate\Http\Client\Factory  $http
     * @param  \Illuminate\Contracts\Config\Repository  $config
     */
    public function __construct(Factory $http, Repository $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    /**
     * Resolves a reCAPTCHA challenge.
     *
     * @param  string  $challenge
     * @param  string  $ip
     * @param  string  $version
     *
     * @return \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse
     */
    public function getChallenge(string $challenge, string $ip, string $version): ReCaptchaResponse
    {
        $response = $this->send($challenge, $ip, $this->useCredentials($version))
            ->setVersion($version)
            ->setAsResolved();

        Container::getInstance()->instance(ReCaptchaResponse::class, $response);

        return $response;
    }

    /**
     * Sets the correct credentials to use to retrieve the challenge results.
     *
     * @param  string  $mode
     *
     * @return string
     */
    protected function useCredentials(string $mode): string
    {
        if (!in_array($mode, static::getModes())) {
            throw new LogicException('The reCAPTCHA mode must be: ' . implode(', ', static::getModes()));
        }

        if (! $key = $this->config->get("captchavel.credentials.{$mode}.secret")) {
            throw new RuntimeException("The reCAPTCHA secret for [{$mode}] doesn't exists");
        }

        return $key;
    }

    /**
     * Retrieves the Response Challenge.
     *
     * @param  string  $challenge
     * @param  string  $ip
     * @param  string  $secret
     *
     * @return \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse
     */
    protected function send(string $challenge, string $ip, string $secret): ReCaptchaResponse
    {
        $response = $this->http
            ->asForm()
            ->withOptions(['version' => 2.0])
            ->post(static::RECAPTCHA_ENDPOINT, ['secret' => $secret, 'response' => $challenge, 'remoteip' => $ip]);

        return $this->parse($response);
    }

    /**
     * Parses the Response
     *
     * @param  \Illuminate\Http\Client\Response  $response
     *
     * @return \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse
     */
    protected function parse(Response $response): ReCaptchaResponse
    {
        return new ReCaptchaResponse($response->json());
    }

    /**
     * Checks if the mode is a valid mode name.
     *
     * @return array|string[]
     */
    protected static function getModes(): array
    {
        return [static::CHECKBOX, static::INVISIBLE, static::ANDROID, static::SCORE];
    }
}
