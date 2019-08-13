<?php

namespace DarkGhostHunter\Captchavel;

use DarkGhostHunter\Captchavel\Http\Middleware\CheckRecaptcha;
use DarkGhostHunter\Captchavel\Http\Middleware\InjectRecaptchaScript;
use DarkGhostHunter\Captchavel\Http\Middleware\TransparentRecaptcha;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use ReCaptcha\ReCaptcha as ReCaptchaFactory;

class CaptchavelServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/captchavel.php', 'captchavel');

        // When the application tries to resolve the ReCaptcha instance, we will pass the Site Key.
        $this->app->when(ReCaptchaFactory::class)
            ->needs('$secret')
            ->give(function ($app) {
                return $app->make('config')->get('captchavel.secret');
            });

        $this->app->singleton(ReCaptcha::class);
        $this->app->alias(ReCaptcha::class, 'recaptcha');
    }

    /**
     * Bootstrap the application services.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'captchavel');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/captchavel.php' => config_path('captchavel.php'),
            ], 'config');

            // Publishing the views.
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/captchavel'),
            ], 'views');
        }

        $this->bootMiddleware();
        $this->extendRequestMacro();
    }

    /**
     * Registers the Middleware
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function bootMiddleware()
    {
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make('router');

        // We will check if we should enable the Middleware of this package based on the environment
        // and package config. If we shouldn't, we will register a transparent middleware in the
        // application to avoid the errors when the "recaptcha" is used but not registered.
        if ($this->shouldEnableMiddleware()) {
            $this->addMiddleware($router);
        } else {
            $this->addTransparentMiddleware($router);
        }
    }

    /**
     * Returns if the application should enable ReCaptcha middleware
     *
     * @return bool
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function shouldEnableMiddleware()
    {
        return $this->app->environment('production')
            || ($this->app->environment('local') && $this->app->make('config')->get('captchavel.enable_local'));
    }

    /**
     * Registers real middleware for the package
     *
     * @param \Illuminate\Routing\Router $router
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function addMiddleware(Router $router)
    {
        $router->aliasMiddleware('recaptcha', CheckRecaptcha::class);
        $router->aliasMiddleware('recaptcha-inject', InjectRecaptchaScript::class);

        if ($this->app->make('config')->get('captchavel.mode') === 'auto') {
            $this->app->make(Kernel::class)->pushMiddleware(InjectRecaptchaScript::class);
        }
    }

    /**
     * Registers a Dummy (Transparent) Middleware
     *
     * @param \Illuminate\Routing\Router $router
     */
    protected function addTransparentMiddleware(Router $router)
    {
        $router->aliasMiddleware('recaptcha', TransparentRecaptcha::class);
    }

    /**
     * Extend the Request with a couple of macros
     *
     * @return void
     */
    protected function extendRequestMacro()
    {
        Request::macro('isHuman', function () {
            return recaptcha()->isHuman();
        });

        Request::macro('isRobot', function () {
            return recaptcha()->isRobot();
        });
    }

}
