<?php

namespace App\Services;

use App\Models\KRS;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DataService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function krs($npm = null)
    {
        $npm = Crypt::decrypt($npm);
        $krsRaw = KRS::with(['jadwal', 'matakuliah', 'hari'])
            ->where('npm', $npm)
            ->get();

        $krsRaw = $krsRaw->sortBy([
            fn($a, $b) => $a->kode_tahun_akademik <=> $b->kode_tahun_akademik,
            fn($b, $a) => ($a->hari->nama_hari ?? '') <=> ($b->hari->nama_hari ?? '')
        ]);

        $krs = [];
        $semester = 1;
        $total_sks_kumulatif = 0;
        $total_bobot_kumulatif = 0;

        foreach ($krsRaw as $row) {
            $ta = $row['kode_tahun_akademik'];
            if (!isset($krs[$ta])) {
                $krs[$ta] = [];
                $krs[$ta]['semester'] = $semester;
                $krs[$ta]['jumlah_sks'] = 0;
                $krs[$ta]['total_bobot'] = 0;
                $krs[$ta]['krs'] = [];
                $semester++;
            }

            $sks = $row['matakuliah']['sks_mata_kuliah'] ?? 0;
            $bobot = $row['nilai_bobot'] ?? 0;

            $krs[$ta]['jumlah_sks'] += $sks;
            $krs[$ta]['total_bobot'] += $bobot * $sks;

            $total_sks_kumulatif += $sks;
            $total_bobot_kumulatif += $bobot * $sks;

            $krs[$ta]['krs'][] = [
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

        return $krs;
    }
}
