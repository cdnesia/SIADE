<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        if (!$apiKey || !$signature || !$timestamp) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (abs(time() - $timestamp) > 120) {
            return response()->json(['message' => 'Request expired'], 401);
        }

        $secret = env('HMAC_SECRET');

        $body = $request->getContent();
        $data = $timestamp . $request->method() . $request->path() . $body;

        $calculated = hash_hmac('sha256', $data, $secret);

        if (!hash_equals($calculated, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
