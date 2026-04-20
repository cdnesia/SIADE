<?php

namespace App\Services;

use App\Models\KRS;
use Illuminate\Support\Facades\Crypt;

class MasterApiService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function krs($periode = null, $prodi = null, $angkatan = null)
    {
        $krsRaw = KRS::with([
            'jadwal',
            'mataKuliahJadwal',
            'mataKuliahLangsung',
            'mahasiswa'
        ])
            ->where('kode_tahun_akademik', $periode)
            ->whereHas('mahasiswa', function ($q) use ($prodi, $angkatan) {
                $q->where('kode_program_studi', $prodi)
                  ->where('tahun_angkatan', 'like', "{$angkatan}%");
            })
            ->get();

        $krs = $krsRaw->map(function ($item) {
            return [
                'nim' => $item->npm,
                'kode_tahun_akademik' => $item->kode_tahun_akademik,
                'kode_mata_kuliah' => $item->matakuliah->kode_mata_kuliah ?? null,
                'nama_mata_kuliah' => $item->matakuliah->nama_mata_kuliah_idn ?? null,
                'nilai_angka' => $item->nilai_angka,
                'nilai_huruf' => $item->nilai_huruf,
                'nilai_indeks' => $item->nilai_bobot
            ];
        });

        return $krs;
    }
}
