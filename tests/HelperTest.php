<?php

namespace Tests;

use LogicException;
use Orchestra\Testbench\TestCase;
use DarkGhostHunter\Captchavel\Captchavel;

class HelperTest extends TestCase
{
    use RegistersPackage;

    public function test_exception_when_no_v3_key_loaded()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The reCAPTCHA site key for [3] doesn\'t exist.');

        captchavel(3);
    }

    public function test_retrieves_test_keys_by_default()
    {
        $this->assertSame(Captchavel::TEST_V2_KEY, captchavel('checkbox'));
        $this->assertSame(Captchavel::TEST_V2_KEY, captchavel('invisible'));
        $this->assertSame(Captchavel::TEST_V2_KEY, captchavel('android'));
    }

    public function test_retrieves_secrets()
    {
        config(['captchavel.credentials.v2' => [
            'checkbox' => ['key' => 'key-checkbox'],
            'invisible' => ['key' => 'key-invisible'],
            'android' => ['key' => 'key-android'],
        ]]);

        config(['captchavel.credentials.v3' => [
            'key' => 'key-score'
        ]]);

        $this->assertSame('key-checkbox', captchavel('checkbox'));
        $this->assertSame('key-invisible', captchavel('invisible'));
        $this->assertSame('key-android', captchavel('android'));
        $this->assertSame('key-score', captchavel('score'));
        $this->assertSame('key-score', captchavel('v3'));
        $this->assertSame('key-score', captchavel('3'));
        $this->assertSame('key-score', captchavel(3));
    }
}
