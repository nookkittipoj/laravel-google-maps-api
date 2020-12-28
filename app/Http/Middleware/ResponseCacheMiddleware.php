<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ResponseCacheMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function handle(Request $request, Closure $next, $tag)
    {
        $key = explode('/', $request->url());
        $key = end($key);

        if (Cache::store('file')->has($key)) {
            $cache = Cache::store('file')->get($key);
            $rawImageString = base64_decode($cache);
            return response($rawImageString)->header('Content-Type', 'image/png');
        } else {
            return $next($request);
        }
    }
}
