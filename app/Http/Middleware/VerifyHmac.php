<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class VerifyHmac
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY');
        $signature = $request->header('X-SIGNATURE');
        $timestamp = $request->header('X-TIMESTAMP');
        $nonce = $request->header('X-NONCE');

        if (!$apiKey || !$signature || !$timestamp || !$nonce) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!hash_equals(config('services.hmac_api_key'), $apiKey)) {
            return response()->json(['message' => 'Invalid API Key'], 401);
        }

        if (!is_numeric($timestamp) || abs(time() - (int)$timestamp) > 120) {
            return response()->json(['message' => 'Request expired'], 401);
        }

        if (Cache::has('nonce_' . $nonce)) {
            return response()->json(['message' => 'Replay detected'], 401);
        }

        Cache::put('nonce_' . $nonce, true, 120);

        $secret = config('services.hmac_secret');

        $body = $request->getContent();
        $method = strtoupper($request->method());
        $path = $request->path();

        $data = $timestamp . $nonce . $method . $path . $body;

        $calculated = hash_hmac('sha256', $data, $secret);

        if (!hash_equals($calculated, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
