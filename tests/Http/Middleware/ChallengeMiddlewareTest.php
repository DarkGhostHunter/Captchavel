<?php

namespace Tests\Http\Middleware;

use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use LogicException;
use Orchestra\Testbench\TestCase;
use Tests\CreatesFulfilledResponse;
use Tests\RegistersPackage;
use function app;
use function config;
use function now;
use function trans;

class ChallengeMiddlewareTest extends TestCase
{
    use RegistersPackage;
    use UsesRoutesWithMiddleware;
    use CreatesFulfilledResponse;

    protected function setUp(): void
    {
        $this->afterApplicationCreated(
            function () {
                $this->createsRoutes();
                config(['captchavel.fake' => false]);
            }
        );

        parent::setUp();
    }

    public function test_exception_if_declaring_v2_middleware_as_score(): void
    {
        $this->app['router']->post('v2/score', function () {
            // ...
        })->middleware('recaptcha:score');

        $exception = $this->post('v2/score')->assertStatus(500)->exception;

        static::assertInstanceOf(LogicException::class, $exception);
        static::assertSame('Use the [recaptcha.score] middleware to capture score-driven reCAPTCHA.',
            $exception->getMessage());
    }

    public function test_exception_if_no_challenge_specified(): void
    {
        config()->set('app.debug', false);

        $this->app['router']->post(
            'test',
            function () {
                return 'ok';
            }
        )->middleware('recaptcha');

        $this->post('test')->assertStatus(500);

        $this->postJson('test')->assertJson(['message' => 'Server Error']);
    }

    public function test_bypass_if_not_enabled(): void
    {
        config(['captchavel.enable' => false]);

        $this->post('v2/checkbox')->assertOk();
        $this->post('v2/invisible')->assertOk();
        $this->post('v2/android')->assertOk();
    }

    public function test_success_when_enabled_and_fake(): void
    {
        config(['captchavel.enable' => true]);
        config(['captchavel.fake' => true]);

        $this->post('v2/checkbox')->assertOk();
        $this->post('v2/checkbox/input_bar')->assertOk();
        $this->post('v2/invisible')->assertOk();
        $this->post('v2/invisible/input_bar')->assertOk();
        $this->post('v2/android')->assertOk();
        $this->post('v2/android/input_bar')->assertOk();
    }

    public function test_success_when_disabled(): void
    {
        config(['captchavel.enable' => false]);

        $this->post('v2/checkbox')->assertOk();
        $this->post('v2/checkbox/input_bar')->assertOk();
        $this->post('v2/invisible')->assertOk();
        $this->post('v2/invisible/input_bar')->assertOk();
        $this->post('v2/android')->assertOk();
        $this->post('v2/android/input_bar')->assertOk();
    }

    public function test_validates_if_real(): void
    {
        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success' => true,
            'foo'     => 'bar',
        ]);

        $mock->expects('getChallenge')->once()
            ->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')->once()
            ->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')->once()
            ->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_uses_custom_input(): void
    {
        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success' => true,
            'score'   => 0.5,
            'foo'     => 'bar',
        ], 'bar');

        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', 'bar')->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', 'bar')->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', 'bar')->andReturn($response);

        $this->post('v2/checkbox/input_bar', ['bar' => 'token'])->assertOk();
        $this->post('v2/invisible/input_bar', ['bar' => 'token'])->assertOk();
        $this->post('v2/android/input_bar', ['bar' => 'token'])->assertOk();
    }

    public function test_exception_when_token_absent(): void
    {
        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(12)->andReturnFalse();
        $mock->expects('shouldFake')->times(12)->andReturnFalse();

        $mock->shouldNotReceive('getChallenge');

        $this->post('v2/checkbox')
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.missing'))
            ->assertRedirect('/');
        $this->postJson('v2/checkbox')->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/invisible')
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.missing'))
            ->assertRedirect('/');
        $this->postJson('v2/invisible')->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/android')
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.missing'))
            ->assertRedirect('/');
        $this->postJson('v2/android')->assertJsonValidationErrors(Captchavel::INPUT);

        $this->post('v2/checkbox/input_bar')
            ->assertSessionHasErrors('bar', trans('captchavel::validation.missing'))
            ->assertRedirect('/');
        $this->postJson('v2/checkbox/input_bar')->assertJsonValidationErrors('bar');
        $this->post('v2/invisible/input_bar')
            ->assertSessionHasErrors('bar', trans('captchavel::validation.missing'))
            ->assertRedirect('/');
        $this->postJson('v2/invisible/input_bar')->assertJsonValidationErrors('bar');
        $this->post('v2/android/input_bar')
            ->assertSessionHasErrors('bar', trans('captchavel::validation.missing'))
            ->assertRedirect('/');
        $this->postJson('v2/android/input_bar')->assertJsonValidationErrors('bar');
    }

    public function test_exception_when_token_null(): void
    {
        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(12)->andReturnFalse();
        $mock->expects('shouldFake')->times(12)->andReturnFalse();

        $mock->allows('getChallenge')->never();

        $this->post('v2/checkbox', [Captchavel::INPUT => null])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.missing'))
            ->assertRedirect('/');
        $this->postJson('v2/checkbox')->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/invisible', [Captchavel::INPUT => null])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.missing'))
            ->assertRedirect('/');
        $this->postJson('v2/invisible')->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/android', [Captchavel::INPUT => null])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.missing'))
            ->assertRedirect('/');
        $this->postJson('v2/android', [Captchavel::INPUT => null])->assertJsonValidationErrors(Captchavel::INPUT);

        $this->post('v2/checkbox/input_bar', ['bar' => null])
            ->assertSessionHasErrors('bar', trans('captchavel::validation.missing'))
            ->assertRedirect('/');
        $this->postJson('v2/checkbox/input_bar')->assertJsonValidationErrors('bar');
        $this->post('v2/invisible/input_bar', ['bar' => null])
            ->assertSessionHasErrors('bar', trans('captchavel::validation.missing'))
            ->assertRedirect('/');
        $this->postJson('v2/invisible/input_bar')->assertJsonValidationErrors('bar');
        $this->post('v2/android/input_bar', ['bar' => null])
            ->assertSessionHasErrors('bar', trans('captchavel::validation.missing'))
            ->assertRedirect('/');
        $this->postJson('v2/android/input_bar', ['bar' => null])->assertJsonValidationErrors('bar');
    }

    public function test_exception_when_response_failed(): void
    {
        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(6)->andReturnFalse();
        $mock->expects('shouldFake')->times(6)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success' => false,
            'foo'     => 'bar',
        ]);

        $mock->expects('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.error'))
            ->assertRedirect('/');
        $this->postJson('v2/checkbox', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.error'))
            ->assertRedirect('/');
        $this->postJson('v2/invisible', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/android', [Captchavel::INPUT => 'token'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.error'))
            ->assertRedirect('/');
        $this->postJson('v2/android', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
    }

    public function test_exception_when_response_invalid(): void
    {
        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(6)->andReturnFalse();
        $mock->expects('shouldFake')->times(6)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'foo' => 'bar',
        ]);

        $mock->expects('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.error'))
            ->assertRedirect('/');
        $this->postJson('v2/checkbox', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.error'))
            ->assertRedirect('/');
        $this->postJson('v2/invisible', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/android', [Captchavel::INPUT => 'token'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.error'))
            ->assertRedirect('/');
        $this->postJson('v2/android', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
    }

    public function test_no_error_if_not_hostname_issued(): void
    {
        config(['captchavel.hostname' => null]);

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success' => true,
            'foo'     => 'bar',
        ]);

        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_no_error_if_not_hostname_same(): void
    {
        config(['captchavel.hostname' => 'foo']);

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'  => true,
            'foo'      => 'bar',
            'hostname' => 'foo',
        ]);

        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_exception_if_hostname_not_equal(): void
    {
        config(['captchavel.hostname' => 'bar']);

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(6)->andReturnFalse();
        $mock->expects('shouldFake')->times(6)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'  => true,
            'foo'      => 'bar',
            'hostname' => 'foo',
        ]);

        $mock->expects('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.match'))
            ->assertRedirect('/');
        $this->postJson('v2/checkbox', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.match'))
            ->assertRedirect('/');
        $this->postJson('v2/invisible', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/android', [Captchavel::INPUT => 'token'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.match'))
            ->assertRedirect('/');
        $this->postJson('v2/android', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
    }

    public function test_no_error_if_no_apk_issued(): void
    {
        config(['captchavel.apk_package_name' => null]);

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'          => true,
            'foo'              => 'bar',
            'apk_package_name' => 'foo',
        ]);

        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_no_error_if_no_apk_same(): void
    {
        config(['captchavel.apk_package_name' => 'foo']);

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'          => true,
            'foo'              => 'bar',
            'apk_package_name' => 'foo',
        ]);

        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_exception_if_apk_not_equal(): void
    {
        config(['captchavel.apk_package_name' => 'bar']);

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(6)->andReturnFalse();
        $mock->expects('shouldFake')->times(6)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'          => true,
            'foo'              => 'bar',
            'apk_package_name' => 'foo',
        ]);

        $mock->expects('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.match'))
            ->assertRedirect('/');
        $this->postJson('v2/checkbox', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.match'))
            ->assertRedirect('/');
        $this->postJson('v2/invisible', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/android', [Captchavel::INPUT => 'token'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.match'))
            ->assertRedirect('/');
        $this->postJson('v2/android', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
    }

    public function test_challenge_is_not_remembered_by_default(): void
    {
        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'          => true,
            'foo'              => 'bar',
            'apk_package_name' => 'foo',
        ]);

        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionMissing('_recaptcha');
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionMissing('_recaptcha');
        $this->post('v2/android', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionMissing('_recaptcha');
    }

    public function test_challenge_is_remembered_in_session(): void
    {
        config(['captchavel.remember.enabled' => true]);

        $this->travelTo($now = now());

        $timestamp = $now->clone()->addMinutes(10)->getTimestamp();

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'          => true,
            'foo'              => 'bar',
        ]);

        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('_recaptcha', $timestamp);

        $this->flushSession();

        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('_recaptcha', $timestamp);

        $this->flushSession();

        $this->post('v2/android', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('_recaptcha', $timestamp);
    }

    public function test_challenge_is_remembered_in_session_when_config_overridden(): void
    {
        $this->travelTo($now = now());

        $timestamp = $now->clone()->addMinutes(10)->getTimestamp();

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'          => true,
            'foo'              => 'bar',
        ]);

        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox/remember', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('_recaptcha', $timestamp);

        $this->flushSession();

        $this->post('v2/invisible/remember', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('_recaptcha', $timestamp);

        $this->flushSession();

        $this->post('v2/android/remember', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('_recaptcha', $timestamp);
    }

    public function test_challenge_is_remembered_in_session_using_custom_key(): void
    {
        config([
            'captchavel.remember.enabled' => true,
            'captchavel.remember.key' => 'foo',
        ]);

        $this->travelTo($now = now());

        $timestamp = $now->clone()->addMinutes(10)->getTimestamp();

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'          => true,
            'foo'              => 'bar',
        ]);

        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('foo', $timestamp);

        $this->flushSession();

        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('foo', $timestamp);

        $this->flushSession();

        $this->post('v2/android', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('foo', $timestamp);
    }

    public function test_challenge_is_remembered_in_session_with_custom_key_when_config_overridden(): void
    {
        config([
            'captchavel.remember.key' => 'foo',
        ]);

        $this->travelTo($now = now());

        $timestamp = $now->clone()->addMinutes(10)->getTimestamp();

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'          => true,
            'foo'              => 'bar',
        ]);

        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox/remember', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('foo', $timestamp);

        $this->flushSession();

        $this->post('v2/invisible/remember', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('foo', $timestamp);

        $this->flushSession();

        $this->post('v2/android/remember', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('foo', $timestamp);
    }

    public function test_challenge_is_remembered_forever(): void
    {
        config([
            'captchavel.remember.enabled' => true,
            'captchavel.remember.minutes' => 0
        ]);

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'          => true,
            'foo'              => 'bar',
        ]);

        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->app['router']->post('v2/checkbox/forever', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:checkbox');

        $this->app['router']->post('v2/invisible/forever', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:invisible');


        $this->app['router']->post('v2/android/forever', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:android');

        $this->post('v2/checkbox/forever', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('_recaptcha', 0);

        $this->flushSession();

        $this->post('v2/invisible/forever', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('_recaptcha', 0);

        $this->flushSession();

        $this->post('v2/android/forever', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('_recaptcha', 0);
    }

    public function test_challenge_is_remembered_forever_when_config_overridden(): void
    {
        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'          => true,
            'foo'              => 'bar',
        ]);

        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->app['router']->post('v2/checkbox/forever', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:checkbox,0');

        $this->app['router']->post('v2/invisible/forever', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:invisible,0');


        $this->app['router']->post('v2/android/forever', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:android,0');

        $this->post('v2/checkbox/forever', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('_recaptcha', 0);

        $this->flushSession();

        $this->post('v2/invisible/forever', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('_recaptcha', 0);

        $this->flushSession();

        $this->post('v2/android/forever', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('_recaptcha', 0);
    }

    public function test_challenge_is_remembered_with_different_offset(): void
    {
        config([
            'captchavel.remember.enabled' => true,
            'captchavel.remember.minutes' => 30
        ]);

        $this->travelTo($now = now());

        $timestamp = $now->clone()->addMinutes(30)->getTimestamp();

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'          => true,
            'foo'              => 'bar',
        ]);

        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('_recaptcha', $timestamp);

        $this->flushSession();

        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('_recaptcha', $timestamp);

        $this->flushSession();

        $this->post('v2/android', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionHas('_recaptcha', $timestamp);
    }

    public function test_challenge_is_not_remembered_when_config_overridden(): void
    {
        config([
            'captchavel.remember.enabled' => true,
        ]);

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'          => true,
            'foo'              => 'bar',
        ]);

        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->expects('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->app['router']->post('v2/checkbox/dont-remember', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:checkbox,false');

        $this->app['router']->post('v2/invisible/dont-remember', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:invisible,false');

        $this->app['router']->post('v2/android/dont-remember', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:android,false');

        $this->post('v2/checkbox/dont-remember', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionMissing('_recaptcha');

        $this->post('v2/invisible/dont-remember', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionMissing('_recaptcha');

        $this->post('v2/android/dont-remember', [Captchavel::INPUT => 'token'])
            ->assertOk()->assertSessionMissing('_recaptcha');
    }

    public function test_bypasses_check_if_session_has_remember_not_expired(): void
    {
        config([
            'captchavel.remember.enabled' => true,
        ]);

        $mock = $this->mock(Captchavel::class);

        $this->session([
            '_recaptcha' => now()->addMinute()->getTimestamp()
        ]);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();
        $mock->shouldNotReceive('getChallenge');

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk()->assertSessionHas('_recaptcha');
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk()->assertSessionHas('_recaptcha');
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk()->assertSessionHas('_recaptcha');
    }

    public function test_bypasses_check_if_session_has_remember_forever(): void
    {
        config([
            'captchavel.remember.enabled' => true,
        ]);

        $mock = $this->mock(Captchavel::class);

        $this->session([
            '_recaptcha' => 0
        ]);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();
        $mock->shouldNotReceive('getChallenge');

        $this->post('v2/checkbox')->assertOk()->assertSessionHas('_recaptcha');
        $this->post('v2/invisible')->assertOk()->assertSessionHas('_recaptcha');
        $this->post('v2/android')->assertOk()->assertSessionHas('_recaptcha');
    }

    public function test_doesnt_bypasses_check_if_session_has_not_remember(): void
    {
        config([
            'captchavel.remember.enabled' => true,
        ]);

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();
        $mock->shouldNotReceive('getChallenge');

        $this->post('v2/checkbox')->assertSessionHasErrors();
        $this->post('v2/invisible')->assertSessionHasErrors();
        $this->post('v2/android')->assertSessionHasErrors();
    }

    public function test_doesnt_bypasses_check_if_remember_has_expired_and_deletes_key(): void
    {
        config([
            'captchavel.remember.enabled' => true,
        ]);

        $this->session(['_recaptcha' => now()->subSecond()->getTimestamp()]);

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();
        $mock->shouldNotReceive('getChallenge');

        $this->post('v2/checkbox')->assertSessionHasErrors()->assertSessionMissing('_recaptcha');
        $this->post('v2/invisible')->assertSessionHasErrors()->assertSessionMissing('_recaptcha');
        $this->post('v2/android')->assertSessionHasErrors()->assertSessionMissing('_recaptcha');
    }

    public function test_doesnt_bypasses_check_if_remember_disabled_when_config_overridden(): void
    {
        config([
            'captchavel.remember.enabled' => true,
        ]);

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->times(3)->andReturnFalse();
        $mock->expects('shouldFake')->times(3)->andReturnFalse();
        $mock->shouldNotReceive('getChallenge');

        $this->app['router']->post('v2/checkbox/dont-remember', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:checkbox,false');

        $this->app['router']->post('v2/invisible/dont-remember', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:invisible,false');

        $this->app['router']->post('v2/android/dont-remember', function () {
            if (app()->has(ReCaptchaResponse::class)) {
                return app(ReCaptchaResponse::class);
            }
        })->middleware('recaptcha:android,false');

        $this->post('v2/checkbox/dont-remember')->assertSessionHasErrors();
        $this->post('v2/invisible/dont-remember')->assertSessionHasErrors();
        $this->post('v2/android/dont-remember')->assertSessionHasErrors();
    }
}
