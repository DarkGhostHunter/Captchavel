<?php

namespace DarkGhostHunter\Captchavel;

use DarkGhostHunter\Captchavel\Http\Middleware\VerifyReCaptchaV2;
use DarkGhostHunter\Captchavel\Http\Middleware\VerifyReCaptchaV3;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

/**
 * @internal
 */
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
        $this->loadTranslationsFrom(__DIR__. '/../resources/lang', 'captchavel');

        $this->app->singleton(Captchavel::class, static function ($app): Captchavel {
            return new Captchavel($app[Factory::class], $app['config']);
        });
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
            $this->publishes([__DIR__.'/../config/captchavel.php' => config_path('captchavel.php')], 'config');

            if ($this->app->runningUnitTests()) {
                $config->set('captchavel.fake', true);
            }
        }

        $router->aliasMiddleware(VerifyReCaptchaV2::SIGNATURE, VerifyReCaptchaV2::class);
        $router->aliasMiddleware(VerifyReCaptchaV3::SIGNATURE, VerifyReCaptchaV3::class);

        Request::macro('isRobot', [RequestMacro::class, 'isRobot']);
        Request::macro('isHuman', [RequestMacro::class, 'isHuman']);
    }
}
