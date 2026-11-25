<?php

namespace App\Services;

use App\Models\KRS;
use App\Models\Mahasiswa;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Pemanggil REST-API (repo API-PUBLIC) untuk SIADE.
 * Panduan endpoint & izin: REST-API/docs/MIGRASI-SERVICE-UMJAMBI.md (bagian 4.5).
 */
class MasterApiService
{
    private const CACHE_PREFIX = 'rest_api.referensi.';

    public function __construct(protected ApiService $api) {}

    public function expandTerms(int $start, int $end): array
    {
        $y = intdiv($start, 10);
        $s = $start % 10;
        $ye = intdiv($end, 10);
        $se = $end % 10;
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

    public function tahunAkademikAktif($kodeProdi = null)
    {
        $today = Carbon::today()->toDateString();
        $query = DB::table('master_tahun_akademik')
            ->where('status', 'A')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today);
        $query->whereJsonContains('kode_program_studi', $kodeProdi);

        return $query->orderByDesc('id')->value('kode_tahun_akademik');
    }

    public function krs($npm = null)
    {
        $npm = Crypt::decrypt($npm);
        $krsRaw = KRS::with([
            'jadwal',
            'mataKuliahJadwal',
            'mataKuliahLangsung',
            'hari',
        ])
            ->where('npm', $npm)
            ->get();

        $krsRaw = $krsRaw->sortBy([
            fn ($a, $b) => $a->kode_tahun_akademik <=> $b->kode_tahun_akademik,
            fn ($b, $a) => ($a->hari->nama_hari ?? '') <=> ($b->hari->nama_hari ?? ''),
        ]);

        $krs = [];
        $total_sks_kumulatif = 0;
        $total_bobot_kumulatif = 0;

        $mahasiswa = Mahasiswa::where('npm', $npm)->firstOrFail();
        $tahun_angkatan = $mahasiswa->tahun_angkatan;
        $kode_program_studi = $mahasiswa->kode_program_studi;
        $tahunAkademikAktif = $this->tahunAkademikAktif($kode_program_studi) ?? $tahun_angkatan;
        $tahunAkademikDitempuh = $this->expandTerms($tahun_angkatan, $tahunAkademikAktif);

        $masterDosen = collect($this->dataDosen())->keyBy('id');

        foreach ($tahunAkademikDitempuh as $key => $value) {
            if (! isset($krs[$value])) {
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
                    'nama_dosen' => $masterDosen[$row['jadwal']['dosen_id'] ?? null]['nama'] ?? '-',
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

    /**
     * Referensi biaya & potongan (SIMAKU) — GET /v1/keuangan/biaya-potongan.
     * Izin: keuangan.biaya-potongan.index
     *
     * @return list<array{id: int, nama: string, jenis: string}>
     */
    public function bipot(): array
    {
        return $this->referensi('bipot', 'v1/keuangan/biaya-potongan');
    }

    /**
     * Daftar dosen ringkas — GET /v1/kepegawaian/pegawai/semua?tipe=dosen.
     * Izin: kepegawaian.pegawai.semua
     *
     * $hanyaAktif = true untuk dropdown pilih PA; false (default) untuk menampilkan
     * nama dosen, termasuk yang sudah nonaktif.
     *
     * @return list<array{id: int, nik: ?string, nidn: ?string, nama: string, tipe: string, status: string}>
     */
    public function dataDosen(bool $hanyaAktif = false): array
    {
        $query = ['tipe' => 'dosen'];
        if ($hanyaAktif) {
            $query['status'] = 'aktif';
        }

        return $this->referensi(
            'dosen'.($hanyaAktif ? '_aktif' : ''),
            'v1/kepegawaian/pegawai/semua?'.http_build_query($query)
        );
    }

    /**
     * Daftar ruang kuliah — GET /v1/akademik/ruangan/semua.
     * Izin: akademik.ruangan.semua
     *
     * @return list<array{id: int, nama: string, kuota: ?int}>
     */
    public function dataRuangan(): array
    {
        return $this->referensi('ruangan', 'v1/akademik/ruangan/semua');
    }

    /**
     * PDF KHS — GET /v1/akademik/khs/{nim}/{tahun_akademik}/pdf. Izin: akademik.khs.cetak
     * 409 = nilai/EDOM belum lengkap (belum boleh dicetak).
     */
    public function cetakKhs(string $npm, string $periode): Response
    {
        return $this->api->getFile('v1/akademik/khs/'.rawurlencode($npm).'/'.rawurlencode($periode).'/pdf');
    }

    /**
     * PDF KRS — GET /v1/akademik/krs/{nim}/{tahun_akademik}/pdf. Izin: akademik.krs.cetak
     */
    public function cetakKrs(string $npm, string $periode): Response
    {
        return $this->api->getFile('v1/akademik/krs/'.rawurlencode($npm).'/'.rawurlencode($periode).'/pdf');
    }

    /**
     * Tagihan KKN banyak NIM — POST /v1/keuangan/tagihan/cari (maks. 100 NIM per request).
     * Izin: keuangan.tagihan.cari
     *
     * @param  list<string>  $npm
     * @return array{error_code: int, error_desc: string, items: list<array<string, mixed>>}
     */
    public function cekTagihanKKN(array $npm): array
    {
        return $this->cariTagihan($npm, ['jenis_tagihan' => 'KKN']);
    }

    /**
     * Cari tagihan banyak NIM, otomatis dipecah per 100 NIM.
     * Gagal di salah satu potongan → error_code ≠ 0 (items berisi yang sempat terambil).
     *
     * @param  list<string>  $npm
     * @param  array{tahun_akademik?: list<string>, jenis_tagihan?: string}  $filter
     * @return array{error_code: int, error_desc: string, items: list<array<string, mixed>>}
     */
    public function cariTagihan(array $npm, array $filter = []): array
    {
        $items = [];
        $nim = array_values(array_unique(array_filter(array_map('strval', $npm))));

        foreach (array_chunk($nim, 100) as $potongan) {
            $respon = $this->api->post('v1/keuangan/tagihan/cari', ['nim' => $potongan, ...$filter]);

            if ($respon['error_code'] !== 0 || ! ($respon['data']['success'] ?? false)) {
                return [
                    'error_code' => $respon['error_code'] ?: 1,
                    'error_desc' => $respon['data']['error']['message'] ?? $respon['error_desc'],
                    'items' => $items,
                ];
            }

            array_push($items, ...($respon['data']['data']['items'] ?? []));
        }

        return ['error_code' => 0, 'error_desc' => '', 'items' => $items];
    }

    /**
     * Ambil data referensi `…/semua` (bentuk { data: { items, meta } }), di-cache 10 menit.
     * Hanya hasil yang berhasil & tidak kosong yang disimpan, agar kegagalan sesaat tidak ikut ter-cache.
     */
    private function referensi(string $kunci, string $endpoint): array
    {
        $cacheKey = self::CACHE_PREFIX.$kunci;

        if (is_array($cached = Cache::get($cacheKey))) {
            return $cached;
        }

        $respon = $this->api->get($endpoint);
        $items = $respon['error_code'] === 0 ? ($respon['data']['data']['items'] ?? []) : [];

        if ($items !== []) {
            Cache::put($cacheKey, $items, now()->addMinutes(10));
        }

        return $items;
    }
}
