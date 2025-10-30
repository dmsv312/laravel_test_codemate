<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->isMethod('post')) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key');
        if (!$key) {
            return $next($request);
        }

        $fingerprint = hash('sha256', $request->method().'|'.$request->path().'|'.$request->getContent());
        $existing = DB::table('idempotency_keys')
            ->where('key', $key)
            ->where('request_fingerprint', $fingerprint)
            ->first();

        if ($existing) {
            return response()->json(json_decode($existing->response_json, true), (int) $existing->status_code);
        }

        $response = $next($request);

        try {
            DB::table('idempotency_keys')->insert([
                'key' => $key,
                'request_fingerprint' => $fingerprint,
                'status_code' => $response->getStatusCode(),
                'response_json' => $response->getContent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore duplicate
        }

        return $response;
    }
}
