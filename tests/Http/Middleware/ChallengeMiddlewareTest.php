<?php

namespace Tests\Http\Middleware;

use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\Events\ReCaptchaResponseReceived;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use Orchestra\Testbench\TestCase;
use Tests\RegistersPackage;

class ChallengeMiddlewareTest extends TestCase
{
    use RegistersPackage;
    use UsesRoutesWithMiddleware;

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

    public function test_exception_if_no_challenge_specified()
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

    public function test_bypass_if_not_enabled()
    {
        config(['captchavel.enable' => false]);

        $this->mock(Captchavel::class)->shouldNotReceive('resolve');

        $this->post('v2/checkbox')->assertOk();
        $this->post('v2/invisible')->assertOk();
        $this->post('v2/android')->assertOk();
    }
    public function test_success_when_enabled_and_fake()
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

    public function test_success_when_disabled()
    {
        config(['captchavel.enable' => false]);

        $this->post('v2/checkbox')->assertOk();
        $this->post('v2/checkbox/input_bar')->assertOk();
        $this->post('v2/invisible')->assertOk();
        $this->post('v2/invisible/input_bar')->assertOk();
        $this->post('v2/android')->assertOk();
        $this->post('v2/android/input_bar')->assertOk();
    }

    public function test_validates_if_real()
    {
        $mock = $this->mock(Captchavel::class);

        $response = new ReCaptchaResponse(
            [
                'success' => true,
                'score' => 0.5,
                'foo' => 'bar',
            ]
        );

        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'checkbox')->andReturn($response);
        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'invisible')->andReturn($response);
        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'android')->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_uses_custom_input()
    {
        $mock = $this->mock(Captchavel::class);

        $response = new ReCaptchaResponse(
            [
                'success' => true,
                'score' => 0.5,
                'foo' => 'bar',
            ]
        );

        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'checkbox')->andReturn($response);
        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'invisible')->andReturn($response);
        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'android')->andReturn($response);

        $this->post('v2/checkbox/input_bar',['bar' => 'token'])->assertOk();
        $this->post('v2/invisible/input_bar',['bar' => 'token'])->assertOk();
        $this->post('v2/android/input_bar',['bar' => 'token'])->assertOk();
    }

    public function test_exception_when_token_absent()
    {
        $mock = $this->mock(Captchavel::class);

        $mock->shouldNotReceive('getChallenge');

        $this->post('v2/checkbox')->assertRedirect('/');
        $this->postJson('v2/checkbox')->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/invisible')->assertRedirect('/');
        $this->postJson('v2/invisible')->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/android')->assertRedirect('/');
        $this->postJson('v2/android')->assertJsonValidationErrors(Captchavel::INPUT);

        $this->post('v2/checkbox/input_bar')->assertRedirect('/');
        $this->postJson('v2/checkbox/input_bar')->assertJsonValidationErrors('bar');
        $this->post('v2/invisible/input_bar')->assertRedirect('/');
        $this->postJson('v2/invisible/input_bar')->assertJsonValidationErrors('bar');
        $this->post('v2/android/input_bar')->assertRedirect('/');
        $this->postJson('v2/android/input_bar')->assertJsonValidationErrors('bar');
    }

    public function test_exception_when_response_invalid()
    {
        $mock = $this->mock(Captchavel::class);

        $response = new ReCaptchaResponse(
            [
                'success' => false,
                'score' => 0.5,
                'foo' => 'bar',
            ]
        );

        $mock->shouldReceive('getChallenge')->twice()->with('token', '127.0.0.1', 'checkbox')->andReturn($response);
        $mock->shouldReceive('getChallenge')->twice()->with('token', '127.0.0.1', 'invisible')->andReturn($response);
        $mock->shouldReceive('getChallenge')->twice()->with('token', '127.0.0.1', 'android')->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/checkbox', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/invisible', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/android', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
    }

    public function test_no_error_if_not_hostname_issued()
    {
        config(['captchavel.hostname' => null]);

        $mock = $this->mock(Captchavel::class);

        $response = new ReCaptchaResponse(
            [
                'success' => true,
                'score' => 0.5,
                'foo' => 'bar',
            ]
        );

        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'checkbox')->andReturn($response);
        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'invisible')->andReturn($response);
        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'android')->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_no_error_if_not_hostname_same()
    {
        config(['captchavel.hostname' => 'foo']);

        $mock = $this->mock(Captchavel::class);

        $response = new ReCaptchaResponse(
            [
                'success' => true,
                'score' => 0.5,
                'foo' => 'bar',
                'hostname' => 'foo',
            ]
        );

        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'checkbox')->andReturn($response);
        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'invisible')->andReturn($response);
        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'android')->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_exception_if_hostname_not_equal()
    {
        config(['captchavel.hostname' => 'bar']);

        $mock = $this->mock(Captchavel::class);

        $response = new ReCaptchaResponse(
            [
                'success' => true,
                'score' => 0.5,
                'foo' => 'bar',
                'hostname' => 'foo',
            ]
        );

        $mock->shouldReceive('getChallenge')->twice()->with('token', '127.0.0.1', 'checkbox')->andReturn($response);
        $mock->shouldReceive('getChallenge')->twice()->with('token', '127.0.0.1', 'invisible')->andReturn($response);
        $mock->shouldReceive('getChallenge')->twice()->with('token', '127.0.0.1', 'android')->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/checkbox', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors('hostname');
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/invisible', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors('hostname');
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/android', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors('hostname');
    }

    public function test_no_error_if_no_apk_issued()
    {
        config(['captchavel.apk_package_name' => null]);

        $mock = $this->mock(Captchavel::class);

        $response = new ReCaptchaResponse(
            [
                'success' => true,
                'score' => 0.5,
                'foo' => 'bar',
                'apk_package_name' => 'foo',
            ]
        );

        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'checkbox')->andReturn($response);
        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'invisible')->andReturn($response);
        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'android')->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_no_error_if_no_apk_same()
    {
        config(['captchavel.apk_package_name' => 'foo']);

        $mock = $this->mock(Captchavel::class);

        $response = new ReCaptchaResponse(
            [
                'success' => true,
                'score' => 0.5,
                'foo' => 'bar',
                'apk_package_name' => 'foo',
            ]
        );

        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'checkbox')->andReturn($response);
        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'invisible')->andReturn($response);
        $mock->shouldReceive('getChallenge')->once()->with('token', '127.0.0.1', 'android')->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_exception_if_apk_not_equal()
    {
        config(['captchavel.apk_package_name' => 'bar']);

        $mock = $this->mock(Captchavel::class);

        $response = new ReCaptchaResponse(
            [
                'success' => true,
                'score' => 0.5,
                'foo' => 'bar',
                'apk_package_name' => 'foo',
            ]
        );

        $mock->shouldReceive('getChallenge')->twice()->with('token', '127.0.0.1', 'checkbox')->andReturn($response);
        $mock->shouldReceive('getChallenge')->twice()->with('token', '127.0.0.1', 'invisible')->andReturn($response);
        $mock->shouldReceive('getChallenge')->twice()->with('token', '127.0.0.1', 'android')->andReturn($response);

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/checkbox', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors('apk_package_name');
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/invisible', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors('apk_package_name');
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/android', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors('apk_package_name');
    }
}
