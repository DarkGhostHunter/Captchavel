<?php

namespace DarkGhostHunter\Captchavel\Tests;

use DarkGhostHunter\Captchavel\Exceptions\RecaptchaNotResolvedException;
use DarkGhostHunter\Captchavel\ReCaptcha;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use ReCaptcha\Response;

class RecaptchaResponseHolderTest extends TestCase
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

    public function testReCaptchaResponse()
    {
        $response = \Mockery::mock(Response::class);

        $holder = new ReCaptcha();

        $holder->setResponse($response);

        $this->assertInstanceOf(Response::class, $holder->response());
    }

    public function testThreshold()
    {
        $holder = new ReCaptcha();

        $holder->setThreshold(0.4);

        $this->assertIsFloat($holder->getThreshold());
    }

    public function testSince()
    {
        $response = \Mockery::mock(Response::class);
        $response->shouldReceive('getChallengeTs')
            ->once()
            ->andReturn(Carbon::now()->toIso8601ZuluString());

        $holder = new ReCaptcha();

        $holder->setResponse($response);

        $this->assertInstanceOf(Carbon::class, $holder->since());
    }

    public function testThresholdOverScore()
    {
        $response = \Mockery::mock(Response::class);
        $response->shouldReceive('getScore')
            ->twice()
            ->andReturn(0.8);

        $holder = new ReCaptcha();

        $holder->setResponse($response);
        $holder->setThreshold(0.5);

        $this->assertTrue($holder->isHuman());
        $this->assertFalse($holder->isRobot());
    }

    public function testThresholdUnderScore()
    {
        $response = \Mockery::mock(Response::class);
        $response->shouldReceive('getScore')
            ->twice()
            ->andReturn(0.2);

        $holder = new ReCaptcha();

        $holder->setResponse($response);
        $holder->setThreshold(0.5);

        $this->assertTrue($holder->isRobot());
        $this->assertFalse($holder->isHuman());
    }

    public function testIsResolved()
    {
        $holder = new ReCaptcha();

        $this->assertFalse($holder->isResolved());

        $holder->setResponse(\Mockery::mock(Response::class));

        $this->assertTrue($holder->isResolved());
    }

    public function testExceptionOnHumanCheck()
    {
        $this->expectException(RecaptchaNotResolvedException::class);

        (new ReCaptcha())->isHuman();
    }

    public function testExceptionOnRobotCheck()
    {
        $this->expectException(RecaptchaNotResolvedException::class);

        (new ReCaptcha())->isRobot();
    }

    public function testExceptionOnSinceCheck()
    {
        $this->expectException(RecaptchaNotResolvedException::class);

        (new ReCaptcha())->since();
    }
}
