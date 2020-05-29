<?php

namespace Tests;

use LogicException;
use Orchestra\Testbench\TestCase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use DarkGhostHunter\Captchavel\Captchavel;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;

class CaptchavelTest extends TestCase
{
    use RegistersPackage;

    public function test_uses_v2_test_credentials_by_default()
    {
        $mock = $this->mock(Factory::class);

        $mock->shouldReceive('asForm')->withNoArgs()->times(3)->andReturnSelf();
        $mock->shouldReceive('withOptions')->with(['version' => 2.0])->times(3)->andReturnSelf();
        $mock->shouldReceive('post')
            ->with(Captchavel::RECAPTCHA_ENDPOINT, [
                'secret'   => Captchavel::TEST_V2_SECRET,
                'response' => 'token',
                'remoteip' => '127.0.0.1',
            ])
            ->times(3)
            ->andReturn(new Response(new GuzzleResponse(200, ['Content-type' => 'application/json'], json_encode([
                'success' => true,
                'score'   => 0.5,
                'foo'     => 'bar',
            ]))));

        $instance = app(Captchavel::class);

        $this->assertNull($instance->getResponse());

        $checkbox = $instance->useCredentials(2, 'checkbox')->retrieve('token', '127.0.0.1');
        $this->assertSame($checkbox, $instance->getResponse());
        $this->assertInstanceOf(ReCaptchaResponse::class, $checkbox);
        $this->assertTrue($checkbox->success);
        $this->assertSame(0.5, $checkbox->score);
        $this->assertSame('bar', $checkbox->foo);

        $invisible = $instance->useCredentials(2, 'invisible')->retrieve('token', '127.0.0.1');
        $this->assertSame($invisible, $instance->getResponse());
        $this->assertInstanceOf(ReCaptchaResponse::class, $invisible);
        $this->assertTrue($invisible->success);
        $this->assertSame(0.5, $invisible->score);
        $this->assertSame('bar', $invisible->foo);

        $android = $instance->useCredentials(2, 'android')->retrieve('token', '127.0.0.1');
        $this->assertSame($android, $instance->getResponse());
        $this->assertInstanceOf(ReCaptchaResponse::class, $android);
        $this->assertTrue($android->success);
        $this->assertSame(0.5, $android->score);
        $this->assertSame('bar', $android->foo);
    }

    public function test_uses_v2_custom_credentials()
    {
        config(['captchavel.credentials.v2' => [
            'checkbox' => ['secret' => 'secret-checkbox'],
            'invisible' => ['secret' => 'secret-invisible'],
            'android' => ['secret' => 'secret-android'],
        ]]);

        $mock = $this->mock(Factory::class);

        $mock->shouldReceive('asForm')->withNoArgs()->times(3)->andReturnSelf();
        $mock->shouldReceive('withOptions')->with(['version' => 2.0])->times(3)->andReturnSelf();

        $mock->shouldReceive('post')
            ->with(Captchavel::RECAPTCHA_ENDPOINT, [
                'secret'   => 'secret-checkbox',
                'response' => 'token',
                'remoteip' => '127.0.0.1',
            ])
            ->once()
            ->andReturn(new Response(new GuzzleResponse(200, ['Content-type' => 'application/json'], json_encode([
                'success' => true,
                'score'   => 0.5,
                'foo'     => 'bar',
            ]))));

        $mock->shouldReceive('post')
            ->with(Captchavel::RECAPTCHA_ENDPOINT, [
                'secret'   => 'secret-invisible',
                'response' => 'token',
                'remoteip' => '127.0.0.1',
            ])
            ->once()
            ->andReturn(new Response(new GuzzleResponse(200, ['Content-type' => 'application/json'], json_encode([
                'success' => true,
                'score'   => 0.5,
                'foo'     => 'bar',
            ]))));

        $mock->shouldReceive('post')
            ->with(Captchavel::RECAPTCHA_ENDPOINT, [
                'secret'   => 'secret-android',
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

        $this->assertNull($instance->getResponse());

        $checkbox = $instance->useCredentials(2, 'checkbox')->retrieve('token', '127.0.0.1');
        $this->assertSame($checkbox, $instance->getResponse());
        $this->assertInstanceOf(ReCaptchaResponse::class, $checkbox);
        $this->assertTrue($checkbox->success);
        $this->assertSame(0.5, $checkbox->score);
        $this->assertSame('bar', $checkbox->foo);

        $invisible = $instance->useCredentials(2, 'invisible')->retrieve('token', '127.0.0.1');
        $this->assertSame($invisible, $instance->getResponse());
        $this->assertInstanceOf(ReCaptchaResponse::class, $invisible);
        $this->assertTrue($invisible->success);
        $this->assertSame(0.5, $invisible->score);
        $this->assertSame('bar', $invisible->foo);

        $android = $instance->useCredentials(2, 'android')->retrieve('token', '127.0.0.1');
        $this->assertSame($android, $instance->getResponse());
        $this->assertInstanceOf(ReCaptchaResponse::class, $android);
        $this->assertTrue($android->success);
        $this->assertSame(0.5, $android->score);
        $this->assertSame('bar', $android->foo);
    }

    public function test_exception_if_no_v3_secret_issued()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The reCAPTCHA secret for [v3] doesn\'t exists.');

        $instance = app(Captchavel::class);

        $instance->useCredentials(3)->retrieve('token', '127.0.0.1');
    }

    public function test_exception_when_invalid_credentials_issued()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The reCAPTCHA v2 variant must be [checkbox], [invisible] or [android].');

        $instance = app(Captchavel::class);

        $instance->useCredentials(2, 'invalid')->retrieve('token', '127.0.0.1');
    }

    public function test_receives_v3_secret()
    {
        config(['captchavel.credentials.v3.secret' => 'secret']);

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

        $this->assertNull($instance->getResponse());

        $score = $instance->useCredentials(3)->retrieve('token', '127.0.0.1');

        $this->assertSame($score, $instance->getResponse());
        $this->assertInstanceOf(ReCaptchaResponse::class, $score);
        $this->assertTrue($score->success);
        $this->assertSame(0.5, $score->score);
        $this->assertSame('bar', $score->foo);
    }
}
