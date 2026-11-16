<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Mengambil kurikulum & matakuliah kurikulum langsung dari Neo Feeder.
 * Hasil di-cache agar halaman transkrip tidak memanggil Feeder setiap dibuka;
 * parameter $segarkan = true memaksa ambil ulang dari Feeder.
 */
class KurikulumFeederService
{
    private const LAMA_CACHE = 3600; // detik

    /** Setelah Feeder gagal dihubungi, jangan coba lagi selama ini (detik) agar halaman tidak menggantung */
    private const JEDA_SETELAH_GAGAL = 120;

    private const KUNCI_GAGAL = 'feeder.terakhir-gagal';

    public function __construct(protected NeofeederService $feeder) {}

    /**
     * Daftar kurikulum milik prodi (kode prodi lokal = kode prodi Feeder),
     * diurutkan dari yang paling lama berlaku.
     *
     * @throws RuntimeException jika Feeder gagal dihubungi
     */
    public function daftarKurikulum(string $kodeProdi, bool $segarkan = false): Collection
    {
        $kodeProdi = preg_replace('/[^0-9A-Za-z]/', '', $kodeProdi);

        $data = $this->ambil("feeder.kurikulum.$kodeProdi", $segarkan, function () use ($kodeProdi) {
            $prodi = $this->panggil('GetProdi', "kode_program_studi='$kodeProdi'");
            if (empty($prodi)) {
                return [];
            }
            $idProdi = $prodi[0]['id_prodi'];

            return $this->panggil('GetListKurikulum', "id_prodi='$idProdi'");
        });

        return collect($data)
            ->map(fn ($k) => [
                'id_kurikulum' => $k['id_kurikulum'],
                'nama_kurikulum' => $k['nama_kurikulum'],
                'id_semester' => (string) $k['id_semester'],
                'semester_mulai_berlaku' => $k['semester_mulai_berlaku'],
                'jumlah_sks_lulus' => (int) $k['jumlah_sks_lulus'],
                'jumlah_sks_wajib' => (int) $k['jumlah_sks_wajib'],
                'jumlah_sks_pilihan' => (int) $k['jumlah_sks_pilihan'],
            ])
            ->sortBy('id_semester')
            ->values();
    }

    /**
     * Tentukan kurikulum yang dipakai untuk prodi + angkatan, termasuk matakuliahnya.
     * $idKurikulum (opsional) mengganti pilihan otomatis jika valid untuk prodi tsb.
     *
     * @return array{daftar: Collection, otomatis: ?array, dipakai: ?array, manual: bool, matakuliah: Collection}
     *
     * @throws RuntimeException jika Feeder gagal dihubungi
     */
    public function tentukan(string $kodeProdi, string $angkatan, ?string $idKurikulum = null, bool $segarkan = false): array
    {
        $daftar = $this->daftarKurikulum($kodeProdi, $segarkan);
        $otomatis = $daftar->isEmpty() ? null : $this->kurikulumUntukAngkatan($daftar, $angkatan);
        $manual = $idKurikulum ? $daftar->firstWhere('id_kurikulum', $idKurikulum) : null;
        $dipakai = $manual ?? $otomatis;

        return [
            'daftar' => $daftar,
            'otomatis' => $otomatis,
            'dipakai' => $dipakai,
            'manual' => $manual !== null && $manual !== $otomatis,
            'matakuliah' => $dipakai ? $this->matakuliah($dipakai['id_kurikulum'], $segarkan) : collect(),
        ];
    }

    /**
     * Kurikulum yang berlaku untuk angkatan: kurikulum dengan semester mulai berlaku
     * paling akhir yang tidak melewati semester masuk angkatan. Jika angkatan lebih tua
     * dari semua kurikulum, dipakai kurikulum paling lama.
     */
    public function kurikulumUntukAngkatan(Collection $daftar, string $angkatan): ?array
    {
        return $daftar->filter(fn ($k) => $k['id_semester'] <= $angkatan)->last()
            ?? $daftar->first();
    }

    /**
     * Matakuliah dalam satu kurikulum, di-key dengan kode matakuliah (huruf besar).
     *
     * @throws RuntimeException jika Feeder gagal dihubungi
     */
    public function matakuliah(string $idKurikulum, bool $segarkan = false): Collection
    {
        if (! preg_match('/^[0-9a-f-]{36}$/i', $idKurikulum)) {
            return collect();
        }

        $data = $this->ambil("feeder.matkul-kurikulum.$idKurikulum", $segarkan,
            fn () => $this->panggil('GetMatkulKurikulum', "id_kurikulum='$idKurikulum'"));

        return collect($data)
            ->map(fn ($m) => [
                'kode_mk' => strtoupper(trim($m['kode_mata_kuliah'])),
                'nama_mk' => $m['nama_mata_kuliah'],
                'semester' => (int) $m['semester'],
                'sks' => (float) $m['sks_mata_kuliah'],
                'wajib' => (string) $m['apakah_wajib'] === '1',
            ])
            ->sortBy([['semester', 'asc'], ['kode_mk', 'asc']])
            ->keyBy('kode_mk');
    }

    private function ambil(string $kunci, bool $segarkan, callable $ambilData): array
    {
        if ($segarkan) {
            Cache::forget($kunci);
            Cache::forget(self::KUNCI_GAGAL);
        }

        if (! Cache::has($kunci) && ($gagal = Cache::get(self::KUNCI_GAGAL))) {
            throw new RuntimeException($gagal.' (dicoba lagi otomatis setelah 2 menit, atau klik Muat Ulang Feeder)');
        }

        return Cache::remember($kunci, self::LAMA_CACHE, $ambilData);
    }

    private function panggil(string $aksi, string $filter): array
    {
        $parameter = [
            'act' => $aksi,
            'filter' => $filter,
            'order' => '',
            'limit' => 0,
            'offset' => 0,
        ];

        // Feeder kadang timeout saat minta token, jadi coba sekali lagi sebelum menyerah
        $hasil = $this->feeder->getData($parameter);
        if ((int) ($hasil['error_code'] ?? 1) !== 0) {
            $hasil = $this->feeder->getData($parameter);
        }

        if ((int) ($hasil['error_code'] ?? 1) !== 0) {
            $pesan = "Neo Feeder $aksi gagal: ".($hasil['error_desc'] ?? 'tanpa keterangan');
            Cache::put(self::KUNCI_GAGAL, $pesan, self::JEDA_SETELAH_GAGAL);

            // Lempar exception supaya hasil gagal tidak ikut tersimpan di cache
            throw new RuntimeException($pesan);
        }

        return $hasil['data'] ?? [];
    }
}
