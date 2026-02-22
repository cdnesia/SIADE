<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;

class NeofeederService
{
    private $api_url;
    private $username;
    private $password;

    public function __construct()
    {
        $this->username = config('services.neofeeder.username');
        $this->password = config('services.neofeeder.password');
        $this->api_url  = config('services.neofeeder.url');
    }

    /**
     * Get authentication token
     *
     * @return string|null
     */
    private function getToken()
    {
        try {
            /** @var Response $response */
            $response = Http::asJson()
                ->timeout(30)
                ->post($this->api_url, [
                    'act' => 'GetToken',
                    'username' => $this->username,
                    'password' => $this->password,
                ]);

            if ($response->successful()) {
                $body = $response->json();

                if (isset($body['error_code']) && $body['error_code'] === 0) {
                    return $body['data']['token'] ?? null;
                }

                Log::error('NeofeederService: Gagal mendapatkan token.', ['response' => $body]);
            } else {
                Log::error('NeofeederService: HTTP request gagal.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('NeofeederService: Exception saat request token.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return null;
    }

    /**
     * Get data from Neofeeder API
     *
     * @param array $data
     * @return array
     */
    public function getData(array $data)
    {
        $token = $this->getToken();

        if (!$token) {
            return [
                'error_code' => 1,
                'error_desc' => 'Gagal mendapatkan token dari Neofeeder.',
                'data' => null
            ];
        }

        $postData = array_merge(['token' => $token], $data);

        try {
            /** @var Response $response */
            $response = Http::asJson()
                ->timeout(30)
                ->post($this->api_url, $postData);

            if (!$response->successful()) {
                return [
                    'error_code' => 3,
                    'error_desc' => "HTTP error code: " . $response->status(),
                    'data' => null
                ];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('NeofeederService: Exception pada getData', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'error_code' => 2,
                'error_desc' => 'HTTP request error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get program studi data
     *
     * @param string|null $nama_prodi
     * @return array|null
     */
    public function getProdi($nama_prodi = null)
    {
        $token = $this->getToken();

        if (!$token) {
            return null;
        }

        $filter = "";

        if ($nama_prodi) {
            $parts = explode(' ', $nama_prodi, 2);

            if (count($parts) === 2) {
                $jenjang = $parts[0];
                $prodi   = $parts[1];
                $filter = "nama_program_studi='$prodi' AND nama_jenjang_pendidikan='$jenjang'";
            }
        }

        $data = [
            "act" => "GetProdi",
            "filter" => $filter,
            "order" => "",
            "limit" => 0,
            "offset" => 0
        ];

        $postData = array_merge(['token' => $token], $data);

        try {
            /** @var Response $response */
            $response = Http::asJson()
                ->timeout(30)
                ->post($this->api_url, $postData);

            if (!$response->successful()) {
                Log::error('NeofeederService: Gagal get prodi', [
                    'status' => $response->status()
                ]);
                return null;
            }

            $responseBody = $response->json();

            if ($nama_prodi) {
                return $responseBody['data'][0] ?? null;
            }

            return $responseBody['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('NeofeederService: Exception pada getProdi', [
                'message' => $e->getMessage(),
                'nama_prodi' => $nama_prodi
            ]);
            return null;
        }
    }

    /**
     * CRUD operations
     *
     * @param array $data
     * @return array
     */
    public function crud(array $data)
    {
        $token = $this->getToken();

        if (!$token) {
            return [
                'error_code' => 1,
                'error_desc' => 'Gagal mendapatkan token dari Neofeeder.',
                'data' => null
            ];
        }

        $postData = array_merge(['token' => $token], $data);

        try {
            /** @var Response $response */
            $response = Http::asJson()
                ->timeout(30)
                ->retry(2, 500)
                ->post($this->api_url, $postData);

            if (!$response->successful()) {
                return [
                    'error_code' => 3,
                    'error_desc' => "HTTP error code: " . $response->status(),
                    'data' => null
                ];
            }

            $responseBody = $response->json();

            if (isset($responseBody['error_code']) && $responseBody['error_code'] !== 0) {
                return [
                    'error_code' => 4,
                    'error_desc' => $responseBody['error_desc'] ?? 'Terjadi kesalahan dari API.',
                    'data' => null
                ];
            }

            return [
                'error_code' => 0,
                'error_desc' => '',
                'jumlah' => count($responseBody['data'] ?? []),
                'data' => $responseBody['data'] ?? null
            ];
        } catch (\Exception $e) {
            Log::error('NeofeederService: Exception pada crud', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'error_code' => 2,
                'error_desc' => 'HTTP request error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get kurikulum data
     *
     * @param string|null $prodi
     * @param int|null $semester
     * @return array
     */
    public function getKurikulum($prodi = null, $semester = null)
    {
        if (!$prodi || !$semester) {
            return [];
        }

        $kurikulumMap = $this->getKurikulumMap();
        $tahunKurikulum = $kurikulumMap[$prodi][$semester] ?? null;

        if (!$tahunKurikulum) {
            return [];
        }

        $token = $this->getToken();

        if (!$token) {
            return [];
        }

        $data = [
            "act" => "GetMatkulKurikulum",
            "filter" => "id_semester='$tahunKurikulum' AND semester='$semester' AND nama_program_studi='$prodi'",
            "order" => "",
            "limit" => 0,
            "offset" => 0
        ];

        $postData = array_merge(['token' => $token], $data);

        try {
            /** @var Response $response */
            $response = Http::asJson()
                ->timeout(30)
                ->post($this->api_url, $postData);

            if (!$response->successful()) {
                Log::error('NeofeederService: Gagal get kurikulum', [
                    'status' => $response->status(),
                    'prodi' => $prodi,
                    'semester' => $semester
                ]);
                return [];
            }

            $responseBody = $response->json();
            return $responseBody['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('NeofeederService: Exception pada getKurikulum', [
                'message' => $e->getMessage(),
                'prodi' => $prodi,
                'semester' => $semester
            ]);
            return [];
        }
    }

    /**
     * Get kurikulum mapping
     *
     * @return array
     */
    private function getKurikulumMap()
    {
        return [
            'S1 Ekonomi Pembangunan' => [
                1 => '20231',
                2 => '20231',
                3 => '20231',
                4 => '20231',
                5 => '20231',
                6 => '20231',
                7 => '20221',
                8 => '20221',
            ],
            'S1 Manajemen' => [
                1 => '20251',
                2 => '20251',
                3 => '20221',
                4 => '20221',
                5 => '20221',
                6 => '20221',
                7 => '20221',
                8 => '20221',
            ],
            'S1 Hukum Bisnis' => [
                1 => '20251',
                2 => '20251',
                3 => '20251',
                4 => '20251',
                5 => '20251',
                6 => '20251',
                7 => '20251',
                8 => '20251',
            ],
            'S1 Informatika' => [
                1 => '20251',
                2 => '20251',
                3 => '20191',
                4 => '20191',
                5 => '20191',
                6 => '20191',
                7 => '20191',
                8 => '20191',
            ],
            'S1 Sistem Informasi' => [
                1 => '20251',
                2 => '20251',
                3 => '20191',
                4 => '20191',
                5 => '20191',
                6 => '20191',
                7 => '20191',
                8 => '20191',
            ],
            'S1 Kehutanan' => [
                1 => '20251',
                2 => '20251',
                3 => '20191',
                4 => '20191',
                5 => '20191',
                6 => '20191',
                7 => '20191',
                8 => '20191',
            ],
            'S1 Perencanaan Wilayah dan Kota' => [
                1 => '20241',
                2 => '20241',
                3 => '20241',
                4 => '20241',
                5 => '20241',
                6 => '20241',
                7 => '20241',
                8 => '20241',
            ],
            'D4 Keperawatan Anestesiologi' => [
                1 => '20251',
                2 => '20251',
                3 => '20251',
                4 => '20251',
                5 => '20251',
                6 => '20251',
                7 => '20251',
                8 => '20251',
            ],
            'S2 Ekonomi Pembangunan' => [
                1 => '20231',
                2 => '20231',
                3 => '20231',
                4 => '20231',
                5 => '20231',
                6 => '20231',
                7 => '20231',
                8 => '20231',
            ],
        ];
    }
}
