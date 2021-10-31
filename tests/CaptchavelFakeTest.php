<?php

namespace Tests;

use DarkGhostHunter\Captchavel\Facades\Captchavel;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use Orchestra\Testbench\TestCase;

class CaptchavelFakeTest extends TestCase
{
    use RegistersPackage;
    use Http\Middleware\UsesRoutesWithMiddleware;

    protected function setUp() : void
    {
        $this->afterApplicationCreated(function () {
            $this->createsRoutes();
        });

        parent::setUp();
    }

    public function test_using_fake_on_unit_test(): void
    {
        static::assertTrue(config('captchavel.fake'));
    }

    public function test_makes_fake_score(): void
    {
        Captchavel::fakeScore(0.3);

        $this->app['router']->post('test', function (ReCaptchaResponse $response) {
            return [$response->score, $response->isRobot(), $response->isHuman()];
        })->middleware('recaptcha.score:0.6');

        $this->post('test')->assertOk()->assertExactJson([0.3, true, false]);
    }

    public function test_makes_human_score_one(): void
    {
        Captchavel::fakeHuman();

        $this->app['router']->post('test', function (ReCaptchaResponse $response) {
            return [$response->score, $response->isRobot(), $response->isHuman()];
        })->middleware('recaptcha.score:0.6');

        $this->post('test')->assertOk()->assertExactJson([1.0, false, true]);
    }

    public function test_makes_robot_score_zero(): void
    {
        Captchavel::fakeRobot();

        $this->app['router']->post('test', function (ReCaptchaResponse $response) {
            return [$response->score, $response->isRobot(), $response->isHuman()];
        })->middleware('recaptcha.score:0.6');

        $this->post('test')->assertOk()->assertExactJson([0.0, true, false]);
    }
}
