<?php

namespace SaamMi\AnyChat\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class CustomCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if ($request->hasCookie('ccookie')) {
            return $next($request);
        } else {
            $uuid = str()->random();
            Cookie::queue(Cookie::forever('ccookie', $uuid));
        }

        return $next($request);
    }
}
