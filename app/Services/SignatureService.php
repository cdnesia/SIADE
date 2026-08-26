<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SignatureService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $privateKey;

    public function __construct()
    {
        $config = config('services.api');

        $this->baseUrl      = rtrim($config['base_url'], '/');
        $this->clientId     = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->privateKey   = $config['private_key'];
    }

    /**
     * Request Access Token
     */
    public function accessToken(): array
    {
        $timestamp = $this->isoTimestamp();

        $stringToSign = $this->clientId . '|' . $timestamp;

        $key = openssl_pkey_get_private($this->privateKey);

        if (!$key) {
            throw new \Exception('Private key tidak valid.');
        }

        openssl_sign(
            $stringToSign,
            $signature,
            $key,
            OPENSSL_ALGO_SHA256
        );

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-TIMESTAMP'  => $timestamp,
            'X-CLIENT-KEY' => $this->clientId,
            'X-SIGNATURE'  => base64_encode($signature),
        ])->post(
            $this->baseUrl . '/api/oauth/token',
            ['grantType' => 'client_credentials']
        );

        if (!$response->successful() || !($response->json('success'))) {
            throw new \Exception($response->body());
        }

        return $response->json();
    }

    /**
     * Generate Symmetric Signature
     */
    public function symmetricSignature(
        string $method,
        string $endpoint,
        array $body = []
    ): array {

        $token = $this->accessToken();

        $accessToken = $token['data']['access_token'];

        $timestamp = $this->isoTimestamp();

        $json = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $hashBody = strtolower(hash('sha256', $json));

        $stringToSign =
            strtoupper($method)
            . ':' . $endpoint
            . ':' . $accessToken
            . ':' . $hashBody
            . ':' . $timestamp;

        $signature = base64_encode(hash_hmac(
            'sha512',
            $stringToSign,
            $this->clientSecret,
            true
        ));

        return [
            'authorization' => 'Bearer ' . $accessToken,
            'access_token'  => $accessToken,
            'timestamp'     => $timestamp,
            'signature'     => $signature,
        ];
    }

    private function isoTimestamp(): string
    {
        return (new \DateTime('now', new \DateTimeZone('+07:00')))->format('Y-m-d\TH:i:s.v P');
    }
}
