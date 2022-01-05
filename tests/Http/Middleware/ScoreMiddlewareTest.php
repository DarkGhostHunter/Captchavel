<?php
/** @noinspection ALL */

namespace Tests\Http\Middleware;

use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\CaptchavelFake;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use DarkGhostHunter\Captchavel\ReCaptcha;
use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Tests\CreatesFulfilledResponse;
use Tests\RegistersPackage;
use function config;
use function trans;

class ScoreMiddlewareTest extends TestCase
{
    use RegistersPackage;
    use UsesRoutesWithMiddleware;
    use CreatesFulfilledResponse;

    protected function setUp(): void
    {
        $this->afterApplicationCreated(
            function (): void {
                $this->createsRoutes();
                $this->app['config']->set('captchavel.fake', false);
            }
        );

        parent::setUp();
    }

    public function test_fakes_response_if_authenticated_in_guard(): void
    {
        $this->app['router']->post('v3/guarded', [__CLASS__, 'returnSameResponse'])->middleware(ReCaptcha::score()->except('web')->toString());

        $this->actingAs(User::make(), 'web');

        $this->post('v3/guarded')->assertOk();
        $this->assertEquals(1.0, $this->app[ReCaptchaResponse::class]->score);
        $this->assertInstanceOf(CaptchavelFake::class, $this->app[Captchavel::class]);
    }

    public function test_fakes_response_if_not_enabled(): void
    {
        config(['captchavel.enable' => false]);

        $this->post('v3/default')->assertOk();

        $this->assertEquals(1.0, $this->app[ReCaptchaResponse::class]->score);
        $this->assertInstanceOf(CaptchavelFake::class, $this->app[Captchavel::class]);
    }

    public function test_fakes_response_if_enabled_and_fake(): void
    {
        config(['captchavel.enable' => true]);
        config(['captchavel.fake' => true]);

        $this->post('v3/default')->assertOk();

        $this->assertEquals(1.0, $this->app[ReCaptchaResponse::class]->score);
        $this->assertInstanceOf(CaptchavelFake::class, $this->app[Captchavel::class]);
    }

    public function test_validates_if_real(): void
    {
        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->once()->andReturnFalse();

        $mock->expects('shouldFake')->once()->andReturnFalse();
        $mock->expects('getChallenge')
            ->with('token', '127.0.0.1', Captchavel::SCORE, Captchavel::INPUT, null)
            ->andReturn(
                $this->fulfilledResponse([
                    'success' => true,
                    'score'   => 0.5,
                    'foo'     => 'bar',
                ])
            );

        $this->post('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'score'   => 0.5,
                'foo'     => 'bar',
            ]);
    }

    public function test_fakes_human_response_automatically(): void
    {
        config(['captchavel.fake' => true]);

        Carbon::setTestNow(Carbon::now());

        $this->post('v3/default')
            ->assertOk()
            ->assertExactJson(
                [
                    'success'          => true,
                    'score'            => 1,
                    'action'           => null,
                    'hostname'         => null,
                    'apk_package_name' => null,
                    'challenge_ts'     => Carbon::now()->toAtomString(),
                ]
            );
    }

    public function test_fakes_robot_response_if_input_is_robot_present(): void
    {
        config(['captchavel.fake' => true]);

        Carbon::setTestNow(Carbon::now());

        $this->post('v3/default', ['is_robot' => 'on'])
            ->assertOk()
            ->assertExactJson(
                [
                    'success'          => true,
                    'score'            => 0,
                    'action'           => null,
                    'hostname'         => null,
                    'apk_package_name' => null,
                    'challenge_ts'     => Carbon::now()->toAtomString(),
                ]
            );
    }

    public function test_uses_custom_threshold(): void
    {
        $mock = $this->spy(Captchavel::class);

        $mock->expects('getChallenge')
            ->with('token', '127.0.0.1', Captchavel::SCORE, Captchavel::INPUT, null)
            ->andReturn(
                $this->fulfilledResponse(['success' => true, 'score' => 0.7, 'foo' => 'bar'])
            );

        $this->app['router']->post('test', function (ReCaptchaResponse $response) {
            return [$response->isHuman(), $response->isRobot(), $response->score];
        })->middleware('recaptcha.score:0.7');

        $this->post('test', [Captchavel::INPUT => 'token'])
            ->assertOk()
            ->assertExactJson([true, false, 0.7]);
    }

    public function test_uses_custom_input(): void
    {
        $mock = $this->spy(Captchavel::class);

        $mock->expects('getChallenge')
            ->with('token', '127.0.0.1', Captchavel::SCORE, 'foo', null)
            ->andReturn(
                $this->fulfilledResponse(['success' => true, 'score' => 0.7, 'foo' => 'bar'])
            );

        $this->app['router']->post('test', [__CLASS__, 'returnSameResponse'])->middleware('recaptcha.score:null,null,foo');

        $this->post('test', ['foo' => 'token'])
            ->assertOk()
            ->assertExactJson(['success' => true, 'score' => 0.7, 'foo' => 'bar']);
    }

    public function test_fakes_human_score_if_authenticated(): void
    {
        $mock = $this->mock(Captchavel::class);

        $mock->allows('getChallenge')->never();

        $this->actingAs(new GenericUser([]), 'web');

        $this->app['router']->post('score/auth', [__CLASS__, 'returnSameResponse'])
            ->middleware('recaptcha.score:0.5,null,null,null,web');

        $this->post('/score/auth')->assertOk();
    }

    public function test_fakes_human_score_if_authenticated_in_any_guard(): void
    {
        config()->set('auth.guards.api', [
            'driver' => 'session',
            'provider' => 'users',
        ]);

        $mock = $this->mock(Captchavel::class);

        $mock->allows('getChallenge')->never();

        $this->actingAs(new GenericUser([]), 'api');

        $this->app['router']->post('score/auth', [__CLASS__, 'returnSameResponse'])
            ->middleware('recaptcha.score:0.5,null,null,null,web');

        $this->post('/score/auth')->assertOk();
    }

    public function test_error_if_is_guest_on_set_guard(): void
    {
        config()->set('auth.guards.api', [
            'driver' => 'session',
            'provider' => 'users',
        ]);

        $mock = $this->mock(Captchavel::class);

        $mock->expects('isDisabled')->once()->andReturnFalse();
        $mock->expects('shouldFake')->once()->andReturnFalse();
        $mock->allows('getChallenge')->never();

        $this->actingAs(new GenericUser([]), 'api');

        $this->app['router']->post('score/auth', [__CLASS__, 'returnSameResponse'])
            ->middleware('recaptcha.score:0.5,null,null,null,web');

        $this->post('/score/auth')
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.missing'))
            ->assertRedirect('/');
    }

    public function test_exception_when_token_absent(): void
    {
        $this->post('v3/default', ['foo' => 'bar'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.error'))
            ->assertRedirect('/');

        $this->postJson('v3/default', ['foo' => 'bar'])
            ->assertJsonValidationErrors(Captchavel::INPUT);
    }

    public function test_exception_when_token_null(): void
    {
        $this->post('v3/default', [Captchavel::INPUT => null])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.error'))
            ->assertRedirect('/');

        $this->postJson('v3/default', [Captchavel::INPUT => null])
            ->assertJsonValidationErrors(Captchavel::INPUT);
    }

    public function test_exception_when_response_invalid(): void
    {
        $mock = $this->spy(Captchavel::class);

        $mock->expects('getChallenge')
            ->with('token', '127.0.0.1', Captchavel::SCORE, Captchavel::INPUT, null)
            ->andReturn(
                $this->fulfilledResponse(['success' => false, 'score' => 0.7, 'foo' => 'bar'])
            );

        $this->post('v3/default', [Captchavel::INPUT => 'token'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.error'))
            ->assertRedirect('/');

        $this->postJson('v3/default', ['foo' => 'bar'])
            ->assertJsonValidationErrors(Captchavel::INPUT);
    }

    public function test_no_error_if_not_hostname_issued(): void
    {
        config(['captchavel.hostname' => null]);

        $mock = $this->spy(Captchavel::class);

        $mock->expects('getChallenge')
            ->twice()
            ->with('token', '127.0.0.1', Captchavel::SCORE, Captchavel::INPUT, null)
            ->andReturn(
                $this->fulfilledResponse(['success' => true, 'score' => 0.7, 'hostname' => 'foo'])
            );

        $this->post('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk();

        $this->postJson('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk();
    }

    public function test_no_error_if_hostname_same(): void
    {
        config(['captchavel.hostname' => 'bar']);

        $mock = $this->spy(Captchavel::class);

        $mock->expects('getChallenge')
            ->twice()
            ->with('token', '127.0.0.1', Captchavel::SCORE, Captchavel::INPUT, null)
            ->andReturn(
                $this->fulfilledResponse(['success' => true, 'score' => 0.7, 'hostname' => 'bar'])
            );

        $this->post('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk();

        $this->postJson('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk();
    }

    public function test_exception_if_hostname_not_equal(): void
    {
        config(['captchavel.hostname' => 'bar']);

        $mock = $this->spy(Captchavel::class);

        $mock->expects('getChallenge')
            ->twice()
            ->with('token', '127.0.0.1', Captchavel::SCORE, Captchavel::INPUT, null)
            ->andReturn(
                $this->fulfilledResponse(['success' => true, 'score' => 0.7, 'hostname' => 'foo'])
            );

        $this->post('v3/default', [Captchavel::INPUT => 'token'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.match'))
            ->assertRedirect('/');

        $this->postJson('v3/default', [Captchavel::INPUT => 'token'])
            ->assertJsonValidationErrors(Captchavel::INPUT);
    }

    public function test_no_error_if_no_apk_issued(): void
    {
        config(['captchavel.apk_package_name' => null]);

        $mock = $this->spy(Captchavel::class);

        $mock->expects('getChallenge')
            ->twice()
            ->with('token', '127.0.0.1', Captchavel::SCORE, Captchavel::INPUT, null)
            ->andReturn(
                $this->fulfilledResponse(['success' => true, 'score' => 0.7, 'apk_package_name' => 'foo'])
            );

        $this->post('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk();

        $this->postJson('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk();
    }

    public function test_no_error_if_apk_same(): void
    {
        config(['captchavel.apk_package_name' => 'foo']);

        $mock = $this->spy(Captchavel::class);

        $mock->expects('getChallenge')
            ->twice()
            ->with('token', '127.0.0.1', Captchavel::SCORE, Captchavel::INPUT, null)
            ->andReturn(
                $this->fulfilledResponse(['success' => true, 'score' => 0.7, 'apk_package_name' => 'foo'])
            );

        $this->post('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk();

        $this->postJson('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk();
    }

    public function test_exception_if_apk_not_equal(): void
    {
        config(['captchavel.apk_package_name' => 'bar']);

        $mock = $this->spy(Captchavel::class);

        $mock->expects('getChallenge')
            ->twice()
            ->with('token', '127.0.0.1', Captchavel::SCORE, Captchavel::INPUT, null)
            ->andReturn(
                $this->fulfilledResponse(['success' => true, 'score' => 0.7, 'apk_package_name' => null])
            );

        $this->post('v3/default', [Captchavel::INPUT => 'token'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.match'))
            ->assertRedirect('/');

        $this->postJson('v3/default', [Captchavel::INPUT => 'token'])
            ->assertJsonValidationErrors(Captchavel::INPUT);
    }

    public function test_no_error_if_no_action(): void
    {
        $mock = $this->spy(Captchavel::class);

        $mock->expects('getChallenge')
            ->with('token', '127.0.0.1', Captchavel::SCORE, Captchavel::INPUT, null)
            ->andReturn(
                $this->fulfilledResponse(['success' => true, 'action' => 'foo', 'apk_package_name' => null])
            );

        $this->app['router']->post('test', [__CLASS__, 'returnSameResponse'])->middleware('recaptcha.score:null,null');

        $this->post('test', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_no_error_if_action_same(): void
    {
        $mock = $this->spy(Captchavel::class);

        $mock->expects('getChallenge')
            ->with('token', '127.0.0.1', Captchavel::SCORE, Captchavel::INPUT, 'foo')
            ->andReturn(
                $this->fulfilledResponse(['success' => true, 'action' => 'foo', 'apk_package_name' => null])
            );

        $this->app['router']->post('test', [__CLASS__, 'returnSameResponse'])->middleware('recaptcha.score:null,foo');

        $this->post('test', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_exception_if_action_not_equal(): void
    {
        $mock = $this->spy(Captchavel::class);

        $mock->expects('getChallenge')
            ->twice()
            ->with('token', '127.0.0.1', Captchavel::SCORE, Captchavel::INPUT, 'bar')
            ->andReturn(
                $this->fulfilledResponse(
                    ['success' => true, 'action' => 'foo', 'apk_package_name' => null],
                    Captchavel::INPUT,
                    'bar'
                )
            );

        $this->app['router']->post('test', [__CLASS__, 'returnSameResponse'])->middleware('recaptcha.score:null,bar');

        $this->post('test', [Captchavel::INPUT => 'token'])
            ->assertSessionHasErrors(Captchavel::INPUT, trans('captchavel::validation.match'))
            ->assertRedirect('/');
        $this->postJson('test', [Captchavel::INPUT => 'token'])
            ->assertJsonValidationErrors(Captchavel::INPUT);
    }

    public function test_checks_for_human_score(): void
    {
        config(['captchavel.credentials.score.secret' => 'secret']);
        config(['captchavel.fake' => false]);

        $mock = $this->mock(Factory::class);

        $mock->expects('async')->withNoArgs()->times(4)->andReturnSelf();
        $mock->expects('asForm')->withNoArgs()->times(4)->andReturnSelf();
        $mock->expects('withOptions')->with(['version' => 2.0])->times(4)->andReturnSelf();
        $mock->expects('post')
            ->with(
                Captchavel::RECAPTCHA_ENDPOINT,
                [
                    'secret'   => 'secret',
                    'response' => 'token',
                    'remoteip' => '127.0.0.1',
                ]
            )
            ->times(4)
            ->andReturn(
                $this->fulfilledPromise([
                    'success' => true,
                    'score'   => 0.5,
                ])
            );

        $this->app['router']->post(
            'human_human',
            function (Request $request) {
                return $request->isHuman() ? 'true' : 'false';
            }
        )->middleware('recaptcha.score:0.7');

        $this->app['router']->post(
            'human_robot',
            function (Request $request) {
                return $request->isRobot() ? 'true' : 'false';
            }
        )->middleware('recaptcha.score:0.7');

        $this->app['router']->post(
            'robot_human',
            function (Request $request) {
                return $request->isHuman() ? 'true' : 'false';
            }
        )->middleware('recaptcha.score:0.3');

        $this->app['router']->post(
            'robot_robot',
            function (Request $request) {
                return $request->isRobot() ? 'true' : 'false';
            }
        )->middleware('recaptcha.score:0.3');

        $this->post('human_human', [Captchavel::INPUT => 'token'])->assertSee('false');
        $this->post('human_robot', [Captchavel::INPUT => 'token'])->assertSee('true');
        $this->post('robot_human', [Captchavel::INPUT => 'token'])->assertSee('true');
        $this->post('robot_robot', [Captchavel::INPUT => 'token'])->assertSee('false');
    }
}
