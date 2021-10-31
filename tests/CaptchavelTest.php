<?php
/** @noinspection JsonEncodingApiUsageInspection */

namespace Tests;

use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\Facades\Captchavel as CaptchavelFacade;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use Illuminate\Http\Client\Factory;
use LogicException;
use Mockery;
use Orchestra\Testbench\TestCase;

use function app;

class CaptchavelTest extends TestCase
{
    use RegistersPackage;
    use CreatesFulfilledResponse;

    public function test_returns_response()
    {
        $mock = $this->mock(Factory::class);

        $mock->shouldReceive('asForm')->withNoArgs()->once()->andReturnSelf();
        $mock->shouldReceive('async')->withNoArgs()->once()->andReturnSelf();
        $mock->shouldReceive('withOptions')->with(['version' => 2.0])->once()->andReturnSelf();
        $mock->shouldReceive('post')
            ->with(
                Captchavel::RECAPTCHA_ENDPOINT,
                [
                    'secret'   => Captchavel::TEST_V2_SECRET,
                    'response' => 'token',
                    'remoteip' => '127.0.0.1',
                ]
            )
            ->once()
            ->andReturn(
                $this->fulfilledPromise([
                    'success' => true,
                    'foo'     => 'bar',
                ])
            );

        $instance = app(Captchavel::class)->getChallenge('token', '127.0.0.1', 'checkbox', Captchavel::INPUT);

        $this->app->instance(ReCaptchaResponse::class, $instance);

        static::assertSame($instance, CaptchavelFacade::response());
    }

    public function test_uses_v2_test_credentials_by_default(): void
    {
        $mock = $this->mock(Factory::class);

        $mock->shouldReceive('asForm')->withNoArgs()->times(3)->andReturnSelf();
        $mock->shouldReceive('async')->withNoArgs()->times(3)->andReturnSelf();
        $mock->shouldReceive('withOptions')->with(['version' => 2.0])->times(3)->andReturnSelf();
        $mock->shouldReceive('post')
            ->with(
                Captchavel::RECAPTCHA_ENDPOINT,
                [
                    'secret'   => Captchavel::TEST_V2_SECRET,
                    'response' => 'token',
                    'remoteip' => '127.0.0.1',
                ]
            )
            ->times(3)
            ->andReturn(
                $this->fulfilledPromise([
                    'success' => true,
                    'foo'     => 'bar',
                ])
            );

        /** @var \DarkGhostHunter\Captchavel\Captchavel $instance */
        $instance = app(Captchavel::class);

        $checkbox = $instance->getChallenge('token', '127.0.0.1', 'checkbox', Captchavel::INPUT);

        static::assertTrue($checkbox->success);
        static::assertNull($checkbox->score);
        static::assertSame('bar', $checkbox->foo);

        $invisible = $instance->getChallenge('token', '127.0.0.1', 'invisible', Captchavel::INPUT);

        static::assertTrue($invisible->success);
        static::assertNull($checkbox->score);
        static::assertSame('bar', $invisible->foo);

        $android = $instance->getChallenge('token', '127.0.0.1', 'android', Captchavel::INPUT);

        static::assertTrue($android->success);
        static::assertNull($checkbox->score);
        static::assertSame('bar', $android->foo);
    }

    public function test_uses_v2_custom_credentials(): void
    {
        config(
            [
                'captchavel.credentials' => [
                    'checkbox'  => ['secret' => 'secret-checkbox'],
                    'invisible' => ['secret' => 'secret-invisible'],
                    'android'   => ['secret' => 'secret-android'],
                ],
            ]
        );

        $mock = $this->mock(Factory::class);

        $mock->shouldReceive('asForm')->withNoArgs()->times(3)->andReturnSelf();
        $mock->shouldReceive('async')->withNoArgs()->times(3)->andReturnSelf();
        $mock->shouldReceive('withOptions')->with(['version' => 2.0])->times(3)->andReturnSelf();

        $mock->shouldReceive('post')
            ->with(
                Captchavel::RECAPTCHA_ENDPOINT,
                [
                    'secret'   => 'secret-checkbox',
                    'response' => 'token',
                    'remoteip' => '127.0.0.1',
                ]
            )
            ->once()
            ->andReturn(
                $this->fulfilledPromise([
                    'success' => true,
                    'foo'     => 'bar',
                ])
            );

        $mock->shouldReceive('post')
            ->with(
                Captchavel::RECAPTCHA_ENDPOINT,
                [
                    'secret'   => 'secret-invisible',
                    'response' => 'token',
                    'remoteip' => '127.0.0.1',
                ]
            )
            ->once()
            ->andReturn(
                $this->fulfilledPromise([
                    'success' => true,
                    'foo'     => 'bar',
                ])
            );

        $mock->shouldReceive('post')
            ->with(
                Captchavel::RECAPTCHA_ENDPOINT,
                [
                    'secret'   => 'secret-android',
                    'response' => 'token',
                    'remoteip' => '127.0.0.1',
                ]
            )
            ->once()
            ->andReturn(
                $this->fulfilledPromise([
                    'success' => true,
                    'foo'     => 'bar',
                ])
            );

        $instance = app(Captchavel::class);

        static::assertEquals(
            'bar',
            $instance->getChallenge('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->foo
        );
        static::assertEquals(
            'bar',
            $instance->getChallenge('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->foo
        );
        static::assertEquals(
            'bar',
            $instance->getChallenge('token', '127.0.0.1', 'android', Captchavel::INPUT)->foo
        );
    }

    public function test_exception_if_no_v3_secret_issued(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The reCAPTCHA secret for [score] doesn't exists or is not set.");

        app(Captchavel::class)->getChallenge('token', '127.0.0.1', 'score', Captchavel::INPUT);
    }

    public function test_exception_when_invalid_credentials_issued(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The reCAPTCHA secret for [invalid] doesn't exists or is not set.");

        app(Captchavel::class)->getChallenge('token', '127.0.0.1', 'invalid', Captchavel::INPUT);
    }

    public function test_receives_v3_secret(): void
    {
        config(['captchavel.credentials.score.secret' => 'secret']);

        $mock = $this->mock(Factory::class);

        $mock->shouldReceive('asForm')->withNoArgs()->once()->andReturnSelf();
        $mock->shouldReceive('async')->withNoArgs()->once()->andReturnSelf();
        $mock->shouldReceive('withOptions')->with(['version' => 2.0])->once()->andReturnSelf();
        $mock->shouldReceive('post')
            ->with(Captchavel::RECAPTCHA_ENDPOINT, [
                'secret'   => 'secret',
                'response' => 'token',
                'remoteip' => '127.0.0.1',
            ])
            ->once()
            ->andReturn(
                $this->fulfilledPromise([
                    'success' => true,
                    'score'   => 0.5,
                    'foo'     => 'bar',
                ])
            );

        /** @var \DarkGhostHunter\Captchavel\Captchavel $instance */
        $instance = app(Captchavel::class);

        $score = $instance->getChallenge('token', '127.0.0.1', 'score', Captchavel::INPUT);

        static::assertTrue($score->success);
        static::assertSame(0.5, $score->score);
        static::assertSame('bar', $score->foo);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
