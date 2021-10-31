<?php

namespace DarkGhostHunter\Captchavel\Http\Middleware;

use DarkGhostHunter\Captchavel\Captchavel;

use function strtolower;

/**
 * @internal
 */
trait NormalizeInput
{
    /**
     * Normalize the input name.
     *
     * @param  string  $input
     * @return string
     */
    protected function normalizeInput(string $input): string
    {
        return strtolower($input) === 'null' ? Captchavel::INPUT : $input;
    }
}
