<?php

namespace Tests\Http;

use LogicException;
use Tests\RegistersPackage;
use Orchestra\Testbench\TestCase;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;

class ReCaptchaResponseTest extends TestCase
{
    use RegistersPackage;

    public function test_exception_when_checking_non_score_response()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This is not a reCAPTCHA v3 response, or the score is absent.');

        (new ReCaptchaResponse([
            'success' => true,
        ]))->isHuman();
    }
}
