<?php

namespace DarkGhostHunter\Captchavel;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Config\Repository;
use DarkGhostHunter\Captchavel\Http\Middleware\VerifyReCaptchaV2;
use DarkGhostHunter\Captchavel\Http\Middleware\VerifyReCaptchaV3;

class CaptchavelServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/captchavel.php', 'captchavel');

        $this->app->singleton(Captchavel::class);
    }

    /**
     * Bootstrap the application services.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function boot(Router $router, Repository $config)
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('captchavel.php'),
            ], 'config');

            if ($this->app->runningUnitTests()) {
                $config->set('captchavel.fake', true);
            }
        }

        $router->aliasMiddleware('recaptcha.v2', VerifyReCaptchaV2::class);
        $router->aliasMiddleware('recaptcha.v3', VerifyReCaptchaV3::class);
    }
}
