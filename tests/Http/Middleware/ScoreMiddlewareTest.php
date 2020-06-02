<?php

namespace Tests\Http\Middleware;

use Tests\RegistersPackage;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Event;
use DarkGhostHunter\Captchavel\Captchavel;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use DarkGhostHunter\Captchavel\Events\ReCaptchaResponseReceived;

class ScoreMiddlewareTest extends TestCase
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

    public function test_bypass_if_not_enabled()
    {
        $event = Event::fake();

        $this->mock(Captchavel::class)->shouldNotReceive('useCredentials', 'retrieve');

        $this->post('v3/default')->assertOk();

        $event->assertNotDispatched(ReCaptchaResponseReceived::class);
    }

    public function test_validates_if_real()
    {
        $mock = $this->mock(Captchavel::class);

        $event = Event::fake();

        $mock->shouldReceive('useCredentials')
            ->with(3, null)
            ->andReturnSelf();
        $mock->shouldReceive('retrieve')
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'score' => 0.5,
                'foo' => 'bar'
            ]));

        $this->post('v3/default', [
            Captchavel::INPUT => 'token'
        ])
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'score' => 0.5,
                'foo' => 'bar'
            ]);

        $event->assertDispatched(ReCaptchaResponseReceived::class, function ($event) {
            return $event->response->foo === 'bar' && $event->request instanceof Request;
        });
    }

    public function test_fakes_human_response_automatically()
    {
        config(['captchavel.fake' => true]);

        $event = Event::fake();

        $this->post('v3/default')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'score' => 1,
                'action' => null,
                'hostname' => null,
                'apk_package_name' => null,
            ]);

        $event->assertDispatched(ReCaptchaResponseReceived::class, function ($event) {
            return $event->response->success === true && $event->request instanceof Request;
        });
    }

    public function test_fakes_robot_response_if_input_is_robot_present()
    {
        config(['captchavel.fake' => true]);

        $event = Event::fake();

        $this->post('v3/default', ['is_robot' => 'on'])
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'score' => 0,
                'action' => null,
                'hostname' => null,
                'apk_package_name' => null,
            ]);

        $event->assertDispatched(ReCaptchaResponseReceived::class, function ($event) {
            return $event->response->success === true && $event->request instanceof Request;
        });
    }

    public function test_uses_custom_threshold()
    {
        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')->andReturnSelf();
        $mock->shouldReceive('retrieve')->andReturn(new ReCaptchaResponse([
            'success' => true,
            'score' => 0.7,
            'foo' => 'bar'
        ]));

        $event = Event::fake();

        Route::post('test', function (ReCaptchaResponse $response) {
            return [$response->isHuman(), $response->isRobot(), $response->score];
        })->middleware('recaptcha.v3:0.7');

        $this->post('test', [
            Captchavel::INPUT => 'token'
        ])
            ->assertOk()
            ->assertExactJson([
                true, false, 0.7
            ]);

        $event->assertDispatched(ReCaptchaResponseReceived::class, function ($event) {
            return $event->response->success === true && $event->request instanceof Request;
        });
    }

    public function test_uses_custom_input()
    {
        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')
            ->andReturnSelf();
        $mock->shouldReceive('retrieve')
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'score' => 0.7,
                'foo' => 'bar'
            ]));

        $event = Event::fake();

        Route::post('test', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.v3:null,null,foo');

        $this->post('test', [
            'foo' => 'token'
        ])
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'score' => 0.7,
                'foo' => 'bar',
            ]);

        $event->assertDispatched(ReCaptchaResponseReceived::class, function ($event) {
            return $event->response->success === true && $event->request instanceof Request;
        });
    }

    public function test_exception_when_token_absent()
    {
        $event = Event::fake();

        $this->post('v3/default', [
            'foo' => 'bar'
        ])->assertRedirect('/');

        $this->postJson('v3/default', [
            'foo' => 'bar'
        ])->assertJsonValidationErrors(Captchavel::INPUT);

        $event->assertNotDispatched(ReCaptchaResponseReceived::class);
    }

    public function test_exception_when_response_invalid()
    {
        $event = Event::fake();

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')
            ->andReturnSelf();
        $mock->shouldReceive('retrieve')
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => false,
            ]));

        $this->post('v3/default', [
            Captchavel::INPUT => 'token'
        ])->assertRedirect('/');

        $event->assertDispatched(ReCaptchaResponseReceived::class, function ($event) {
            return $event->response->success === false && $event->request instanceof Request;
        });

        $this->postJson('v3/default', [
            'foo' => 'bar'
        ])->assertJsonValidationErrors(Captchavel::INPUT);
    }

    public function test_no_error_if_not_hostname_issued()
    {
        config(['captchavel.hostname' => null]);

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')
            ->andReturnSelf();
        $mock->shouldReceive('retrieve')
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'hostname' => 'foo',
            ]));

        $event = Event::fake();

        $this->post('v3/default', [
            Captchavel::INPUT => 'token'
        ])->assertOk();

        $event->assertDispatched(ReCaptchaResponseReceived::class, function ($event) {
            return $event->response->success === true && $event->request instanceof Request;
        });

        $this->postJson('v3/default', [
            Captchavel::INPUT => 'token'
        ])->assertOk();
    }

    public function test_no_error_if_hostname_same()
    {
        config(['captchavel.hostname' => 'bar']);

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')
            ->andReturnSelf();
        $mock->shouldReceive('retrieve')
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'hostname' => 'bar',
            ]));

        $event = Event::fake();

        $this->post('v3/default', [
            Captchavel::INPUT => 'token'
        ])->assertOk();

        $event->assertDispatched(ReCaptchaResponseReceived::class, function ($event) {
            return $event->response->success === true && $event->request instanceof Request;
        });

        $this->postJson('v3/default', [
            Captchavel::INPUT => 'token'
        ])->assertOk();
    }

    public function test_exception_if_hostname_not_equal()
    {
        config(['captchavel.hostname' => 'bar']);

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')
            ->andReturnSelf();
        $mock->shouldReceive('retrieve')
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'hostname' => 'foo',
            ]));

        $event = Event::fake();

        $this->post('v3/default', [
            Captchavel::INPUT => 'token'
        ])->assertRedirect('/');

        $event->assertDispatched(ReCaptchaResponseReceived::class, function ($event) {
            return $event->response->hostname === 'foo' && $event->request instanceof Request;
        });

        $this->postJson('v3/default', [
            Captchavel::INPUT => 'token'
        ])->assertJsonValidationErrors('hostname');
    }

    public function test_no_error_if_no_apk_issued()
    {
        config(['captchavel.apk_package_name' => null]);

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')
            ->andReturnSelf();
        $mock->shouldReceive('retrieve')
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'apk_package_name' => 'foo',
            ]));

        $event = Event::fake();

        $this->post('v3/default', [
            Captchavel::INPUT => 'token'
        ])->assertOk();

        $event->assertDispatched(ReCaptchaResponseReceived::class, function ($event) {
            return $event->response->apk_package_name === 'foo' && $event->request instanceof Request;
        });

        $this->postJson('v3/default', [
            Captchavel::INPUT => 'token'
        ])->assertOk();
    }

    public function test_no_error_if_apk_same()
    {
        config(['captchavel.apk_package_name' => 'foo']);

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')
            ->andReturnSelf();
        $mock->shouldReceive('retrieve')
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'apk_package_name' => 'foo',
            ]));

        $event = Event::fake();

        $this->post('v3/default', [
            Captchavel::INPUT => 'token'
        ])->assertOk();

        $event->assertDispatched(ReCaptchaResponseReceived::class, function ($event) {
            return $event->response->apk_package_name === 'foo' && $event->request instanceof Request;
        });

        $this->postJson('v3/default', [
            Captchavel::INPUT => 'token'
        ])->assertOk();
    }

    public function test_exception_if_apk_not_equal()
    {
        config(['captchavel.apk_package_name' => 'bar']);

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')
            ->andReturnSelf();
        $mock->shouldReceive('retrieve')
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'apk_package_name' => null,
            ]));

        $event = Event::fake();

        $this->post('v3/default', [
            Captchavel::INPUT => 'token'
        ])->assertRedirect('/');

        $event->assertDispatched(ReCaptchaResponseReceived::class, function ($event) {
            return $event->response->apk_package_name === null && $event->request instanceof Request;
        });

        $this->postJson('v3/default', [
            Captchavel::INPUT => 'token'
        ])->assertJsonValidationErrors('apk_package_name');
    }

    public function test_no_error_if_no_action()
    {
        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')
            ->andReturnSelf();
        $mock->shouldReceive('retrieve')
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'apk_package_name' => null,
                'action' => 'foo',
            ]));

        $event = Event::fake();

        Route::post('test', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.v3:null,null');

        $this->post('test', [
            Captchavel::INPUT => 'token'
        ])->assertOk();

        $event->assertDispatched(ReCaptchaResponseReceived::class, function ($event) {
            return $event->response->action === 'foo' && $event->request instanceof Request;
        });
    }

    public function test_no_error_if_action_same()
    {
        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')
            ->andReturnSelf();
        $mock->shouldReceive('retrieve')
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'apk_package_name' => null,
                'action' => 'foo',
            ]));

        $event = Event::fake();

        Route::post('test', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.v3:null,foo');

        $this->post('test', [
            Captchavel::INPUT => 'token'
        ])->assertOk();

        $event->assertDispatched(ReCaptchaResponseReceived::class, function ($event) {
            return $event->response->action === 'foo' && $event->request instanceof Request;
        });
    }

    public function test_exception_if_action_not_equal()
    {
        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('useCredentials')
            ->andReturnSelf();
        $mock->shouldReceive('retrieve')
            ->with('token', '127.0.0.1')
            ->andReturn(new ReCaptchaResponse([
                'success' => true,
                'apk_package_name' => null,
                'action' => 'foo',
            ]));

        Route::post('test', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.v3:null,bar');

        $this->post('test', [
            Captchavel::INPUT => 'token'
        ])->assertRedirect('/');

        $this->postJson('test', [
            Captchavel::INPUT => 'token'
        ])->assertJsonValidationErrors('action');
    }

    public function test_checks_for_human_score()
    {
        config(['captchavel.credentials.v3.secret' => 'secret']);
        config(['captchavel.fake' => false]);

        $mock = $this->mock(Factory::class);

        $mock->shouldReceive('asForm')->withNoArgs()->times(4)->andReturnSelf();
        $mock->shouldReceive('withOptions')->with(['version' => 2.0])->times(4)->andReturnSelf();
        $mock->shouldReceive('post')
            ->with(Captchavel::RECAPTCHA_ENDPOINT, [
                'secret'   => 'secret',
                'response' => 'token',
                'remoteip' => '127.0.0.1',
            ])
            ->times(4)
            ->andReturn(new Response(new GuzzleResponse(200, ['Content-type' => 'application/json'], json_encode([
                'success' => true,
                'score'   => 0.5,
            ]))));

        Route::post('human_human', function (Request $request) {
            return $request->isHuman() ? 'true' : 'false';
        })->middleware('recaptcha.v3:0.7');

        Route::post('human_robot', function (Request $request) {
            return $request->isRobot() ? 'true' : 'false';
        })->middleware('recaptcha.v3:0.7');

        Route::post('robot_human', function (Request $request) {
            return $request->isHuman() ? 'true' : 'false';
        })->middleware('recaptcha.v3:0.3');

        Route::post('robot_robot', function (Request $request) {
            return $request->isRobot() ? 'true' : 'false';
        })->middleware('recaptcha.v3:0.3');

        $this->post('human_human', [Captchavel::INPUT => 'token'])->assertSee('false');
        $this->post('human_robot', [Captchavel::INPUT => 'token'])->assertSee('true');
        $this->post('robot_human', [Captchavel::INPUT => 'token'])->assertSee('true');
        $this->post('robot_robot', [Captchavel::INPUT => 'token'])->assertSee('false');
    }
}
