<?php

namespace Tests\Http;

use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use Orchestra\Testbench\TestCase;
use RuntimeException;
use Tests\RegistersPackage;

class ReCaptchaResponseTest extends TestCase
{
    use RegistersPackage;

    public function test_exception_when_checking_non_score_response()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This is not a reCAPTCHA v3 response');

        (new ReCaptchaResponse([
            'success' => true,
        ]))->setAsResolved()->isHuman();
    }

    public function test_exception_when_score_response_has_no_score()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This is reCAPTCHA v3 response has no score');

        (new ReCaptchaResponse([
            'success' => true,
        ]))->setVersion(Captchavel::SCORE)->setAsResolved()->isHuman();
    }

    public function test_is_checkbox_response()
    {
        static::assertTrue(
            (new ReCaptchaResponse())->setVersion(Captchavel::CHECKBOX)->isCheckbox()
        );
    }

    public function test_is_invisible_response()
    {
        static::assertTrue(
            (new ReCaptchaResponse())->setVersion(Captchavel::INVISIBLE)->isInvisible()
        );
    }

    public function test_is_android_response()
    {
        static::assertTrue(
            (new ReCaptchaResponse())->setVersion(Captchavel::ANDROID)->isAndroid()
        );
    }

    public function test_is_score_response()
    {
        static::assertTrue(
            (new ReCaptchaResponse())->setVersion(Captchavel::SCORE)->isScore()
        );
    }
}
