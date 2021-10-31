<?php

namespace Tests;

use DarkGhostHunter\Captchavel\ReCaptcha;
use LogicException;
use Orchestra\Testbench\TestCase;

use function config;

class ReCaptchaMiddlewareHelperTest extends TestCase
{
    use RegistersPackage;

    public function test_creates_full_recaptcha_v2_checkbox_string(): void
    {
        static::assertEquals('recaptcha:checkbox,g-recaptcha-response', (string) ReCaptcha::checkbox());
        static::assertEquals('recaptcha:checkbox,foo', (string) ReCaptcha::checkbox()->input('foo'));
        static::assertEquals('recaptcha:checkbox,g-recaptcha-response,bar', (string) ReCaptcha::checkbox()->except('bar'));
    }

    public function test_creates_full_recaptcha_v2_invisible_string(): void
    {
        static::assertEquals('recaptcha:invisible,g-recaptcha-response', (string) ReCaptcha::invisible());
        static::assertEquals('recaptcha:invisible,foo', (string) ReCaptcha::invisible()->input('foo'));
        static::assertEquals('recaptcha:invisible,g-recaptcha-response,bar', (string) ReCaptcha::invisible()->except('bar'));
    }

    public function test_creates_full_recaptcha_v2_android_string(): void
    {
        static::assertEquals('recaptcha:android,g-recaptcha-response', (string) ReCaptcha::android());
        static::assertEquals('recaptcha:android,foo', (string) ReCaptcha::android()->input('foo'));
        static::assertEquals('recaptcha:android,g-recaptcha-response,bar', (string) ReCaptcha::android()->except('bar'));
    }

    public function test_exception_if_using_v3_methods_on_v2_checkbox(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You cannot set [threshold] for a [checkbox] middleware.');
        ReCaptcha::checkbox()->threshold(1);
    }

    public function test_exception_if_using_v3_methods_on_v2_invisible(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You cannot set [action] for a [invisible] middleware.');
        ReCaptcha::invisible()->action('route');
    }

    public function test_exception_if_using_v3_methods_on_v2_android(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You cannot set [threshold] for a [android] middleware.');
        ReCaptcha::android()->threshold(1);
    }

    public function test_creates_full_recaptcha_v3_score_string()
    {
        static::assertSame(
            'recaptcha.score:0.5,null,g-recaptcha-response',
            (string) ReCaptcha::score()
        );

        static::assertSame(
            'recaptcha.score:0.3,bar,foo',
            (string) ReCaptcha::score()->input('foo')->threshold(0.3)->action('bar')
        );

        static::assertSame(
            'recaptcha.score:0.3,bar,foo,quz,cougar',
            (string) ReCaptcha::score()->except('quz', 'cougar')->threshold(0.3)->action('bar')->input('foo')
        );
    }

    public function test_uses_threshold_from_config()
    {
        static::assertSame(
            'recaptcha.score:0.5,null,g-recaptcha-response',
            (string) ReCaptcha::score()
        );

        config(['captchavel.threshold' => 0.1]);

        static::assertSame(
            'recaptcha.score:0.1,null,g-recaptcha-response',
            (string) ReCaptcha::score()
        );
    }

    public function test_normalizes_threshold(): void
    {
        static::assertSame(
            'recaptcha.score:1.0,null,g-recaptcha-response',
            (string) ReCaptcha::score(1.7)
        );

        static::assertSame(
            'recaptcha.score:0.0,null,g-recaptcha-response',
            (string) ReCaptcha::score(-9)
        );

        static::assertSame(
            'recaptcha.score:1.0,null,g-recaptcha-response',
            (string) ReCaptcha::score()->threshold(1.7)
        );

        static::assertSame(
            'recaptcha.score:0.0,null,g-recaptcha-response',
            (string) ReCaptcha::score()->threshold(-9)
        );
    }
}
