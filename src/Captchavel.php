<?php

namespace DarkGhostHunter\Captchavel;

use LogicException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Illuminate\Contracts\Config\Repository as Config;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;

class Captchavel
{
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
    public const RECAPTCHA_ENDPOINT = 'www.google.com/recaptcha/api/siteverify';

    /**
     * The name of the input for a reCAPTCHA frontend response.
     *
     * @var string
     */
    public const INPUT = 'g-recaptcha-response';

    /**
     * The available reCAPTCHA v2 variants name.
     *
     * @var string
     */
    public const V2_VARIANTS = [
        'checkbox', 'invisible', 'android',
    ];

    /**
     * Laravel HTTP Client factory.
     *
     * @var \Illuminate\Http\Client\Factory|\Illuminate\Http\Client\PendingRequest
     */
    protected $httpFactory;

    /**
     * Config Repository.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Secret to use with given challenge.
     *
     * @var string
     */
    protected $secret;

    /**
     * The Captchavel Response created from the reCAPTCHA response.
     *
     * @var null|\DarkGhostHunter\Captchavel\Http\ReCaptchaResponse
     */
    protected $response;

    /**
     * Create a new Captchavel instance.
     *
     * @param  \Illuminate\Http\Client\Factory  $httpFactory
     * @param  \Illuminate\Contracts\Config\Repository  $config
     */
    public function __construct(Factory $httpFactory, Config $config)
    {
        $this->httpFactory = $httpFactory;
        $this->config = $config;
    }

    /**
     * Returns the Captchavel Response, if any.
     *
     * @return null|\DarkGhostHunter\Captchavel\Http\ReCaptchaResponse
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Check if the a response was resolved from reCAPTCHA servers.
     *
     * @return bool
     */
    public function isNotResolved()
    {
        return $this->response === null;
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
        if ($version === 2) {
            if (! in_array($variant, static::V2_VARIANTS, true)) {
                throw new LogicException("The reCAPTCHA v2 variant must be [checkbox], [invisible] or [android].");
            }
            $this->secret = $this->config->get("captchavel.credentials.v2.{$variant}.secret");
        } elseif ($version === 3) {
            $this->secret = $this->config->get('captchavel.credentials.v3.secret');
        }

        if (! $this->secret) {
            $name = 'v' . $version . ($variant ? '-' . $variant : '');
            throw new LogicException("The reCAPTCHA secret for [{$name}] doesn't exists.");
        }

        return $this;
    }

    /**
     * Retrieves the Response Challenge.
     *
     * @param  string  $challenge
     * @param  string  $ip
     * @return \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse
     */
    public function retrieve(string $challenge, string $ip)
    {
        $response = $this->httpFactory->asForm()
            ->withOptions(['version' => 2.0])
            ->post(static::RECAPTCHA_ENDPOINT, [
                'secret'   => $this->secret,
                'response' => $challenge,
                'remoteip' => $ip,
            ]);

        return $this->parse($response);
    }

    /**
     * Parses the Response
     *
     * @param  \Illuminate\Http\Client\Response  $response
     * @return \DarkGhostHunter\Captchavel\Http\ReCaptchaResponse
     */
    protected function parse(Response $response)
    {
        return $this->response = new ReCaptchaResponse($response->json());
    }
}
