<?php

namespace DarkGhostHunter\Captchavel\Tests;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;
use ReCaptcha\RequestParameters;
use ReCaptcha\Response;

class ExtendsRequestMethodTest extends TestCase
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

    public function testExtendsRequestMethod()
    {
        $requester = \Mockery::mock(RequestMethod::class);
        $requester->shouldReceive('submit')
            ->with(RequestParameters::class)
            ->andReturn(json_encode([
                'success' => $success = true,
                'score' => $score = 0.8,
                'action' => $action = 'test-action',
                'challenge_ts' => $challenge_ts = Carbon::now()->toIso8601ZuluString(),
            ]));

        config()->set('captchavel.request_method', $requester);
        config()->set('captchavel.secret', Str::random());

        $this->app->when(ReCaptcha::class)
            ->needs(RequestMethod::class)
            ->give(function () use ($requester) {
                return $requester;
            });

        $recaptcha = $this->app->make(ReCaptcha::class);

        $response = $recaptcha->verify('anytoken');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertEquals($response->getScore(), $score);
        $this->assertEquals($response->getAction(), $action);
        $this->assertEquals($response->getChallengeTs(), $challenge_ts);
    }

}
