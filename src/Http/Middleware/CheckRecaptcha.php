<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use DarkGhostHunter\Captchavel\ReCaptcha;
use ReCaptcha\ReCaptcha as ReCaptchaFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Validation\Factory as Validator;
use DarkGhostHunter\Captchavel\Exceptions\FailedRecaptchaException;
use DarkGhostHunter\Captchavel\Exceptions\InvalidRecaptchaException;

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
    protected $reCaptchaFactory;

    /**
     * The reCAPTCHA response holder
     *
     * @var \DarkGhostHunter\Captchavel\ReCaptcha
     */
    protected $reCaptcha;

    /**
     * CheckRecaptcha constructor.
     *
     * @param  \Illuminate\Contracts\Validation\Factory  $validator
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \ReCaptcha\ReCaptcha  $reCaptchaFactory
     * @param  \DarkGhostHunter\Captchavel\ReCaptcha  $reCaptcha
     */
    public function __construct(Validator $validator,
                                Config $config,
                                ReCaptchaFactory $reCaptchaFactory,
                                ReCaptcha $reCaptcha)
    {
        $this->validator = $validator;
        $this->config = $config->get('captchavel');
        $this->reCaptchaFactory = $reCaptchaFactory;
        $this->reCaptcha = $reCaptcha;
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
        if ($request->getRealMethod() === 'POST') {
            $this->hasValidRequest($request);
            $this->hasValidReCaptcha($request, $threshold ?? $this->config['threshold']);
        }

        return $next($request);
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
            '_recaptcha' => 'required|string',
        ])->fails();

        return throw_unless($isValid, InvalidRecaptchaException::class, $request->only('_recaptcha'));
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
     * @return \DarkGhostHunter\Captchavel\ReCaptcha
     */
    protected function resolve(Request $request, float $threshold)
    {
        return $this->reCaptcha->setResponse(
            $this->reCaptchaFactory
                ->setExpectedAction($this->sanitizeAction($request->getRequestUri()))
                ->verify($request->input('_recaptcha'), $request->getClientIp())
        )->setThreshold($threshold);
    }

    /**
     * Sanitizes the Action string to be sent to Google reCAPTCHA servers
     *
     * @param  string  $action
     * @return string|string[]|null
     */
    protected function sanitizeAction(string $action)
    {
        return preg_replace('/[^A-z\/\_]/s', '', $action);
    }
}
