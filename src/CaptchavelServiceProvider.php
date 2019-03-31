<?php

namespace DarkGhostHunter\Captchavel;

use Captchavel\Http\Middleware\CheckRecaptcha;
use Captchavel\Http\Middleware\InjectRecaptchaScript;
use Captchavel\Http\Middleware\TransparentMiddleware;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;
use ReCaptcha\Response;

class CaptchavelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'captchavel');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'captchavel');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('captchavel.php'),
            ], 'config');

            // Publishing the views.
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/captchavel'),
            ], 'views');

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/captchavel'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/captchavel'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        }

        $this->bootMiddleware();
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'captchavel');

        // When the application tries to resolve the ReCaptcha instance, we will pass the Site Key.
        $this->app->when(ReCaptcha::class)->needs('secret')->give(function ($app) {
            /** @var \Illuminate\Foundation\Application $app */
            return $app->make('config')->get('captchavel.secret');
        });

        $this->app->bind('recaptcha', RecaptchaResponseHolder::class);
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

        if (! $this->app->environment('testing', 'debug', 'local')) {

            $router->aliasMiddleware('recaptcha', CheckRecaptcha::class);

            if ($this->app->make('config')->get('captchavel.mode') === 'auto') {
                $router->pushMiddlewareToGroup('web', InjectRecaptchaScript::class);
            }
        }

        $router->aliasMiddleware('recaptcha', TransparentMiddleware::class);
    }

}
