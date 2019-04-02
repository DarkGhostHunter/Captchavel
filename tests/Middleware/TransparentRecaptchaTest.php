<?php

namespace DarkGhostHunter\Captchavel\Tests;

use Orchestra\Testbench\TestCase;

class TransparentRecaptchaTest extends TestCase
{

    protected function getPackageAliases($app)
    {
        return [
            'ReCaptcha' => 'DarkGhostHunter\Captchavel\Facades\ReCaptcha'
        ];
    }

    protected function getPackageProviders($app)
    {
        return ['DarkGhostHunter\Captchavel\CaptchavelServiceProvider'];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app->make('config')->set('captchavel.secret', 'test-secret');
        $app->make('config')->set('captchavel.key', 'test-key');

        $app->make('router')->post('test-post', function () {
            return 'true';
        })->middleware('recaptcha');
    }

    public function testTransparentMiddlewareReturnsHuman()
    {
        $this->post('test-post', [
            '_recaptcha' => null
        ])->assertSeeText('true');

        $this->assertTrue(recaptcha()->isHuman());
    }

    public function testTransparentMiddlewareReturnsRobotOnQuery()
    {
        $this->post('test-post?is_robot=null', [
            '_recaptcha' => null
        ])->assertSeeText('true');

        $this->assertFalse(recaptcha()->isRobot());
    }

    public function testTransparentMiddlewareReturnsRobotOnInput()
    {
        $this->post('test-post?is_robot=null', [
            '_recaptcha' => null,
            'is_robot' => 'true'
        ])->assertSeeText('true');

        $this->assertFalse(recaptcha()->isRobot());
    }

}
