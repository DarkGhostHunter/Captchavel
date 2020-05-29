<?php

namespace Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use DarkGhostHunter\Captchavel\Facades\Captchavel;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;

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

    public function test_using_fake_on_unit_test()
    {
        $this->assertTrue(config('captchavel.fake'));
    }

    public function test_makes_fake_score()
    {
        Captchavel::fake()->shouldScore(0.3);

        Route::post('test', function (ReCaptchaResponse $response) {
            return [$response->score, $response->isRobot(), $response->isHuman()];
        })->middleware('recaptcha.v3:0.6');

        $this->post('test')->assertOk()->assertExactJson([0.3, true, false]);
    }

    public function test_makes_human_score_one()
    {
        Captchavel::fake()->asHuman();

        Route::post('test', function (ReCaptchaResponse $response) {
            return [$response->score, $response->isRobot(), $response->isHuman()];
        })->middleware('recaptcha.v3:0.6');

        $this->post('test')->assertOk()->assertExactJson([1.0, false, true]);
    }

    public function test_makes_robot_score_zero()
    {
        Captchavel::fake()->asRobot();

        Route::post('test', function (ReCaptchaResponse $response) {
            return [$response->score, $response->isRobot(), $response->isHuman()];
        })->middleware('recaptcha.v3:0.6');

        $this->post('test')->assertOk()->assertExactJson([0.0, true, false]);
    }
}
