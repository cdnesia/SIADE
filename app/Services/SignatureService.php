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

        $this->baseUrl      = $config['base_url'];
        $this->clientId     = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->privateKey   = $config['private_key'];
    }

    /**
     * Request Access Token
     */
    public function accessToken(): array
    {
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');

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

        openssl_free_key($key);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-CLIENT-ID'  => $this->clientId,
            'X-TIMESTAMP'  => $timestamp,
            'X-SIGNATURE'  => base64_encode($signature),
        ])->post(
            $this->baseUrl . '/api/v1/auth/access-token',
            []
        );

        if (!$response->successful()) {
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

        $accessToken = str_replace(
            'Bearer ',
            '',
            $token['responseData']['accessToken']
        );

        $timestamp = gmdate('Y-m-d\TH:i:s\Z');

        $json = empty($body)
            ? ''
            : json_encode($body, JSON_UNESCAPED_SLASHES);

        $hashBody = strtolower(hash('sha256', $json));

        $stringToSign =
            strtoupper($method)
            . '|' . $endpoint
            . '|' . $accessToken
            . '|' . $hashBody
            . '|' . $timestamp;

        $signature = strtolower(hash_hmac(
            'sha512',
            $stringToSign,
            $this->clientSecret
        ));

        return [
            'authorization' => 'Bearer ' . $accessToken,
            'access_token'  => $accessToken,
            'timestamp'     => $timestamp,
            'signature'     => $signature,
        ];
    }
}
