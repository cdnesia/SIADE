<?php

namespace App\Http\Controllers\kipk;

use App\Http\Controllers\Controller;
use App\Models\KRS;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\TahunAkademik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class CekKhsMahasiswa extends Controller
{
    public function index(Request $request)
    {
        $d['tahun_akademik'] = TahunAkademik::all();
        return view('kipk.form', $d);
    }
    public function store(Request $request)
    {
        $nim_input = $request->input('nim_list');
        $nim_array = preg_split("/\r\n|\n|\r/", trim($nim_input));
        $nim_array = array_filter(array_map('trim', $nim_array));

        $tahun_akademik = $request->input('tahun_akademik', []);

        $data_db = KRS::with([
            'jadwal',
            'mataKuliahJadwal',
            'mataKuliahLangsung',
            'hari'
        ])
            ->whereIn('npm', $nim_array)
            ->get();

        $krsRaw = $data_db->sortBy([
            fn($a, $b) => $a->kode_tahun_akademik <=> $b->kode_tahun_akademik,
            fn($b, $a) => ($a->hari->nama_hari ?? '') <=> ($b->hari->nama_hari ?? '')
        ]);

        $total_sks_kumulatif = 0;
        $total_bobot_kumulatif = 0;

        $masterMahasiswa = Mahasiswa::get()->keyBy('npm');
        $masterProdi = Prodi::get()->keyBy('kode_program_studi');


        $krs = [];

        foreach ($krsRaw as $row) {

            $nim = $row->npm;

            if (!isset($krs[$nim])) {

                $nama = $masterMahasiswa[$nim]->nama_mahasiswa ?? '';
                $kodeProdi = $masterMahasiswa[$nim]->kode_program_studi ?? null;
                $prodi = $masterProdi[$kodeProdi]->nama_program_studi_idn ?? '';

                $krs[$nim] = [];
                $krs[$nim]['nama_mahasiswa'] = $nama;
                $krs[$nim]['program_studi'] = $prodi;

                $total_sks_kumulatif = 0;
                $total_bobot_kumulatif = 0;
            }

            $ta = $row['kode_tahun_akademik'];

            if (!isset($krs[$nim][$ta])) {

                $krs[$nim][$ta] = [];
                $krs[$nim][$ta]['jumlah_sks'] = 0;
                $krs[$nim][$ta]['total_bobot'] = 0;
                $krs[$nim][$ta]['ips'] = 0;
                $krs[$nim][$ta]['ipk'] = 0;
            }

            $sks = $row['matakuliah']['sks_mata_kuliah'] ?? 0;
            $bobot = $row['nilai_bobot'] ?? 0;

            $total_sks_kumulatif += $sks;
            $total_bobot_kumulatif += $bobot * $sks;

            $krs[$nim][$ta]['jumlah_sks'] += $sks;
            $krs[$nim][$ta]['total_bobot'] += $bobot * $sks;

            $krs[$nim][$ta]['ips'] =
                $krs[$nim][$ta]['jumlah_sks']
                ? round($krs[$nim][$ta]['total_bobot'] / $krs[$nim][$ta]['jumlah_sks'], 2)
                : 0;

            $krs[$nim][$ta]['ipk'] =
                $total_sks_kumulatif
                ? round($total_bobot_kumulatif / $total_sks_kumulatif, 2)
                : 0;
        }


        sort($tahun_akademik);

        $data = [
            'mahasiswa'      => $krs,
            'tahun_akademik' => $tahun_akademik
        ];
        return view('kipk.view', $data);
    }
}
