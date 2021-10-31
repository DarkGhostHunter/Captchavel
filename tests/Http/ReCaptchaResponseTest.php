<?php

namespace Tests\Http;

use DarkGhostHunter\Captchavel\Captchavel;
use DarkGhostHunter\Captchavel\Http\ReCaptchaResponse;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Validation\ValidationException;
use Orchestra\Testbench\TestCase;
use Tests\CreatesFulfilledResponse;
use Tests\RegistersPackage;

use function json_encode;
use function now;
use function trans;

use const JSON_THROW_ON_ERROR;

class ReCaptchaResponseTest extends TestCase
{
    use RegistersPackage;
    use CreatesFulfilledResponse;

    public function test_can_be_json(): void
    {
        $response = $this->fulfilledResponse(['success' => true, 'foo' => 'bar']);

        static::assertEquals('{"success":true,"foo":"bar"}', $response->toJson());
    }

    public function test_can_be_array(): void
    {
        $response = $this->fulfilledResponse($array = ['success' => true, 'foo' => 'bar']);

        static::assertEquals($array, $response->toArray());
    }

    public function test_access_attributes_as_properties()
    {
        $response = $this->fulfilledResponse(['success' => true, 'foo' => 'bar']);

        static::assertTrue(isset($response->foo));
        static::assertEquals('bar', $response->foo);

        unset($response->foo);

        static::assertFalse(isset($response->foo));
        static::assertNull($response->foo);

        $response->foo = 'bar';

        static::assertEquals('bar', $response->foo);
    }

    public function test_checks_resolve(): void
    {
        $response = new ReCaptchaResponse(
            $promise = new Promise(),
            Captchavel::INPUT,
        );

        static::assertFalse($response->isResolved());
        static::assertTrue($response->isPending());

        $promise->resolve(
            new Response(
                new GuzzleResponse(200, ['Content-type' => 'application/json'], json_encode([
                    'success' => true,
                    'foo'     => 'bar',
                ], JSON_THROW_ON_ERROR))
            )
        );

        $response->wait();

        static::assertTrue($response->isResolved());
        static::assertFalse($response->isPending());
    }

    public function test_always_returns_human_if_not_score_response(): void
    {
        $response = $this->fulfilledResponse([
            'success' => true,
            'foo'     => 'bar',
        ]);

        static::assertTrue($response->isHuman());
        static::assertFalse($response->isRobot());
    }

    public function test_returns_carbon_of_challenge_ts(): void
    {
        $response = $this->fulfilledResponse([
            'success'      => true,
            'foo'          => 'bar',
            'challenge_ts' => ($now = now())->toIso8601ZuluString(),
        ]);

        static::assertEquals($now->startOfSecond(), $response->carbon());
    }

    public function test_attributes_always_empty_until_resolved(): void
    {
        $response = new ReCaptchaResponse(
            $promise = new Promise(),
            Captchavel::INPUT,
        );

        static::assertEmpty($response->getAttributes());

        $promise->resolve(
            new Response(
                new GuzzleResponse(200, ['Content-type' => 'application/json'], json_encode([
                    'success' => true,
                    'foo'     => 'bar',
                ], JSON_THROW_ON_ERROR))
            )
        );

        $response->wait();

        static::assertNotEmpty($response->getAttributes());
    }

    public function test_validation_fails_if_no_success(): void
    {
        $this->expectException(ValidationException::class);

        $response = $this->fulfilledResponse([
            'success' => false,
            'foo'     => 'bar',
            'errors'  => ['quz', 'cougar'],
        ]);

        try {
            $response->wait();
        } catch (ValidationException $exception) {
            static::assertArrayHasKey(Captchavel::INPUT, $exception->errors());
            static::assertEquals($exception->errors()[Captchavel::INPUT], [
                trans('captchavel::validation.error', ['errors' => 'quz, cougar']),
            ]);

            throw $exception;
        }
    }

    public function test_validation_fails_if_success_absent(): void
    {
        $this->expectException(ValidationException::class);

        $response = $this->fulfilledResponse([
            'foo' => 'bar',
        ]);

        try {
            $response->wait();
        } catch (ValidationException $exception) {
            static::assertArrayHasKey(Captchavel::INPUT, $exception->errors());
            static::assertEquals($exception->errors()[Captchavel::INPUT], [
                trans('captchavel::validation.error', ['errors' => '']),
            ]);

            throw $exception;
        }
    }
}
