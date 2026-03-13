<?php

namespace App\Services;

use App\Models\KRS;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class DataService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    private function expandTerms(int $start, int $end): array
    {
        $y  = intdiv($start, 10);
        $s  = $start % 10;
        $ye = intdiv($end,   10);
        $se = $end   % 10;
        $out = [];
        while ($y < $ye || ($y === $ye && $s <= $se)) {
            $out[] = $y * 10 + $s;
            $s++;
            if ($s > 2) {
                $s = 1;
                $y++;
            }
        }
        return $out;
    }
    public function krs($npm = null)
    {
        $npm = Crypt::decrypt($npm);
        $krsRaw = KRS::with([
            'jadwal',
            'mataKuliahJadwal',
            'mataKuliahLangsung',
            'hari'
        ])
            ->where('npm', $npm)
            ->get();

        $krsRaw = $krsRaw->sortBy([
            fn($a, $b) => $a->kode_tahun_akademik <=> $b->kode_tahun_akademik,
            fn($b, $a) => ($a->hari->nama_hari ?? '') <=> ($b->hari->nama_hari ?? '')
        ]);

        $krs = [];
        $total_sks_kumulatif = 0;
        $total_bobot_kumulatif = 0;

        $mahasiswa = Mahasiswa::where('npm', $npm)->first();
        $tahun_angkatan = $mahasiswa->tahun_angkatan;

        $tahunAkademikDitempuh = $this->expandTerms($tahun_angkatan, 20252);

        foreach ($tahunAkademikDitempuh as $key => $value) {
            if (!isset($krs[$value])) {
                $krs[$value] = [];
                $krs[$value]['semester'] = $key + 1;
                $krs[$value]['jumlah_sks'] = 0;
                $krs[$value]['total_bobot'] = 0;
                $krs[$value]['krs'] = [];
                $krs[$value]['jumlah_sks'] = 0;
                $krs[$value]['total_bobot'] = 0;
                $krs[$value]['metadata'] = [
                    'ips' => 0,
                    'ipk' => 0,
                ];
            }
            foreach ($krsRaw as $row) {
                $ta = $row['kode_tahun_akademik'];
                if ($value !== (int) $ta) {
                    continue;
                }

                $sks = $row['matakuliah']['sks_mata_kuliah'] ?? 0;
                $bobot = $row['nilai_bobot'] ?? 0;

                $krs[$ta]['jumlah_sks'] += $sks;
                $krs[$ta]['total_bobot'] += $bobot * $sks;

                $total_sks_kumulatif += $sks;
                $total_bobot_kumulatif += $bobot * $sks;

                $krs[$ta]['krs'][] = [
                    'encrypted_id' => Crypt::encrypt($row['id']),
                    'nilai_angka' => $row['nilai_angka'] ?? '',
                    'nilai_huruf' => $row['nilai_huruf'] ?? '',
                    'nilai_bobot' => $bobot,
                    'persetujuan_pa' => $row['persetujuan_pa'] ?? '',
                    'lulus' => $row['lulus'] ?? '',
                    'edome' => $row['edome'] ?? '',
                    'kode_mata_kuliah' => $row['matakuliah']['kode_mata_kuliah'] ?? '',
                    'nama_mata_kuliah' => $row['matakuliah']['nama_mata_kuliah_idn'] ?? '',
                    'sks_matakuliah' => $sks,
                    'jam_mulai' => $row['jadwal']['jam_mulai'] ?? '',
                    'jam_selesai' => $row['jadwal']['jam_selesai'] ?? '',
                    'dosen_id' => $row['jadwal']['dosen_id'] ?? '',
                    'ruang_id' => $row['jadwal']['ruang_id'] ?? '',
                    'kelompok' => $row['jadwal']['kelompok'] ?? '',
                    'hari' => $row['hari']['nama_hari'] ?? '',
                ];

                $krs[$ta]['metadata'] = [
                    'ips' => $krs[$ta]['jumlah_sks'] ? round($krs[$ta]['total_bobot'] / $krs[$ta]['jumlah_sks'], 2) : 0,
                    'ipk' => $total_sks_kumulatif ? round($total_bobot_kumulatif / $total_sks_kumulatif, 2) : 0,
                ];
            }
        }

        return $krs;
    }
    public function bipot()
    {
        $url = config('services.simaku_url');
        $timestamp = time();
        $nonce = Str::uuid()->toString();
        $path = 'api/data-bipot';

        $body = json_encode([]);

        $data = $timestamp . $nonce . 'POST' . $path . $body;
        $signature = hash_hmac('sha256', $data, config('services.hmac_secret'));
        $response = Http::withHeaders([
            'X-API-KEY'   => config('services.hmac_api_key'),
            'X-TIMESTAMP' => $timestamp,
            'X-NONCE'     => $nonce,
            'X-SIGNATURE' => $signature,
        ])->withBody($body, 'application/json')
            ->post($url . $path);

        $responseData = $response->json();

        $data = $responseData['data'] ?? [];

        if (empty($data)) {
            return [];
        }
        return $data;
    }
    public function dataDosen()
    {
        $url = "https://api.umjambi.ac.id/";
        $timestamp = time();
        $nonce = Str::uuid()->toString();
        $path = 'api/data-dosen';

        $body = json_encode([]);

        $data = $timestamp . $nonce . 'POST' . $path . $body;
        $signature = hash_hmac('sha256', $data, config('services.hmac_secret'));
        $response = Http::withHeaders([
            'X-API-KEY'   => config('services.hmac_api_key'),
            'X-TIMESTAMP' => $timestamp,
            'X-NONCE'     => $nonce,
            'X-SIGNATURE' => $signature,
        ])->withBody($body, 'application/json')
            ->post($url . $path);

        $responseData = $response->json();
        $data = $responseData['data'] ?? [];

        if (empty($data)) {
            return [];
        }
        return $data;
    }
}
