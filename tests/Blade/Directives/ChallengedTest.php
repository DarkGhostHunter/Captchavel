<?php

namespace Tests\Blade\Directives;

use Illuminate\Support\Facades\Blade;
use Orchestra\Testbench\TestCase;
use Tests\RegistersPackage;

use function now;

class ChallengedTest extends TestCase
{
    use RegistersPackage;

    public function test_registers_directive(): void
    {
        $directives = $this->app['blade.compiler']->getCustomDirectives();

        static::assertArrayHasKey('challenged', $directives);
        static::assertArrayHasKey('unlesschallenged', $directives);
        static::assertArrayHasKey('elsechallenged', $directives);
        static::assertArrayHasKey('endchallenged', $directives);
    }

    public function test_true_if_remember_exists_and_not_expired()
    {
        $this->session(['_recaptcha' => now()->addMinute()->getTimestamp()]);

        static::assertTrue(Blade::check('challenged'));
    }

    public function test_true_if_remember_exists_and_forever()
    {
        $this->session(['_recaptcha' => 0]);

        static::assertTrue(Blade::check('challenged'));
    }

    public function test_false_if_remember_expired()
    {
        $this->session(['_recaptcha' => now()->subSecond()->getTimestamp()]);

        static::assertFalse(Blade::check('challenged'));
    }

    public function test_false_if_remember_doesnt_exists()
    {
        static::assertFalse(Blade::check('challenged'));
    }
}
