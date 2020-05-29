<?php

namespace Tests\Http\Middleware;

use Tests\RegistersPackage;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use DarkGhostHunter\Captchavel\Events\ReCaptchaResponseReceived;

class ChallengeMiddlewareTest extends TestCase
{
    use RegistersPackage;
    use UsesRoutesWithMiddleware;

    protected function setUp() : void
    {
        $this->afterApplicationCreated(function () {
            $this->createsRoutes();
            config(['captchavel.fake' => false]);
        });

        parent::setUp();
    }

    public function test_exception_if_no_challenge_specified()
    {
        Route::post('test', function () {
            return 'ok';
        })->middleware('recaptcha.v2');

        $this->post('test')->assertStatus(500);

        $this->postJson('test')->assertJson(['message' => 'Server Error']);
    }

    public function test_bypass_if_not_enabled()
    {
        config(['captchavel.enable' => false]);

        $event = Event::fake();

        $this->mock(Captchavel::class)->shouldNotReceive('useCredentials', 'retrieve');

        $this->post('v2/checkbox')->assertOk();
        $this->post('v2/invisible')->assertOk();
        $this->post('v2/android')->assertOk();

        $event->assertNotDispatched(ReCaptchaResponseReceived::class);
    }

    public function test_fakes_success()
    {
        config(['captchavel.fake' => true]);

        $this->post('v2/checkbox')->assertOk();
        $this->post('v2/checkbox/input_bar')->assertOk();
        $this->post('v2/invisible')->assertOk();
        $this->post('v2/invisible/input_bar')->assertOk();
        $this->post('v2/android')->assertOk();
        $this->post('v2/android/input_bar')->assertOk();
    }

    public function test_validates_if_real()
    {
        $event = Event::fake();

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')->once()->with(2, 'checkbox')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->once()->with(2, 'invisible')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->once()->with(2, 'android')->andReturnSelf();

        $mock->shouldReceive('retrieve')
            ->times(3)
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'score' => 0.5,
                'foo' => 'bar'
            ]));

        $this->post('v2/checkbox', [
            Captchavel::INPUT => 'token'
        ])->assertOk();
        $this->post('v2/invisible', [
            Captchavel::INPUT => 'token'
        ])->assertOk();
        $this->post('v2/android', [
            Captchavel::INPUT => 'token'
        ])->assertOk();

        $event->assertDispatchedTimes(ReCaptchaResponseReceived::class, 3);
    }

    public function test_uses_custom_input()
    {
        $event = Event::fake();

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')->once()->with(2, 'checkbox')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->once()->with(2, 'invisible')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->once()->with(2, 'android')->andReturnSelf();

        $mock->shouldReceive('retrieve')
            ->times(3)
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'score' => 0.5,
                'foo' => 'bar'
            ]));

        $this->post('v2/checkbox/input_bar', [
            'bar' => 'token'
        ])->assertOk();
        $this->post('v2/invisible/input_bar', [
            'bar' => 'token'
        ])->assertOk();
        $this->post('v2/android/input_bar', [
            'bar' => 'token'
        ])->assertOk();

        $event->assertDispatchedTimes(ReCaptchaResponseReceived::class, 3);
    }

    public function test_exception_when_token_absent()
    {
        $event = Event::fake();

        $mock = $this->mock(Captchavel::class);

        $mock->shouldNotReceive('useCredentials', 'retrieve');

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

        $event->assertNotDispatched(ReCaptchaResponseReceived::class);
    }

    public function test_exception_when_response_invalid()
    {
        $event = Event::fake();

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')->twice()->with(2, 'checkbox')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->twice()->with(2, 'invisible')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->twice()->with(2, 'android')->andReturnSelf();

        $mock->shouldReceive('retrieve')
            ->times(6)
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => false,
            ]));

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/checkbox', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/invisible', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/android', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors(Captchavel::INPUT);

        $event->assertDispatchedTimes(ReCaptchaResponseReceived::class, 6);
    }

    public function test_no_error_if_not_hostname_issued()
    {
        config(['captchavel.hostname' => null]);

        $event = Event::fake();

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')->once()->with(2, 'checkbox')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->once()->with(2, 'invisible')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->once()->with(2, 'android')->andReturnSelf();

        $mock->shouldReceive('retrieve')
            ->times(3)
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'hostname' => 'foo'
            ]));

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();

        $event->assertDispatchedTimes(ReCaptchaResponseReceived::class, 3);
    }

    public function test_no_error_if_not_hostname_same()
    {
        config(['captchavel.hostname' => 'foo']);

        $event = Event::fake();

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')->once()->with(2, 'checkbox')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->once()->with(2, 'invisible')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->once()->with(2, 'android')->andReturnSelf();

        $mock->shouldReceive('retrieve')
            ->times(3)
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'hostname' => 'foo'
            ]));

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();

        $event->assertDispatchedTimes(ReCaptchaResponseReceived::class, 3);
    }

    public function test_exception_if_hostname_not_equal()
    {
        config(['captchavel.hostname' => 'bar']);

        $event = Event::fake();

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')->twice()->with(2, 'checkbox')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->twice()->with(2, 'invisible')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->twice()->with(2, 'android')->andReturnSelf();

        $mock->shouldReceive('retrieve')
            ->times(6)
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'hostname' => 'foo'
            ]));

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/checkbox', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors('hostname');
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/invisible', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors('hostname');
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/android', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors('hostname');

        $event->assertDispatchedTimes(ReCaptchaResponseReceived::class, 6);
    }

    public function test_no_error_if_no_apk_issued()
    {
        config(['captchavel.apk_package_name' => null]);

        $event = Event::fake();

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')->once()->with(2, 'checkbox')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->once()->with(2, 'invisible')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->once()->with(2, 'android')->andReturnSelf();

        $mock->shouldReceive('retrieve')
            ->times(3)
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'apk_package_name' => 'foo'
            ]));

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();

        $event->assertDispatchedTimes(ReCaptchaResponseReceived::class, 3);
    }

    public function test_no_error_if_no_apk_same()
    {
        config(['captchavel.apk_package_name' => 'foo']);

        $event = Event::fake();

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')->once()->with(2, 'checkbox')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->once()->with(2, 'invisible')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->once()->with(2, 'android')->andReturnSelf();

        $mock->shouldReceive('retrieve')
            ->times(3)
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'apk_package_name' => 'foo'
            ]));

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertOk();
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertOk();

        $event->assertDispatchedTimes(ReCaptchaResponseReceived::class, 3);
    }

    public function test_exception_if_apk_not_equal()
    {
        config(['captchavel.apk_package_name' => 'bar']);

        $event = Event::fake();

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')->twice()->with(2, 'checkbox')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->twice()->with(2, 'invisible')->andReturnSelf();
        $mock->shouldReceive('useCredentials')->twice()->with(2, 'android')->andReturnSelf();

        $mock->shouldReceive('retrieve')
            ->times(6)
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'apk_package_name' => 'foo'
            ]));

        $this->post('v2/checkbox', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/checkbox', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors('apk_package_name');
        $this->post('v2/invisible', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/invisible', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors('apk_package_name');
        $this->post('v2/android', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('v2/android', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors('apk_package_name');

        $event->assertDispatchedTimes(ReCaptchaResponseReceived::class, 6);
    }
}
