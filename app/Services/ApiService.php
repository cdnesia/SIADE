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

    private string $baseUrl;

    private ?string $clientId;

    private ?string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.api.base_url'), '/');
        $this->clientId = config('services.api.client_id');
        $this->clientSecret = config('services.api.client_secret');
    }

    /**
     * HTTP client dengan Bearer Token.
     */
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

    /**
     * GET request.
     */
    public function get(string $endpoint): array
    {
        return $this->handle(
            fn () => $this->client()->get($endpoint),
            $endpoint
        );
    }

    /**
     * POST request.
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->handle(
            fn () => $this->client()->post($endpoint, $data),
            $endpoint
        );
    }

    /**
     * PATCH request.
     */
    public function patch(string $endpoint, array $data = []): array
    {
        return $this->handle(
            fn () => $this->client()->patch($endpoint, $data),
            $endpoint
        );
    }

    /**
     * GET file (mis. PDF) — Response mentah dikembalikan apa adanya.
     */
    public function getFile(string $endpoint, array $query = []): Response
    {
        $response = $this->client()->get($endpoint, $query);

        if ($response->status() === 401) {
            Cache::forget(self::TOKEN_CACHE_KEY);

            $response = $this->client()->get($endpoint, $query);
        }

        return $response;
    }

    /**
     * Ambil token dari cache.
     *
     * Jika belum ada, request token baru.
     */
    private function getToken(): ?string
    {
        return Cache::get(self::TOKEN_CACHE_KEY)
            ?? $this->requestToken();
    }

    /**
     * Request token REST-API (POST /v1/auth/token).
     *
     * Catatan: satu client = satu token aktif, meminta token baru
     * membatalkan token lama — karena itu token wajib di-cache.
     */
    private function requestToken(): ?string
    {
        return $this->authenticate('v1/auth/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);
    }

    /**
     * Authenticate ke API.
     */
    private function authenticate(string $endpoint, array $data): ?string
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->timeout(30)
                ->post($endpoint, $data);

            if (! $response->successful()) {
                Log::error('ApiService: Gagal mendapatkan token.', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            /*
             * Format REST-API: { success, data: { access_token, token_type, expires_in, expires_at }, request_id }
             */
            $responseData = $response->json('data') ?? [];

            if (empty($responseData['access_token'])) {
                Log::error('ApiService: access_token tidak ditemukan.', [
                    'endpoint' => $endpoint,
                    'response' => $responseData,
                ]);

                return null;
            }

            /*
             * expires_in dari REST-API berupa detik (mis. 3600).
             * Format lama "15m" / "1h" tetap didukung parseExpiresIn().
             */
            $expiresIn = $this->parseExpiresIn(
                $responseData['expires_in'] ?? '15m'
            );

            /*
             * Simpan sedikit lebih pendek dari expiry token
             * agar aplikasi tidak menggunakan token yang
             * sudah hampir expired.
             */
            $cacheSeconds = max($expiresIn - 30, 5);

            Cache::put(
                self::TOKEN_CACHE_KEY,
                $responseData['access_token'],
                $cacheSeconds
            );

            return $responseData['access_token'];

        } catch (Exception $e) {
            Log::error('ApiService: Exception saat request token.', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Konversi expires_in dari API menjadi detik.
     *
     * Contoh:
     * 15m  = 900
     * 1h   = 3600
     * 30s  = 30
     * 900  = 900
     */
    private function parseExpiresIn(string|int $expiresIn): int
    {
        if (is_numeric($expiresIn)) {
            return (int) $expiresIn;
        }

        $expiresIn = strtolower(trim($expiresIn));

        if (preg_match('/^(\d+)\s*s$/', $expiresIn, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/^(\d+)\s*m$/', $expiresIn, $matches)) {
            return (int) $matches[1] * 60;
        }

        if (preg_match('/^(\d+)\s*h$/', $expiresIn, $matches)) {
            return (int) $matches[1] * 3600;
        }

        if (preg_match('/^(\d+)\s*d$/', $expiresIn, $matches)) {
            return (int) $matches[1] * 86400;
        }

        /*
         * Default jika format tidak dikenal.
         */
        return 900;
    }

    /**
     * Handle response API.
     *
     * Format REST-API:
     *   sukses : { success: true, data: {...}, request_id }
     *   gagal  : { success: false, error: { code, message, details? }, request_id }
     *
     * Kembalian: ['error_code', 'error_desc', 'data' => body JSON utuh].
     */
    private function handle(Closure $request, string $endpoint): array
    {
        try {
            /** @var Response $response */
            $response = $request();

            /*
             * Jika token expired / invalid,
             * hapus cache lalu request ulang dengan token baru.
             */
            if ($response->status() === 401) {
                Cache::forget(self::TOKEN_CACHE_KEY);

                $response = $request();
            }

            if (! $response->successful()) {
                Log::error('ApiService: HTTP request gagal.', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'error_code' => $response->status(),
                    'error_desc' => self::pesanError($response),
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
                'error_desc' => 'Request error: '.$e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Ambil pesan error dari respons REST-API (error.message), fallback ke format lama.
     */
    public static function pesanError(Response $response): string
    {
        return $response->json('error.message')
            ?? $response->json('message')
            ?? 'HTTP error: '.$response->status();
    }
}
