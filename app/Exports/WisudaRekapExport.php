<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Export rekap transkrip wisuda per angkatan:
 *  - Sheet "Transkrip"     : satu baris per mahasiswa, kode MK memanjang ke kanan.
 *  - Sheet "Keterangan MK" : arti tiap kode MK (nama, SKS, semester, wajib/pilihan).
 */
class WisudaRekapExport implements WithMultipleSheets
{
    /** @var array<int, array{kode: string, nama: string, sks: float, semester: ?int, wajib: ?bool, luar: bool}> */
    private array $kolomMk;

    /**
     * @param  Collection  $baris  hasil rekap per mahasiswa (berisi 'transkrip')
     * @param  array|null  $kurikulum  kurikulum Feeder yang dipakai (null jika gagal dimuat)
     * @param  Collection  $mkKurikulum  matakuliah kurikulum Feeder, key = kode MK
     * @param  array  $info  ['prodi', 'angkatan', 'filter']
     */
    public function __construct(
        private Collection $baris,
        private ?array $kurikulum,
        Collection $mkKurikulum,
        private array $info,
    ) {
        $this->kolomMk = $this->susunKolom($mkKurikulum);
    }

    public function sheets(): array
    {
        return [
            new WisudaRekapTranskripSheet($this->baris, $this->kolomMk, $this->kurikulum, $this->info),
            new WisudaRekapKeteranganSheet($this->kolomMk, $this->kurikulum),
        ];
    }

    /**
     * Urutan kolom MK: semua MK kurikulum (urut semester, kode), lalu kode lain yang
     * muncul di transkrip mahasiswa tapi tidak ada di kurikulum (ditandai "luar").
     */
    private function susunKolom(Collection $mkKurikulum): array
    {
        $kolom = $mkKurikulum->map(fn ($mk) => [
            'kode' => $mk['kode_mk'],
            'nama' => $mk['nama_mk'],
            'sks' => (float) $mk['sks'],
            'semester' => $mk['semester'],
            'wajib' => $mk['wajib'],
            'luar' => false,
        ])->values();

        $luar = $this->baris
            ->flatMap(fn ($b) => $b['transkrip']->where('diambil', true))
            ->reject(fn ($t) => $mkKurikulum->has(strtoupper(trim($t['kode_mk']))))
            ->unique(fn ($t) => strtoupper(trim($t['kode_mk'])))
            ->map(fn ($t) => [
                'kode' => strtoupper(trim($t['kode_mk'])),
                'nama' => $t['nama_mk'],
                'sks' => (float) $t['sks'],
                'semester' => $t['semester_mk'],
                'wajib' => null,
                'luar' => $mkKurikulum->isNotEmpty(),
            ])
            ->sortBy([['semester', 'asc'], ['kode', 'asc']])
            ->values();

        return $kolom->concat($luar)->all();
    }
}
