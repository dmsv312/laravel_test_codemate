<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-Api-Key');
        $expected = config('app.api_key', env('APP_API_KEY', null));
        if (!$expected || $key !== $expected) {
            return response()->json(['error' => ['code' => 'unauthorized', 'message' => 'Invalid API key']], 401);
        }
        return $next($request);
    }
}
