<?php

namespace Tests;

trait RegistersPackage
{
    protected function getPackageAliases($app)
    {
        return [
            'Captchavel' => 'DarkGhostHunter\Captchavel\Facades\Captchavel'
        ];
    }

    protected function getPackageProviders($app)
    {
        return ['DarkGhostHunter\Captchavel\CaptchavelServiceProvider'];
    }

}
