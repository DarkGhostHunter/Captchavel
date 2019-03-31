<?php

namespace Captchavel\Http\Middleware;

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
     * @param  bool  $shouldBack
     * @return mixed
     * @throws \Throwable
     */
    public function handle($request, Closure $next, float $threshold = null, bool $shouldBack = false)
    {
        $threshold = $threshold ?? $this->config['threshold'];

        throw_unless($this->isPostMethod($request), InvalidCaptchavelMiddlewareMethod::class);

        abort_unless($this->hasValidRequest($request) && $this->hasValidReCaptcha($request, $threshold), 500);

        if ($shouldBack || $this->shouldBackOnLowScore()) {
            return back()->withInput();
        }

        return $next($request);
    }

    /**
     * Detect if the Robot should return back with the input
     *
     * @return bool
     */
    protected function shouldBackOnLowScore()
    {
        return $this->config['return_on_robot'] ? $this->response->isRobot() : false;
    }

    /**
     * Detect if the Request has been as a POST method
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isPostMethod(Request $request)
    {
        return $request->getRealMethod() === 'POST';
    }

    /**
     * Detect if the Request accepts HTML and is not an AJAX/PJAX Request
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response | \Illuminate\Http\JsonResponse  $response
     * @return bool
     */
    protected function isHtml(Request $request, $response)
    {
        return $response instanceof Response && $request->acceptsHtml() && !$request->ajax() && !$request->pjax();
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
        $holder = $this->resolve($request, $threshold);

        return throw_unless(
            $holder->response()->isSuccess(),
            FailedRecaptchaException::class,
            $holder->response()->getErrorCodes()
        );
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
        /** @var \DarkGhostHunter\Captchavel\RecaptchaResponseHolder $recaptcha */
        $recaptcha = app('recaptcha');

        if (!$recaptcha->isResolved()) {

            if ($timeout = $this->config['captchavel.timeout']) {
                $this->recaptchaFactory->setChallengeTimeout($timeout);
            }

            return $recaptcha->setResponse(
                $this->recaptchaFactory
                    ->setExpectedAction($request->getRequestUri())
                    ->setScoreThreshold($threshold ?? $this->config['threshold'])
                    ->verify($request->input('_recaptcha', $request->getClientIp()))
            );
        }

        return $recaptcha;
    }
}