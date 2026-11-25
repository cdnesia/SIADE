<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Memindahkan pembayaran daftar ulang yang dilakukan saat masih memakai nomor pendaftaran PMB
 * (contoh UMJA202640183) ke tagihan NIM (contoh S12654001) lewat REST-API tagihan
 * (POST /v1/keuangan/tagihan/cari & PATCH /v1/keuangan/tagihan/{id_record_tagihan}).
 *
 * Alur per NIM:
 * 1. Cari nomor pendaftaran di penmaru_old.pmb berdasarkan NIM.
 * 2. Tagihan sumber = tagihan nomor pendaftaran yang memuat bipot SPP (tagihan daftar ulang,
 *    sama dengan acuan Generate NPM), bukan tagihan uang pendaftaran.
 * 3. Tagihan tujuan = tagihan SPP NIM paling awal (semester pertama).
 * 4. Rincian tagihan dicocokkan; jika berbeda, rincian & potongan disalin dari tagihan sumber.
 * 5. nominal_terbayar sumber ditambahkan ke tujuan; API menghitung ulang nominal_ditagih (sisa).
 * 6. Jika sisa ditagih jadi 0 (terbayar = total tagihan - potongan), tagihan NIM dinonaktifkan (status T).
 *
 * Tagihan dianggap sudah disinkron jika nominal_terbayar NIM sudah mencapai pembayaran PMB.
 * Tagihan yang sudah disinkron & lunas tapi masih aktif bisa diproses ulang untuk dinonaktifkan saja.
 */
class SinkronPembayaranService
{
    public const BIPOT_SPP = 1;

    public const MAKS_NIM = 200;

    public function __construct(protected ApiService $api, protected MasterApiService $master) {}

    /**
     * Pratinjau sinkron untuk sejumlah NIM tanpa mengubah data.
     */
    public function periksa(array $daftarNim): Collection
    {
        $pmb = DB::connection('penmaru_old')->table('pmb')
            ->whereIn('nim', $daftarNim)
            ->get(['nomor', 'pmb', 'nim', 'nama'])
            ->keyBy('nim');

        $tagihan = $this->tagihan(array_merge($daftarNim, $pmb->pluck('pmb')->all()));

        return collect($daftarNim)->map(function ($nim) use ($pmb, $tagihan) {
            $daftar = $pmb[$nim] ?? null;
            $sumber = $daftar ? $this->pilihSumber($tagihan[$daftar->pmb] ?? collect()) : null;
            $tujuan = $this->pilihTujuan($tagihan[$nim] ?? collect());

            return $this->hitung($nim, $daftar, $sumber, $tujuan);
        });
    }

    /**
     * Jalankan sinkron satu NIM. Melempar RuntimeException berisi alasan jika tidak bisa disinkron.
     */
    public function sinkron(string $nim): array
    {
        // Diperiksa ulang dari data terbaru agar pembayaran tidak ditambahkan dua kali
        $hasil = $this->periksa([$nim])->first();
        if (! $hasil['bisa_sinkron']) {
            throw new RuntimeException($hasil['keterangan']);
        }

        $sumber = $hasil['sumber'];
        $tujuan = $hasil['tujuan'];
        $payload = [
            'nim' => $nim,
            'jenis_tagihan' => $tujuan['jenis_tagihan'],
        ];
        // Sudah disinkron sebelumnya: pembayaran tidak ditambahkan lagi, hanya status dinonaktifkan
        if ($hasil['status'] !== 'perlu_nonaktif') {
            $payload['nominal_terbayar'] = $hasil['terbayar_baru'];
        }
        if ($hasil['nonaktifkan']) {
            $payload['status_aktif'] = 'T';
        }
        if (! $hasil['cocok'] && $hasil['status'] !== 'perlu_nonaktif') {
            $payload['detail_tagihan'] = $this->keRincianApi($sumber['detail_tagihan']);
            $payload['detail_potongan'] = $this->keRincianApi($sumber['detail_potongan']);
        }

        $respon = $this->api->patch('v1/keuangan/tagihan/'.rawurlencode($tujuan['id_record_tagihan']), $payload);
        if (! ($respon['data']['success'] ?? false)) {
            throw new RuntimeException('API tagihan menolak: '.$respon['error_desc']);
        }

        return $hasil;
    }

    /**
     * Tagihan dari API, dikelompokkan per npm. Gagal memanggil API dianggap error, bukan "tidak ada tagihan".
     */
    private function tagihan(array $npm): Collection
    {
        // REST-API tidak mengembalikan tagihan terhapus, jadi tidak perlu filter deleted_at
        $respon = $this->master->cariTagihan($npm);
        if ($respon['error_code'] !== 0) {
            throw new RuntimeException('Gagal mengambil data tagihan: '.$respon['error_desc']);
        }

        return collect($respon['items'])->groupBy('nim');
    }

    /**
     * Tagihan daftar ulang PMB: memuat bipot SPP, diutamakan yang pembayarannya paling besar.
     */
    private function pilihSumber(Collection $tagihan): ?array
    {
        return $tagihan
            ->filter(fn ($t) => collect($this->rincian($t['detail_tagihan']))->has(self::BIPOT_SPP))
            ->sortBy([fn ($a, $b) => (float) $b['nominal_terbayar'] <=> (float) $a['nominal_terbayar'], ['dibuat_pada', 'desc']])
            ->first();
    }

    /**
     * Tagihan SPP NIM semester pertama (tahun akademik paling awal).
     */
    private function pilihTujuan(Collection $tagihan): ?array
    {
        return $tagihan
            ->filter(fn ($t) => strtoupper((string) $t['jenis_tagihan']) === 'SPP')
            ->sortBy([['tahun_akademik', 'asc'], ['dibuat_pada', 'asc']])
            ->first();
    }

    private function hitung(string $nim, $daftar, ?array $sumber, ?array $tujuan): array
    {
        $hasil = [
            'nim' => $nim,
            'nama' => $daftar->nama ?? ($tujuan['nama_mahasiswa'] ?? null),
            'nomor_pendaftaran' => $daftar->pmb ?? null,
            'sumber' => $sumber,
            'tujuan' => $tujuan,
            'cocok' => null,
            'nominal_dipindahkan' => $sumber ? (float) $sumber['nominal_terbayar'] : 0,
            'terbayar_baru' => null,
            'ditagih_baru' => null,
            'nonaktifkan' => false,
            'bisa_sinkron' => false,
        ];

        $sudahSinkron = false;
        $sudahLunasAktif = false;
        if ($sumber && $tujuan) {
            $hasil['cocok'] = $this->rincian($sumber['detail_tagihan']) == $this->rincian($tujuan['detail_tagihan'])
                && $this->rincian($sumber['detail_potongan']) == $this->rincian($tujuan['detail_potongan']);

            // Pembayaran PMB ditambahkan ke terbayar NIM; sisa dihitung dari rincian (ikut sumber jika tidak cocok)
            $acuan = $hasil['cocok'] ? $tujuan : $sumber;
            $hasil['terbayar_baru'] = round((float) $tujuan['nominal_terbayar'] + $hasil['nominal_dipindahkan'], 2);
            $hasil['ditagih_baru'] = max(0, round((float) $acuan['total_tagihan'] - (float) $acuan['total_potongan'] - $hasil['terbayar_baru'], 2));

            // Terbayar NIM sudah mencapai pembayaran PMB = pernah disinkron
            $sudahSinkron = $hasil['nominal_dipindahkan'] > 0
                && round((float) $tujuan['nominal_terbayar'], 2) >= round($hasil['nominal_dipindahkan'], 2);

            if ($sudahSinkron) {
                // Pembayaran tidak dipindahkan lagi, angka tetap seperti tagihan NIM saat ini
                $hasil['terbayar_baru'] = round((float) $tujuan['nominal_terbayar'], 2);
                $hasil['ditagih_baru'] = round((float) $tujuan['nominal_ditagih'], 2);
                $sisa = round((float) $tujuan['total_tagihan'] - (float) $tujuan['total_potongan'] - $hasil['terbayar_baru'], 2);
                $sudahLunasAktif = $tujuan['status_aktif'] === 'Y' && $hasil['ditagih_baru'] <= 0 && $sisa <= 0;
            }

            // Total terbayar sudah sama dengan total tagihan (sisa ditagih 0) -> tagihan NIM dinonaktifkan
            $hasil['nonaktifkan'] = $hasil['ditagih_baru'] <= 0;
        }

        [$status, $keterangan] = match (true) {
            ! $daftar => ['tidak_ditemukan', 'NIM tidak ditemukan di data PMB'],
            ! $sumber => ['tanpa_sumber', "Tagihan daftar ulang {$daftar->pmb} tidak ditemukan"],
            (float) $sumber['nominal_terbayar'] <= 0 => ['belum_bayar', "Belum ada pembayaran pada tagihan {$daftar->pmb}"],
            ! $tujuan => ['tanpa_tujuan', 'Tagihan NIM belum dibuat di SIMKEU'],
            $sudahLunasAktif => ['perlu_nonaktif', 'Sudah disinkron & lunas, tagihan NIM masih aktif. Sinkronkan untuk menonaktifkan'],
            $sudahSinkron => ['sudah', 'Sudah disinkron, pembayaran PMB sudah tercatat di tagihan NIM'],
            $tujuan['status_aktif'] !== 'Y' => ['lunas', 'Tagihan NIM sudah lunas/tidak aktif, tidak bisa diubah'],
            default => ['siap', 'Siap disinkron'],
        };
        $hasil['status'] = $status;
        $hasil['keterangan'] = $keterangan;
        $hasil['bisa_sinkron'] = in_array($status, ['siap', 'perlu_nonaktif']);

        return $hasil;
    }

    /**
     * Rincian bipot dalam bentuk [id_bipot => nominal] terurut, untuk dicocokkan antar tagihan.
     */
    private function rincian($detail): array
    {
        $rincian = collect(is_string($detail) ? json_decode($detail, true) : $detail)
            ->filter(fn ($item) => isset($item['id_bipot']))
            ->groupBy(fn ($item) => (int) $item['id_bipot'])
            ->map(fn ($items) => (int) $items->sum(fn ($item) => (float) ($item['nominal'] ?? 0)))
            ->all();
        ksort($rincian);

        return $rincian;
    }

    /**
     * Rincian tagihan/potongan ke format request REST-API (snake_case, hanya id_bipot, nama_bipot, nominal).
     */
    private function keRincianApi($detail): array
    {
        return collect(is_string($detail) ? json_decode($detail, true) : ($detail ?? []))
            ->map(fn ($item) => [
                'id_bipot' => $item['id_bipot'],
                'nama_bipot' => $item['nama_bipot'],
                'nominal' => $item['nominal'],
            ])
            ->values()
            ->all();
    }
}
