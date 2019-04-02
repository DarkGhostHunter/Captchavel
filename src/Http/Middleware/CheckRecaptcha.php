<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use Closure;
use DarkGhostHunter\Captchavel\Exceptions\FailedRecaptchaException;
use DarkGhostHunter\Captchavel\Exceptions\InvalidCaptchavelMiddlewareMethod;
use DarkGhostHunter\Captchavel\Exceptions\InvalidRecaptchaException;
use DarkGhostHunter\Captchavel\RecaptchaResponseHolder as RecaptchaResponse;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Validation\Factory as Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ReCaptcha\ReCaptcha as ResponseFactory;

class CheckRecaptcha
{
    /**
     * Validator
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    protected $validator;

    /**
     * Captchavel Configuration array
     *
     * @var array
     */
    protected $config;

    /**
     * ReCaptcha Client instance
     *
     * @var \ReCaptcha\ReCaptcha
     */
    protected $recaptchaFactory;

    /**
     * The reCAPTCHA response holder
     *
     * @var \DarkGhostHunter\Captchavel\RecaptchaResponseHolder
     */
    protected $response;

    /**
     * CheckRecaptcha constructor.
     *
     * @param  \Illuminate\Contracts\Validation\Factory  $validator
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \ReCaptcha\ReCaptcha  $recaptchaFactory
     * @param  \DarkGhostHunter\Captchavel\RecaptchaResponseHolder  $response
     */
    public function __construct(Validator $validator,
                                ResponseFactory $recaptchaFactory,
                                RecaptchaResponse $response,
                                Config $config)
    {
        $this->validator = $validator;
        $this->recaptchaFactory = $recaptchaFactory;
        $this->response = $response;
        $this->config = $config->get('captchavel');
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  float  $threshold
     * @return mixed
     * @throws \Throwable
     */
    public function handle($request, Closure $next, float $threshold = null)
    {
        $this->isPostMethod($request);
        $this->hasValidRequest($request);
        $this->hasValidReCaptcha($request, $threshold ?? $this->config['threshold']);

        return $next($request);
    }

    /**
     * Detect if the Request has been as a POST method
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     * @throws \Throwable
     */
    protected function isPostMethod(Request $request)
    {
        return throw_unless($request->getRealMethod() === 'POST', InvalidCaptchavelMiddlewareMethod::class);
    }

    /**
     * Return if the Request has a valid reCAPTCHA token
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     * @throws \Throwable
     */
    protected function hasValidRequest(Request $request)
    {
        $isValid = !$this->validator->make($request->only('_recaptcha'), [
            '_recaptcha' => 'required|string|size:356',
        ])->fails();

        return throw_unless($isValid, InvalidRecaptchaException::class);
    }

    /**
     * Checks if the reCAPTCHA Response is valid
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  float  $threshold
     * @return mixed
     * @throws \Throwable
     */
    protected function hasValidReCaptcha(Request $request, float $threshold)
    {
        $response = $this->resolve($request, $threshold)->response();

        return throw_unless($response->isSuccess(), FailedRecaptchaException::class, $response->getErrorCodes());
    }

    /**
     * Resolves a reCAPTCHA Request into a reCAPTCHA Response
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  float  $threshold
     * @return \DarkGhostHunter\Captchavel\RecaptchaResponseHolder
     */
    protected function resolve(Request $request, float $threshold)
    {
        return app('recaptcha')->setResponse(
            $this->recaptchaFactory
                ->setExpectedAction(preg_replace('/[^A-z\/\_]/s', '', $request->getRequestUri()))
                ->setScoreThreshold($threshold)
                ->verify($request->input('_recaptcha'), $request->getClientIp())
        );
    }
}