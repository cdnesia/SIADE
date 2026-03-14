<?php

namespace App\Http\Controllers\kipk;

use App\Http\Controllers\Controller;
use App\Models\KRS;
use App\Models\Mahasiswa;
use App\Models\PenerimaBeasiswa;
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

        $cekBeasiswa = PenerimaBeasiswa::whereIn('npm', $nim_array)
            ->get()
            ->keyBy('npm');

        $masterMahasiswa = Mahasiswa::whereIn('npm', $nim_array)
            ->get()
            ->keyBy('npm');

        $kodeProdiList = $masterMahasiswa->pluck('kode_program_studi')->unique();

        $masterProdi = Prodi::whereIn('kode_program_studi', $kodeProdiList)
            ->get()
            ->keyBy('kode_program_studi');

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
            fn($a, $b) => ($a->hari->nama_hari ?? '') <=> ($b->hari->nama_hari ?? '')
        ]);

        $krs = [];
        $total_sks_kumulatif = [];
        $total_bobot_kumulatif = [];

        foreach ($krsRaw as $row) {

            $nim = $row->npm;

            if (!isset($krs[$nim])) {

                $nama = $masterMahasiswa[$nim]->nama_mahasiswa ?? '';
                $kodeProdi = $masterMahasiswa[$nim]->kode_program_studi ?? null;
                $prodi = $masterProdi[$kodeProdi]->nama_program_studi_idn ?? '';

                $krs[$nim] = [
                    'nama_mahasiswa' => $nama,
                    'program_studi'  => $prodi,
                    'status_kipk'    => isset($cekBeasiswa[$nim])
                        ? 'Penerima KIPK'
                        : 'Bukan Penerima KIPK'
                ];

                $total_sks_kumulatif[$nim] = 0;
                $total_bobot_kumulatif[$nim] = 0;
            }

            $ta = $row->kode_tahun_akademik;
            if (!in_array($ta, $tahun_akademik)) {
                continue;
            }

            if (!isset($krs[$nim][$ta])) {

                $krs[$nim][$ta] = [
                    'jumlah_sks'  => 0,
                    'total_bobot' => 0,
                    'ips'         => 0,
                    'ipk'         => 0
                ];
            }

            $sks = $row['matakuliah']['sks_mata_kuliah'] ?? 0;
            $bobot = $row->nilai_bobot ?? 0;

            $total_sks_kumulatif[$nim] += $sks;
            $total_bobot_kumulatif[$nim] += $bobot * $sks;

            $krs[$nim][$ta]['jumlah_sks'] += $sks;
            $krs[$nim][$ta]['total_bobot'] += $bobot * $sks;

            $krs[$nim][$ta]['ips'] =
                $krs[$nim][$ta]['jumlah_sks']
                ? round($krs[$nim][$ta]['total_bobot'] / $krs[$nim][$ta]['jumlah_sks'], 2)
                : 0;

            $krs[$nim][$ta]['ipk'] =
                $total_sks_kumulatif[$nim]
                ? round($total_bobot_kumulatif[$nim] / $total_sks_kumulatif[$nim], 2)
                : 0;
        }

        foreach ($nim_array as $nim) {

            if (!isset($krs[$nim])) {

                $nama = $masterMahasiswa[$nim]->nama_mahasiswa ?? '';
                $kodeProdi = $masterMahasiswa[$nim]->kode_program_studi ?? null;
                $prodi = $masterProdi[$kodeProdi]->nama_program_studi_idn ?? '';

                $krs[$nim] = [
                    'nama_mahasiswa' => $nama,
                    'program_studi'  => $prodi,
                    'status_kipk'    => isset($cekBeasiswa[$nim])
                        ? 'Penerima KIPK'
                        : 'Bukan Penerima KIPK'
                ];
            }
        }

        sort($tahun_akademik);

        $data = [
            'mahasiswa'      => $krs,
            'tahun_akademik' => $tahun_akademik
        ];

        return view('kipk.view', $data);
    }
}
