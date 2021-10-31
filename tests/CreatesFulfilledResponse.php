<?php

namespace Tests;

use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\Response;

use function json_encode;

use const JSON_THROW_ON_ERROR;

trait CreatesFulfilledResponse
{
    protected function fulfilledResponse(
        array $properties = ['success' => true],
        string $input = Captchavel::INPUT,
        string $action = null,
    ): ReCaptchaResponse
    {
        return new ReCaptchaResponse(
            $this->fulfilledPromise($properties),
            $input,
            $action,
        );
    }

    protected function fulfilledPromise(array $properties = ['success' => true]): FulfilledPromise
    {
        return new FulfilledPromise(
            new Response(
                new GuzzleResponse(
                    200, ['Content-type' => 'application/json'], json_encode($properties, JSON_THROW_ON_ERROR)
                )
            )
        );
    }
}
