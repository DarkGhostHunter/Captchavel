<?php

namespace Tests;

use DarkGhostHunter\Captchavel\Captchavel;
use Orchestra\Testbench\TestCase;
use RuntimeException;

class HelperTest extends TestCase
{
    use RegistersPackage;

    public function test_exception_when_no_v3_key_loaded(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The reCAPTCHA site key for [3] doesn\'t exist.');

        captchavel(3);
    }

    public function test_retrieves_test_keys_by_default(): void
    {
        static::assertSame(Captchavel::TEST_V2_KEY, captchavel('checkbox'));
        static::assertSame(Captchavel::TEST_V2_KEY, captchavel('invisible'));
        static::assertSame(Captchavel::TEST_V2_KEY, captchavel('android'));
    }

    public function test_retrieves_secrets(): void
    {
        config(['captchavel.credentials' => [
            'checkbox' => ['key' => 'key-checkbox'],
            'invisible' => ['key' => 'key-invisible'],
            'android' => ['key' => 'key-android'],
            'score' => ['key' => 'key-score'],
        ]]);

        static::assertSame('key-checkbox', captchavel('checkbox'));
        static::assertSame('key-invisible', captchavel('invisible'));
        static::assertSame('key-android', captchavel('android'));
        static::assertSame('key-score', captchavel('score'));
    }
}
