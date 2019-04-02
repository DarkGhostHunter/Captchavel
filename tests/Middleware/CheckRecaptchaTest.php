<?php

namespace DarkGhostHunter\Captchavel\Tests;

use DarkGhostHunter\Captchavel\Exceptions\FailedRecaptchaException;
use DarkGhostHunter\Captchavel\Exceptions\InvalidCaptchavelMiddlewareMethod;
use DarkGhostHunter\Captchavel\Exceptions\InvalidRecaptchaException;
use DarkGhostHunter\Captchavel\Http\Middleware\CheckRecaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;
use ReCaptcha\RequestParameters;
use ReCaptcha\Response;

class CheckRecaptchaTest extends TestCase
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

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['env'] = 'local';

        $app->make('config')->set('captchavel.enable_local', true);
        $app->make('config')->set('captchavel.secret', 'test-secret');
        $app->make('config')->set('captchavel.key', 'test-key');

        $app->make('router')->get('test-get-with-middleware', function () { return 'true'; })->middleware('recaptcha');
        $app->make('router')->get('test-get', function () { return 'true'; });
        $app->make('router')->post('test-post', function () { return 'true'; })->middleware('recaptcha');
    }

    public function testRequestWithCaptchaValidates()
    {
        $mockRequester = \Mockery::mock(RequestMethod::class);
        $mockRequester->shouldReceive('submit')->andReturn(json_encode([
            'success' => true,
            'score' => 0.8,
            'action' => '/test-post',
            'challenge_ts' => Carbon::now()->toIso8601ZuluString(),
        ]));

        $this->app->when(ReCaptcha::class)
            ->needs(RequestMethod::class)
            ->give(function ($app) use ($mockRequester) {
                return $mockRequester;
            });

        $this->post('test-post', [
            '_recaptcha' => Str::random(356)
        ])->assertOk();
    }

    public function testFailsOnNonPostMethod()
    {
        $this->app->make('router')->get('get-route', function () { return 'true'; })->middleware('recaptcha');
        $this->app->make('router')->match(['head'], 'head-route', function () { return 'true'; })->middleware('recaptcha');

        $response = $this->get('get-route');

        $response->assertStatus(500);
        $this->assertInstanceOf(InvalidCaptchavelMiddlewareMethod::class, $response->exception);

        $response = $this->call('head', 'head-route');

        $response->assertStatus(500);
        $this->assertInstanceOf(InvalidCaptchavelMiddlewareMethod::class, $response->exception);
    }

    public function testFailsInvalidToken()
    {
        $response = $this->post('test-post', [ '_recaptcha' => Str::random(357)]);

        $response->assertStatus(500);
        $this->assertInstanceOf(InvalidRecaptchaException::class, $response->exception);
    }

    public function testFailsInvalidRecaptcha()
    {
        $mockRequester = \Mockery::mock(RequestMethod::class);
        $mockRequester->shouldReceive('submit')->andReturn(json_encode([
            'success' => false,
            'score' => 0.8,
            'action' => '/test-post',
            'challenge_ts' => Carbon::now()->toIso8601ZuluString(),
        ]));

        $this->app->when(ReCaptcha::class)
            ->needs(RequestMethod::class)
            ->give(function ($app) use ($mockRequester) {
                return $mockRequester;
            });

        $response = $this->post('test-post', [ '_recaptcha' => Str::random(356)]);

        $response->assertStatus(500);
        $this->assertInstanceOf(FailedRecaptchaException::class, $response->exception);
    }

    public function testMiddlewareAcceptsParameter()
    {
        $mockReCaptchaFactory = \Mockery::mock(ReCaptcha::class);
        $mockReCaptchaFactory->shouldReceive('setExpectedAction')
            ->once()
            ->andReturnSelf();
        $mockReCaptchaFactory->shouldReceive('setScoreThreshold')
            ->once()
            ->withArgs(function ($threshold) {
                $this->assertEquals(0.9, $threshold);
                return true;
            })
            ->andReturnSelf();
        $mockReCaptchaFactory->shouldReceive('verify')
            ->once()
            ->andReturn(new Response(true, [], null, null, null, 1.0, null));

        $this->app->when(CheckRecaptcha::class)
            ->needs(ReCaptcha::class)
            ->give(function () use ($mockReCaptchaFactory) {
                return $mockReCaptchaFactory;
            });

        $this->app->make('router')
            ->post('test-post', function () {
                $this->assertTrue($this->app->make('recaptcha')->isResolved());
                $this->assertTrue($this->app->make('recaptcha')->isHuman());
            })
            ->middleware('recaptcha:0.9');

        $this->post('test-post', [ '_recaptcha' => Str::random(356)])
            ->assertOk();
    }
}
