<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ApiService
{
    protected SignatureService $signature;
    protected string $baseUrl;

    public function __construct(SignatureService $signature)
    {
        $this->signature = $signature;
        $this->baseUrl = config('services.api.base_url');
    }

    public function post(string $endpoint, array $body = [])
    {
        $auth = $this->signature->symmetricSignature(
            'POST',
            $endpoint,
            $body
        );

        return Http::withHeaders([
            'Authorization' => $auth['authorization'],
            'Content-Type'  => 'application/json',
            'X-TIMESTAMP'   => $auth['timestamp'],
            'X-SIGNATURE'   => $auth['signature'],
        ])->post(
            $this->baseUrl . $endpoint,
            $body
        )->json();
    }

    public function get(string $endpoint)
    {
        $auth = $this->signature->symmetricSignature(
            'GET',
            $endpoint
        );

        return Http::withHeaders([
            'Authorization' => $auth['authorization'],
            'X-TIMESTAMP'   => $auth['timestamp'],
            'X-SIGNATURE'   => $auth['signature'],
        ])->get(
            $this->baseUrl . $endpoint
        )->json();
    }

    public function put(string $endpoint, array $body = [])
    {
        $auth = $this->signature->symmetricSignature(
            'PUT',
            $endpoint,
            $body
        );

        return Http::withHeaders([
            'Authorization' => $auth['authorization'],
            'Content-Type'  => 'application/json',
            'X-TIMESTAMP'   => $auth['timestamp'],
            'X-SIGNATURE'   => $auth['signature'],
        ])->put(
            $this->baseUrl . $endpoint,
            $body
        )->json();
    }

    public function delete(string $endpoint)
    {
        $auth = $this->signature->symmetricSignature(
            'DELETE',
            $endpoint
        );

        return Http::withHeaders([
            'Authorization' => $auth['authorization'],
            'X-TIMESTAMP'   => $auth['timestamp'],
            'X-SIGNATURE'   => $auth['signature'],
        ])->delete(
            $this->baseUrl . $endpoint
        )->json();
    }
}
