<?php

namespace DarkGhostHunter\Captchavel\Http;

use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

use function array_filter;
use function back;
use function config;
use function implode;
use function trans;

/**
 * @internal
 */
trait ValidatesResponse
{
    /**
     * Validates the response based on previously set expectations.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @return void
     */
    public function validate(): void
    {
        // If the "success" key is not explicitly true, bail out.
        if (Arr::get($this->attributes, 'success') !== true) {
            throw $this->validationException([
                $this->input => trans('captchavel::validation.error', [
                    'errors' => implode(', ', Arr::wrap($this->attributes['errors'] ?? []))
                ])
            ]);
        }

        foreach ($this->expectations() as $key => $value) {
            $expectation = $this->attributes[$key] ?? null;

            if ($expectation !== '' && $expectation !== $value) {
                $errors[$key] = trans('captchavel::validation.match');
            }
        }

        if (!empty($errors)) {
            throw $this->validationException([$this->input => $errors]);
        }
    }

    /**
     * Creates a new validation exceptions with messages.
     *
     * @param  array  $messages
     * @return \Illuminate\Validation\ValidationException
     */
    protected function validationException(array $messages): ValidationException
    {
        return ValidationException::withMessages($messages)->redirectTo(back()->getTargetUrl());
    }

    /**
     * Retrieve the expectations for the current response.
     *
     * @return array
     * @internal
     */
    protected function expectations(): array
    {
        return array_filter(
            Arr::only(config('captchavel'), ['hostname', 'apk_package_name']) +
            ['action' => $this->expectedAction]
        );
    }
}
