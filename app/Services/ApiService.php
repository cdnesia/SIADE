<?php

namespace App\Services;

use Closure;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiService
{
    private const TOKEN_CACHE_KEY = 'api_service.access_token';
    private const REFRESH_TOKEN_CACHE_KEY = 'api_service.refresh_token';

    private string $baseUrl;
    private ?string $clientId;
    private ?string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.api.base_url'), '/');
        $this->clientId = config('services.api.client_id');
        $this->clientSecret = config('services.api.client_secret');
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->getToken())
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->timeout(30)
            ->retry(2, 500, throw: false);
    }

    public function get(string $endpoint): array
    {
        return $this->handle(fn() => $this->client()->get($endpoint), $endpoint);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->handle(fn() => $this->client()->post($endpoint, $data), $endpoint);
    }

    public function postFile(string $endpoint, array $data = []): Response
    {
        $response = $this->client()->post($endpoint, $data);

        if ($response->status() === 401) {
            Cache::forget(self::TOKEN_CACHE_KEY);
            $response = $this->client()->post($endpoint, $data);
        }

        return $response;
    }

    private function getToken(): ?string
    {
        return Cache::get(self::TOKEN_CACHE_KEY) ?? $this->requestToken();
    }

    private function requestToken(): ?string
    {
        $refreshToken = Cache::get(self::REFRESH_TOKEN_CACHE_KEY);

        if ($refreshToken) {
            $token = $this->authenticate('oauth/token', [
                'grant_type' => 'refresh_tokenm',
                'refresh_token' => $refreshToken,
            ]);

            if ($token !== null) {
                return $token;
            }
            Cache::forget(self::REFRESH_TOKEN_CACHE_KEY);
        }

        return $this->authenticate('oauth/token', [
            "grant_type"=> "client_credentials",
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);
    }

    private function authenticate(string $endpoint, array $data): ?string
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout(30)
                ->post($endpoint, $data);

            if ($response->successful()) {
                $responseData = $response->json();

                Cache::put(self::TOKEN_CACHE_KEY, $responseData['access_token'], max(($responseData['expires_in'] ?? 3600) - 10, 5));
                Cache::put(self::REFRESH_TOKEN_CACHE_KEY, $responseData['refresh_token'], $responseData['refresh_token_expires_in'] ?? 2592000);

                return $responseData['access_token'];
            }

            Log::error('ApiService: Gagal mendapatkan token.', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (Exception $e) {
            Log::error('ApiService: Exception saat request token.', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function handle(Closure $request, string $endpoint): array
    {
        try {
            /** @var Response $response */
            $response = $request();

            if ($response->status() === 401) {
                Cache::forget(self::TOKEN_CACHE_KEY);
                $response = $request();
            }

            if (!$response->successful()) {
                Log::error('ApiService: HTTP request gagal.', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'error_code' => $response->status(),
                    'error_desc' => 'HTTP error: ' . $response->status(),
                    'data' => null,
                ];
            }

            return [
                'error_code' => 0,
                'error_desc' => '',
                'data' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error('ApiService: Exception pada request.', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return [
                'error_code' => 1,
                'error_desc' => 'Request error: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }
}
