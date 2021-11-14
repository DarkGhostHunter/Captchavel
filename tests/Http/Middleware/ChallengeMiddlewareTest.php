<?php

namespace Tests\Http\Middleware;

use DarkGhostHunter\Captchavel\Captchavel;
use LogicException;
use Orchestra\Testbench\TestCase;
use Tests\CreatesFulfilledResponse;
use Tests\RegistersPackage;

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
        static::assertSame('Use the [recaptcha.score] middleware to capture score-driven reCAPTCHA.', $exception->getMessage());
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

        $this->spy(Captchavel::class)->shouldNotReceive('getChallenge');

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

        $mock->shouldReceive('isEnabled')->times(3)->andReturnTrue();
        $mock->shouldReceive('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success' => true,
            'foo'     => 'bar',
        ]);

        $mock->shouldReceive('getChallenge')->once()
            ->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')->once()
            ->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')->once()
            ->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_uses_custom_input(): void
    {
        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('isEnabled')->times(3)->andReturnTrue();
        $mock->shouldReceive('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success' => true,
            'score'   => 0.5,
            'foo'     => 'bar',
        ], 'bar');

        $mock->shouldReceive('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', 'bar')->andReturn($response);
        $mock->shouldReceive('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', 'bar')->andReturn($response);
        $mock->shouldReceive('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', 'bar')->andReturn($response);

        $this->post('v2/checkbox/input_bar', ['bar' => 'token'])->assertOk();
        $this->post('v2/invisible/input_bar', ['bar' => 'token'])->assertOk();
        $this->post('v2/android/input_bar', ['bar' => 'token'])->assertOk();
    }

    public function test_exception_when_token_absent(): void
    {
        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('isEnabled')->times(12)->andReturnTrue();
        $mock->shouldReceive('shouldFake')->times(12)->andReturnFalse();

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

    public function test_exception_when_response_failed(): void
    {
        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('isEnabled')->times(6)->andReturnTrue();
        $mock->shouldReceive('shouldFake')->times(6)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success' => false,
            'foo'     => 'bar',
        ]);

        $mock->shouldReceive('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')
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

        $mock->shouldReceive('isEnabled')->times(6)->andReturnTrue();
        $mock->shouldReceive('shouldFake')->times(6)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'foo' => 'bar',
        ]);

        $mock->shouldReceive('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')
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

        $mock->shouldReceive('isEnabled')->times(3)->andReturnTrue();
        $mock->shouldReceive('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success' => true,
            'foo'     => 'bar',
        ]);

        $mock->shouldReceive('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_no_error_if_not_hostname_same(): void
    {
        config(['captchavel.hostname' => 'foo']);

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('isEnabled')->times(3)->andReturnTrue();
        $mock->shouldReceive('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'  => true,
            'foo'      => 'bar',
            'hostname' => 'foo',
        ]);

        $mock->shouldReceive('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_exception_if_hostname_not_equal(): void
    {
        config(['captchavel.hostname' => 'bar']);

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('isEnabled')->times(6)->andReturnTrue();
        $mock->shouldReceive('shouldFake')->times(6)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'  => true,
            'foo'      => 'bar',
            'hostname' => 'foo',
        ]);

        $mock->shouldReceive('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')
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

        $mock->shouldReceive('isEnabled')->times(3)->andReturnTrue();
        $mock->shouldReceive('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'  => true,
            'foo'      => 'bar',
            'apk_package_name' => 'foo',
        ]);

        $mock->shouldReceive('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_no_error_if_no_apk_same(): void
    {
        config(['captchavel.apk_package_name' => 'foo']);

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('isEnabled')->times(3)->andReturnTrue();
        $mock->shouldReceive('shouldFake')->times(3)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'  => true,
            'foo'      => 'bar',
            'apk_package_name' => 'foo',
        ]);

        $mock->shouldReceive('getChallenge')
            ->once()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')
            ->once()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')
            ->once()->with('token', '127.0.0.1', 'android', Captchavel::INPUT)->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_exception_if_apk_not_equal(): void
    {
        config(['captchavel.apk_package_name' => 'bar']);

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('isEnabled')->times(6)->andReturnTrue();
        $mock->shouldReceive('shouldFake')->times(6)->andReturnFalse();

        $response = $this->fulfilledResponse([
            'success'  => true,
            'foo'      => 'bar',
            'apk_package_name' => 'foo',
        ]);

        $mock->shouldReceive('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'checkbox', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')
            ->twice()->with('token', '127.0.0.1', 'invisible', Captchavel::INPUT)->andReturn($response);
        $mock->shouldReceive('getChallenge')
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
        
    }

    public function test_challenge_is_remembered_in_session(): void
    {

    }

    public function test_challenge_is_remembered_in_session_when_config_overriden(): void
    {

    }

    public function test_challenge_is_remembered_forever(): void
    {

    }

    public function test_challenge_is_remembered_forever_when_config_overriden(): void
    {

    }

    public function test_challenge_is_remembered_with_different_offset(): void
    {

    }

    public function test_challenge_is_not_remembered_when_config_overriden(): void
    {

    }

    public function test_bypasses_check_if_session_has_remember_not_expired(): void
    {

    }

    public function test_bypasses_check_if_session_has_remember_forever(): void
    {

    }

    public function test_doesnt_bypasses_check_if_session_has_not_remember(): void
    {

    }

    public function test_doesnt_bypasses_check_if_remember_disabled_when_config_overriden(): void
    {

    }

    public function test_challenge_renewed_if_remember_present_and_disabled_when_config_overriden(): void
    {

    }

    public function test_challenge_not_renewed_if_config_false_and_remember_present_and_disabled_when_config_overriden(): void
    {

    }
}
