<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use function auth;
use function back;
use function trans;

/**
 * @internal
 */
trait VerificationHelpers
{
    /**
     * Checks if the user is not authenticated on the given guards.
     *
     * @param  array  $guards
     * @return bool
     */
    protected function isGuest(array $guards): bool
    {
        $auth = auth();

        if ($guard === ['null']) {
            $guard = [null];
        }

        foreach ($guards as $guard) {
            if ($auth->guard($guard)->check()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the user is authenticated on the given guards.
     *
     * @param  array  $guards
     * @return bool
     */
    protected function isAuth(array $guards): bool
    {
        return ! $this->isGuest($guards);
    }

    /**
     * Validate if this Request has the reCAPTCHA challenge string.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $input
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function ensureChallengeIsPresent(Request $request, string $input): void
    {
        if ($request->isNotFilled($input)) {
            throw ValidationException::withMessages([
                $input => trans('captchavel::validation.missing')
            ])->redirectTo(back()->getTargetUrl());
        }
    }
}
