<?php /** @noinspection ALL */

namespace Tests\Http\Middleware;

use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Tests\RegistersPackage;

class ScoreMiddlewareTest extends TestCase
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

    public function test_bypass_if_not_enabled()
    {
        config(['captchavel.enable' => false]);

        $this->mock(Captchavel::class)->shouldNotReceive('getChallenge');

        $this->post('v3/default')->assertOk();
    }

    public function test_validates_if_real()
    {
        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('getChallenge')
            ->with('token', '127.0.0.1', 'score')
            ->andReturn(
                new ReCaptchaResponse(
                    [
                        'success' => true,
                        'score' => 0.5,
                        'foo' => 'bar',
                    ]
                )
            );

        $this->post(
            'v3/default',
            [
                Captchavel::INPUT => 'token',
            ]
        )
            ->assertOk()
            ->assertExactJson(
                [
                    'success' => true,
                    'score' => 0.5,
                    'foo' => 'bar',
                ]
            );
    }

    public function test_fakes_human_response_automatically()
    {
        config(['captchavel.fake' => true]);

        Carbon::setTestNow(Carbon::now());

        $this->post('v3/default')
            ->assertOk()
            ->assertExactJson(
                [
                    'success' => true,
                    'score' => 1,
                    'action' => null,
                    'hostname' => null,
                    'apk_package_name' => null,
                    'challenge_ts' => Carbon::now()->toAtomString(),
                ]
            );
    }

    public function test_fakes_robot_response_if_input_is_robot_present()
    {
        config(['captchavel.fake' => true]);

        Carbon::setTestNow(Carbon::now());

        $this->post('v3/default', ['is_robot' => 'on'])
            ->assertOk()
            ->assertExactJson(
                [
                    'success' => true,
                    'score' => 0,
                    'action' => null,
                    'hostname' => null,
                    'apk_package_name' => null,
                    'challenge_ts' => Carbon::now()->toAtomString(),
                ]
            );
    }

    public function test_uses_custom_threshold()
    {
        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('getChallenge')
            ->with('token', '127.0.0.1', 'score')
            ->andReturn(
            (new ReCaptchaResponse(['success' => true, 'score' => 0.7, 'foo' => 'bar']))
                ->setVersion(Captchavel::SCORE)
                ->setAsResolved()
        );

        $this->app['router']->post('test', function (ReCaptchaResponse $response) {
            return [$response->isHuman(), $response->isRobot(), $response->score];
        })->middleware('recaptcha.score:0.7');

        $this->post('test', [Captchavel::INPUT => 'token'])
            ->assertOk()
            ->assertExactJson([true, false, 0.7]);
    }

    public function test_uses_custom_input()
    {
        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('getChallenge')
            ->with('token', '127.0.0.1', 'score')
            ->andReturn(
                (new ReCaptchaResponse(['success' => true, 'score' => 0.7, 'foo' => 'bar']))
                    ->setVersion(Captchavel::SCORE)
                    ->setAsResolved()
            );

        $this->app['router']->post('test', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.score:null,null,foo');

        $this->post('test', ['foo' => 'token'])
            ->assertOk()
            ->assertExactJson(['success' => true, 'score' => 0.7, 'foo' => 'bar']);
    }

    public function test_exception_when_token_absent()
    {
        $this->post('v3/default', ['foo' => 'bar'])
            ->assertRedirect('/');

        $this->postJson('v3/default', ['foo' => 'bar'])
            ->assertJsonValidationErrors(Captchavel::INPUT);
    }

    public function test_exception_when_response_invalid()
    {
        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('getChallenge')
            ->with('token', '127.0.0.1', 'score')
            ->andReturn(
                (new ReCaptchaResponse(['success' => false, 'score' => 0.7, 'foo' => 'bar']))
                    ->setVersion(Captchavel::SCORE)
                    ->setAsResolved()
            );

        $this->post('v3/default', [Captchavel::INPUT => 'token'])
            ->assertRedirect('/');

        $this->postJson('v3/default', ['foo' => 'bar'])
            ->assertJsonValidationErrors(Captchavel::INPUT);
    }

    public function test_no_error_if_not_hostname_issued()
    {
        config(['captchavel.hostname' => null]);

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('getChallenge')
            ->with('token', '127.0.0.1', 'score')
            ->andReturn(
                (new ReCaptchaResponse(['success' => true, 'score' => 0.7, 'hostname' => 'foo']))
                    ->setVersion(Captchavel::SCORE)
                    ->setAsResolved()
            );

        $this->post('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk();

        $this->postJson('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk();
    }

    public function test_no_error_if_hostname_same()
    {
        config(['captchavel.hostname' => 'bar']);

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('getChallenge')
            ->with('token', '127.0.0.1', 'score')
            ->andReturn(
                (new ReCaptchaResponse(['success' => true, 'score' => 0.7, 'hostname' => 'bar']))
                    ->setVersion(Captchavel::SCORE)
                    ->setAsResolved()
            );

        $this->post('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk();

        $this->postJson('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk();
    }

    public function test_exception_if_hostname_not_equal()
    {
        config(['captchavel.hostname' => 'bar']);

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('getChallenge')
            ->with('token', '127.0.0.1', 'score')
            ->andReturn(
                (new ReCaptchaResponse(['success' => true, 'score' => 0.7, 'hostname' => 'foo']))
                    ->setVersion(Captchavel::SCORE)
                    ->setAsResolved()
            );

        $this->post('v3/default', [Captchavel::INPUT => 'token'])
            ->assertRedirect('/');

        $this->postJson('v3/default', [Captchavel::INPUT => 'token'])
            ->assertJsonValidationErrors('hostname');
    }

    public function test_no_error_if_no_apk_issued()
    {
        config(['captchavel.apk_package_name' => null]);

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('getChallenge')
            ->with('token', '127.0.0.1', 'score')
            ->andReturn(
                (new ReCaptchaResponse(['success' => true, 'score' => 0.7, 'apk_package_name' => 'foo']))
                    ->setVersion(Captchavel::SCORE)
                    ->setAsResolved()
            );

        $this->post('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk();

        $this->postJson('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk();
    }

    public function test_no_error_if_apk_same()
    {
        config(['captchavel.apk_package_name' => 'foo']);

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('getChallenge')
            ->with('token', '127.0.0.1', 'score')
            ->andReturn(
                (new ReCaptchaResponse(['success' => true, 'score' => 0.7, 'apk_package_name' => 'foo']))
                    ->setVersion(Captchavel::SCORE)
                    ->setAsResolved()
            );

        $this->post('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk();

        $this->postJson('v3/default', [Captchavel::INPUT => 'token'])
            ->assertOk();
    }

    public function test_exception_if_apk_not_equal()
    {
        config(['captchavel.apk_package_name' => 'bar']);

        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('getChallenge')
            ->with('token', '127.0.0.1', 'score')
            ->andReturn(
                (new ReCaptchaResponse(['success' => true, 'score' => 0.7, 'apk_package_name' => null]))
                    ->setVersion(Captchavel::SCORE)
                    ->setAsResolved()
            );

        $this->post('v3/default', [Captchavel::INPUT => 'token'])
            ->assertRedirect('/');

        $this->postJson('v3/default', [Captchavel::INPUT => 'token'])
            ->assertJsonValidationErrors('apk_package_name');
    }

    public function test_no_error_if_no_action()
    {
        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('getChallenge')
            ->with('token', '127.0.0.1', 'score')
            ->andReturn(
                (new ReCaptchaResponse(['success' => true, 'action' => 'foo', 'apk_package_name' => null]))
                    ->setVersion(Captchavel::SCORE)
                    ->setAsResolved()
            );

        $this->app['router']->post('test', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.score:null,null');

        $this->post('test', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_no_error_if_action_same()
    {
        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('getChallenge')
            ->with('token', '127.0.0.1', 'score')
            ->andReturn(
                (new ReCaptchaResponse(['success' => true, 'action' => 'foo', 'apk_package_name' => null]))
                    ->setVersion(Captchavel::SCORE)
                    ->setAsResolved()
            );

        $this->app['router']->post('test', function (ReCaptchaResponse $response) {
            return $response;
        })->middleware('recaptcha.score:null,foo');

        $this->post('test', [Captchavel::INPUT => 'token'])->assertOk();
    }

    public function test_exception_if_action_not_equal()
    {
        $mock = $this->mock(Captchavel::class);

        $mock->shouldReceive('getChallenge')
            ->with('token', '127.0.0.1', 'score')
            ->andReturn(
                (new ReCaptchaResponse(['success' => true, 'action' => 'foo', 'apk_package_name' => null]))
                    ->setVersion(Captchavel::SCORE)
                    ->setAsResolved()
            );

        $this->app['router']->post(
            'test',
            function (ReCaptchaResponse $response) {
                return $response;
            }
        )->middleware('recaptcha.score:null,bar');

        $this->post('test', [Captchavel::INPUT => 'token'])->assertRedirect('/');
        $this->postJson('test', [Captchavel::INPUT => 'token'])->assertJsonValidationErrors('action');
    }

    public function test_checks_for_human_score()
    {
        config(['captchavel.credentials.score.secret' => 'secret']);
        config(['captchavel.fake' => false]);

        $mock = $this->mock(Factory::class);

        $mock->shouldReceive('asForm')->withNoArgs()->times(4)->andReturnSelf();
        $mock->shouldReceive('withOptions')->with(['version' => 2.0])->times(4)->andReturnSelf();
        $mock->shouldReceive('post')
            ->with(
                Captchavel::RECAPTCHA_ENDPOINT,
                [
                    'secret' => 'secret',
                    'response' => 'token',
                    'remoteip' => '127.0.0.1',
                ]
            )
            ->times(4)
            ->andReturn(
                new Response(
                    new GuzzleResponse(
                        200, ['Content-type' => 'application/json'], json_encode(
                        [
                            'success' => true,
                            'score' => 0.5,
                        ]
                    )
                    )
                )
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
