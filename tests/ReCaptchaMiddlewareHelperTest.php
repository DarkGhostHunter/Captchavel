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
        static::assertEquals('recaptcha:checkbox', (string) ReCaptcha::checkbox());
        static::assertEquals('recaptcha:checkbox,null,foo', (string) ReCaptcha::checkbox()->input('foo'));
        static::assertEquals('recaptcha:checkbox,null,null,bar', (string) ReCaptcha::checkbox()->except('bar'));
        static::assertEquals('recaptcha:checkbox,10,null,bar', (string) ReCaptcha::checkbox()->except('bar')->remember());
        static::assertEquals('recaptcha:checkbox,20,null,bar', (string) ReCaptcha::checkbox()->except('bar')->remember(20));
        static::assertEquals('recaptcha:checkbox,0,null,bar', (string) ReCaptcha::checkbox()->except('bar')->rememberForever());
        static::assertEquals('recaptcha:checkbox,false,null,bar', (string) ReCaptcha::checkbox()->except('bar')->dontRemember());
        static::assertEquals('recaptcha:checkbox,false,foo,bar', (string) ReCaptcha::checkbox()->input('foo')->except('bar')->dontRemember());
    }

    public function test_creates_full_recaptcha_v2_invisible_string(): void
    {
        static::assertEquals('recaptcha:invisible', (string) ReCaptcha::invisible());
        static::assertEquals('recaptcha:invisible,null,foo', (string) ReCaptcha::invisible()->input('foo'));
        static::assertEquals('recaptcha:invisible,null,null,bar', (string) ReCaptcha::invisible()->except('bar'));
        static::assertEquals('recaptcha:invisible,10,null,bar', (string) ReCaptcha::invisible()->except('bar')->remember());
        static::assertEquals('recaptcha:invisible,20,null,bar', (string) ReCaptcha::invisible()->except('bar')->remember(20));
        static::assertEquals('recaptcha:invisible,0,null,bar', (string) ReCaptcha::invisible()->except('bar')->rememberForever());
        static::assertEquals('recaptcha:invisible,false,null,bar', (string) ReCaptcha::invisible()->except('bar')->dontRemember());
        static::assertEquals('recaptcha:invisible,false,foo,bar', (string) ReCaptcha::invisible()->input('foo')->except('bar')->dontRemember());
    }

    public function test_creates_full_recaptcha_v2_android_string(): void
    {
        static::assertEquals('recaptcha:android', (string) ReCaptcha::android());
        static::assertEquals('recaptcha:android,null,foo', (string) ReCaptcha::android()->input('foo'));
        static::assertEquals('recaptcha:android,null,null,bar', (string) ReCaptcha::android()->except('bar'));
        static::assertEquals('recaptcha:android,10,null,bar', (string) ReCaptcha::android()->except('bar')->remember());
        static::assertEquals('recaptcha:android,20,null,bar', (string) ReCaptcha::android()->except('bar')->remember(20));
        static::assertEquals('recaptcha:android,0,null,bar', (string) ReCaptcha::android()->except('bar')->rememberForever());
        static::assertEquals('recaptcha:android,false,null,bar', (string) ReCaptcha::android()->except('bar')->dontRemember());
        static::assertEquals('recaptcha:android,false,foo,bar', (string) ReCaptcha::android()->input('foo')->except('bar')->dontRemember());
    }

    public function test_exception_if_using_remember_on_v3(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You cannot set [remember] for a [score] middleware.');
        ReCaptcha::score()->remember();
    }

    public function test_exception_if_using_dont_remember_on_v3(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You cannot set [dontRemember] for a [score] middleware.');
        ReCaptcha::score()->dontRemember();
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
            'recaptcha.score:0.5',
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
            'recaptcha.score:0.5',
            (string) ReCaptcha::score()
        );

        config(['captchavel.threshold' => 0.1]);

        static::assertSame(
            'recaptcha.score:0.1',
            (string) ReCaptcha::score()
        );
    }

    public function test_normalizes_threshold(): void
    {
        static::assertSame(
            'recaptcha.score:1.0',
            (string) ReCaptcha::score(1.7)
        );

        static::assertSame(
            'recaptcha.score:0.0',
            (string) ReCaptcha::score(-9)
        );

        static::assertSame(
            'recaptcha.score:1.0',
            (string) ReCaptcha::score()->threshold(1.7)
        );

        static::assertSame(
            'recaptcha.score:0.0',
            (string) ReCaptcha::score()->threshold(-9)
        );
    }

    public function test_cast_to_string(): void
    {
        static::assertEquals('recaptcha.score:0.7', ReCaptcha::score(0.7)->toString());
        static::assertEquals('recaptcha.score:0.7', ReCaptcha::score(0.7)->__toString());
    }

    public function tests_uses_all_guards_as_exception(): void
    {
        static::assertEquals('recaptcha:checkbox,null,null,null', (string) ReCaptcha::checkbox()->onlyGuests());
        static::assertEquals('recaptcha:invisible,null,null,null', (string) ReCaptcha::invisible()->onlyGuests());
        static::assertEquals('recaptcha:android,null,null,null', (string) ReCaptcha::android()->onlyGuests());
        static::assertEquals('recaptcha.score:0.5,null,null,null,null', (string) ReCaptcha::score()->onlyGuests());
    }
}
