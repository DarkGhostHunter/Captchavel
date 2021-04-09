<?php
/** @noinspection JsonEncodingApiUsageInspection */

namespace Tests;

use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use LogicException;
use Mockery;
use Orchestra\Testbench\TestCase;
use RuntimeException;

class CaptchavelTest extends TestCase
{
    use RegistersPackage;

    public function test_uses_v2_test_credentials_by_default()
    {
        $mock = $this->mock(Factory::class);

        $mock->shouldReceive('asForm')->withNoArgs()->times(3)->andReturnSelf();
        $mock->shouldReceive('withOptions')->with(['version' => 2.0])->times(3)->andReturnSelf();
        $mock->shouldReceive('post')
            ->with(
                Captchavel::RECAPTCHA_ENDPOINT,
                [
                    'secret' => Captchavel::TEST_V2_SECRET,
                    'response' => 'token',
                    'remoteip' => '127.0.0.1',
                ]
            )
            ->times(3)
            ->andReturn(
                new Response(
                    new GuzzleResponse(
                        200, ['Content-type' => 'application/json'], json_encode(
                        $array = [
                            'success' => true,
                            'score' => 0.5,
                            'foo' => 'bar',
                        ]
                    )
                    )
                )
            );

        /** @var \DarkGhostHunter\Captchavel\Captchavel $instance */
        $instance = app(Captchavel::class);

        $checkbox = $instance->getChallenge('token', '127.0.0.1', 'checkbox');

        static::assertTrue($checkbox->isResolved());
        static::assertSame($checkbox->version, 'checkbox');
        static::assertTrue($checkbox->success);
        static::assertSame(0.5, $checkbox->score);
        static::assertSame('bar', $checkbox->foo);

        $invisible = $instance->getChallenge('token', '127.0.0.1', 'invisible');

        static::assertTrue($invisible->isResolved());
        static::assertSame($invisible->version, 'invisible');
        static::assertTrue($invisible->success);
        static::assertSame(0.5, $invisible->score);
        static::assertSame('bar', $invisible->foo);

        $android = $instance->getChallenge('token', '127.0.0.1', 'android');

        static::assertTrue($android->isResolved());
        static::assertSame($android->version, 'android');
        static::assertTrue($android->success);
        static::assertSame(0.5, $android->score);
        static::assertSame('bar', $android->foo);
    }

    public function test_uses_v2_custom_credentials()
    {
        config(
            [
                'captchavel.credentials' => [
                    'checkbox' => ['secret' => 'secret-checkbox'],
                    'invisible' => ['secret' => 'secret-invisible'],
                    'android' => ['secret' => 'secret-android'],
                ],
            ]
        );

        $mock = $this->mock(Factory::class);

        $mock->shouldReceive('asForm')->withNoArgs()->times(3)->andReturnSelf();
        $mock->shouldReceive('withOptions')->with(['version' => 2.0])->times(3)->andReturnSelf();

        $mock->shouldReceive('post')
            ->with(
                Captchavel::RECAPTCHA_ENDPOINT,
                [
                    'secret' => 'secret-checkbox',
                    'response' => 'token',
                    'remoteip' => '127.0.0.1',
                ]
            )
            ->once()
            ->andReturn(
                new Response(
                    new GuzzleResponse(
                        200, ['Content-type' => 'application/json'], json_encode(
                        [
                            'success' => true,
                            'score' => 0.5,
                            'foo' => 'bar',
                        ]
                    )
                    )
                )
            );

        $mock->shouldReceive('post')
            ->with(
                Captchavel::RECAPTCHA_ENDPOINT,
                [
                    'secret' => 'secret-invisible',
                    'response' => 'token',
                    'remoteip' => '127.0.0.1',
                ]
            )
            ->once()
            ->andReturn(
                new Response(
                    new GuzzleResponse(
                        200, ['Content-type' => 'application/json'], json_encode(
                        [
                            'success' => true,
                            'score' => 0.5,
                            'foo' => 'bar',
                        ]
                    )
                    )
                )
            );

        $mock->shouldReceive('post')
            ->with(
                Captchavel::RECAPTCHA_ENDPOINT,
                [
                    'secret' => 'secret-android',
                    'response' => 'token',
                    'remoteip' => '127.0.0.1',
                ]
            )
            ->once()
            ->andReturn(
                new Response(
                    new GuzzleResponse(
                        200, ['Content-type' => 'application/json'], json_encode(
                        [
                            'success' => true,
                            'score' => 0.5,
                            'foo' => 'bar',
                        ]
                    )
                    )
                )
            );

        $instance = app(Captchavel::class);

        static::assertEquals(
            Captchavel::CHECKBOX,
            $instance->getChallenge('token', '127.0.0.1', 'checkbox')->version
        );
        static::assertEquals(
            Captchavel::INVISIBLE,
            $instance->getChallenge('token', '127.0.0.1', 'invisible')->version
        );
        static::assertEquals(
            Captchavel::ANDROID,
            $instance->getChallenge('token', '127.0.0.1', 'android')->version
        );
    }

    public function test_default_response_singleton_never_resolved()
    {
        static::assertFalse(app(ReCaptchaResponse::class)->isResolved());
        static::assertNull(app(ReCaptchaResponse::class)->version);
    }

    public function test_exception_if_no_v3_secret_issued()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The reCAPTCHA secret for [score] doesn\'t exists');

        app(Captchavel::class)->getChallenge('token', '127.0.0.1', 'score');
    }

    public function test_exception_when_invalid_credentials_issued()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The reCAPTCHA mode must be: checkbox, invisible, android, score');

        app(Captchavel::class)->getChallenge('token', '127.0.0.1', 'invalid');
    }

    public function test_receives_v3_secret()
    {
        config(['captchavel.credentials.score.secret' => 'secret']);

        $mock = $this->mock(Factory::class);

        $mock->shouldReceive('asForm')->withNoArgs()->once()->andReturnSelf();
        $mock->shouldReceive('withOptions')->with(['version' => 2.0])->once()->andReturnSelf();
        $mock->shouldReceive('post')
            ->with(Captchavel::RECAPTCHA_ENDPOINT, [
                'secret'   => 'secret',
                'response' => 'token',
                'remoteip' => '127.0.0.1',
            ])
            ->once()
            ->andReturn(new Response(new GuzzleResponse(200, ['Content-type' => 'application/json'], json_encode([
                'success' => true,
                'score'   => 0.5,
                'foo'     => 'bar',
            ]))));

        $instance = app(Captchavel::class);

        $score = $instance->getChallenge('token', '127.0.0.1', 'score');

        static::assertEquals('score', $score->version);
        static::assertTrue($score->isResolved());
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
