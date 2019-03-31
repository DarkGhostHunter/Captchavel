<?php

namespace Captchavel\Http\Middleware;

use Closure;

class TransparentMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     * @throws \Throwable
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }
}