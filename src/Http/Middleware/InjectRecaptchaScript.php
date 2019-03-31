<?php

namespace Captchavel\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\View\Factory as View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InjectRecaptchaScript
{
    /**
     * Google reCAPTCHA Site Key
     *
     * @var string
     */
    protected $key;

    /**
     * View Factory
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $view;

    /**
     * InjectRecaptchaScript constructor.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \Illuminate\Contracts\View\Factory  $view
     */
    public function __construct(Config $config, View $view)
    {
        $this->key = $config->get('captchavel.key');
        $this->view = $view;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     * @throws \Throwable
     * @throws \\Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($this->isHtml($request, $response)) {
            return $this->injectScript($response);
        }

        return $response;
    }

    /**
     * Detect if the Request accepts HTML and is not an AJAX/PJAX Request
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response | \Illuminate\Http\JsonResponse $response
     * @return bool
     */
    protected function isHtml(Request $request, $response)
    {
        return $response instanceof Response && $request->acceptsHtml() && !$request->ajax() && !$request->pjax();
    }

    /**
     * Detect if the Response has a form with reCAPTCHA data attribute
     *
     * @param  \Illuminate\Http\Response  $response
     * @return bool|int
     */
    protected function hasForm(Response $response)
    {
        return strpos('data-recaptcha="true"', $response->getContent());
    }

    /**
     * Injects the front-end Scripts
     *
     * @param  \Illuminate\Http\Response  $response
     * @return \Illuminate\Http\Response
     */
    protected function injectScript(Response $response)
    {
        // To inject the script automatically, we will do it before the ending
        // head tag. If it's not found, the response may not be valid HTML,
        // so we will bail out returning the original untouched content.
        if (!$endHeadPosition = stripos($content = $response->content(), '</head>')) {
            return $response;
        };

        $script = $this->view->make('captchavel::script', ['key' => $this->key])->render();

        // Now we will check if the response has a Form with a data attribute.
        // If it has it, we will inject the script that allows the reCAPTCHA
        // input to be rendered, and then passed down to the application.
        if ($this->hasForm($response)) {
            $script .= $this->view->make('captchavel::head-script', ['key' => $this->key])->render();
        }

        return $response->setContent(
            substr_replace($content, $script, $endHeadPosition, 0)
        );
    }
}