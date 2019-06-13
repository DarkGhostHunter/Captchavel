<?php

namespace DarkGhostHunter\Captchavel\Tests;

use DarkGhostHunter\Captchavel\CaptchavelServiceProvider;
use DarkGhostHunter\Captchavel\Http\Middleware\CheckRecaptcha;
use DarkGhostHunter\Captchavel\Http\Middleware\InjectRecaptchaScript;
use DarkGhostHunter\Captchavel\Http\Middleware\TransparentRecaptcha;
use DarkGhostHunter\Captchavel\ReCaptcha;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use ReCaptcha\ReCaptcha as ReCaptchaFactory;

class ServiceProviderTest extends TestCase
{

    protected function getPackageAliases($app)
    {
        return [
            'ReCaptcha' => 'DarkGhostHunter\Captchavel\Facades\ReCaptcha'
        ];
    }

    protected function getPackageProviders($app)
    {
        return ['DarkGhostHunter\Captchavel\CaptchavelServiceProvider'];
    }

    public function testRegistersPackage()
    {
        $instance = $this->app->make('recaptcha');

        $this->assertInstanceOf(ReCaptcha::class, $instance);
    }

    public function testRecaptchaFacade()
    {
        $this->assertInstanceOf(ReCaptcha::class, \ReCaptcha::getFacadeRoot());
    }

    public function testReceivesConfig()
    {
        $this->assertEquals(
            include __DIR__.'/../config/captchavel.php',
            $this->app->make('config')->get('captchavel')
        );
    }

    public function testResolvesRecaptchaResponse()
    {
        config()->set('captchavel.secret', Str::random());

        $instance = app()->make(ReCaptchaFactory::class);

        $this->assertInstanceOf(ReCaptchaFactory::class, $instance);
    }

    public function testDoesntResolveRecaptchaWithoutSecret()
    {
        $this->expectException(\RuntimeException::class);

        $instance = app()->make(ReCaptchaFactory::class);

        $this->assertInstanceOf(ReCaptchaFactory::class, $instance);
    }

    public function testRegisterMiddleware()
    {
        $this->app['env'] = 'production';

        /** @var CaptchavelServiceProvider $provider */
        $provider = app()->make(CaptchavelServiceProvider::class, ['app' => $this->app]);

        $provider->boot();

        $middleware = $this->app->make('router')->getMiddleware();

        $this->assertArrayHasKey('recaptcha', $middleware);
        $this->assertEquals($middleware['recaptcha'], CheckRecaptcha::class);
        $this->assertEquals($middleware['recaptcha-inject'], InjectRecaptchaScript::class);
    }

    public function testDoesntRegisterMiddlewareOnTesting()
    {
        $this->assertFalse($this->app->make(Kernel::class)->hasMiddleware(CheckRecaptcha::class));
        $middleware = $this->app->make('router')->getMiddleware();

        $this->assertEquals(TransparentRecaptcha::class, $middleware['recaptcha']);
    }

    public function testRegisterTransparentMiddlewareOnNotProduction()
    {
        $this->app['env'] = 'local';

        /** @var CaptchavelServiceProvider $provider */
        $provider = $this->app->make(CaptchavelServiceProvider::class, ['app' => $this->app]);

        $provider->boot();

        $middleware = $this->app->make('router')->getMiddleware();

        $this->assertEquals(TransparentRecaptcha::class, $middleware['recaptcha']);
    }

    public function testRegisterInjectMiddlewareOnAuto()
    {
        $this->app['env'] = 'production';

        /** @var CaptchavelServiceProvider $provider */
        $provider = $this->app->make(CaptchavelServiceProvider::class, ['app' => $this->app]);

        $provider->boot();

        $this->assertTrue(
            $this->app->make(Kernel::class)->hasMiddleware(InjectRecaptchaScript::class)
        );
    }

    public function testDoesntRegisterInjectMiddlewareOnNonAuto()
    {
        $this->app['env'] = 'production';
        $this->app['config']->set('captchavel.mode', 'manual');

        /** @var CaptchavelServiceProvider $provider */
        $provider = $this->app->make(CaptchavelServiceProvider::class, ['app' => $this->app]);

        $provider->boot();

        $this->assertFalse(
            $this->app->make(Kernel::class)->hasMiddleware(InjectRecaptchaScript::class)
        );
    }

    public function testRegisterMiddlewareOnLocalTrue()
    {
        $this->app['env'] = 'local';
        $this->app['config']->set('captchavel.enable_local', true);

        /** @var CaptchavelServiceProvider $provider */
        $provider = $this->app->make(CaptchavelServiceProvider::class, ['app' => $this->app]);

        $provider->boot();

        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make('router');

        $this->assertEquals(CheckRecaptcha::class, $router->getMiddleware()['recaptcha']);
    }

    public function testPublishesConfigFile()
    {
        $this->artisan('vendor:publish', [
            '--provider' => CaptchavelServiceProvider::class
        ]);

        $this->assertFileExists(config_path('captchavel.php'));
        $this->assertFileIsReadable(config_path('captchavel.php'));
        $this->assertFileEquals(config_path('captchavel.php'), __DIR__ . '/../config/captchavel.php');
        $this->assertTrue(unlink(config_path('captchavel.php')));
    }

    public function testRegistersMacros()
    {
        \DarkGhostHunter\Captchavel\Facades\ReCaptcha::shouldReceive('isHuman')
            ->once()
            ->andReturnTrue();

        \DarkGhostHunter\Captchavel\Facades\ReCaptcha::shouldReceive('isRobot')
            ->once()
            ->andReturnFalse();

        $this->assertTrue(Request::isHuman());
        $this->assertFalse(Request::isRobot());
    }

}
