<?php

namespace Tests;

use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;

class RequestMacroTest extends TestCase
{
    use RegistersPackage;
    use CreatesFulfilledResponse;

    public function test_checks_if_human(): void
    {
        $this->instance(ReCaptchaResponse::class,
            $this->fulfilledResponse(['success' => true, 'score' => 0.5])->setThreshold(0.5)
        );

        static::assertTrue(Request::isHuman());
        static::assertFalse(Request::isRobot());
    }

    public function test_checks_if_robot(): void
    {
        $this->instance(ReCaptchaResponse::class,
            $this->fulfilledResponse(['success' => true, 'score' => 0.2])->setThreshold(0.5)
        );

        static::assertFalse(Request::isHuman());
        static::assertTrue(Request::isRobot());
    }
}
