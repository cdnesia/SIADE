<?php

namespace App\Http\Controllers;

use App\Models\Fakultas;
use App\Models\JadwalPerkuliahan;
use App\Models\JadwalPertemuan;
use App\Models\JadwalPertemuanAbsensi;
use App\Models\KRS;
use App\Models\Kurikulum;
use App\Models\KurikulumMataKuliah;
use App\Models\KurikulumProdi;
use App\Models\Mahasiswa;
use App\Models\PenerimaBeasiswa;
use App\Models\Prodi;
use App\Models\SkalaNilai;
use App\Models\TahunAkademik;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    private $modul = 'master.sync';
    public function __construct()
    {

        view()->share('modul', $this->modul);
    }
    public function index()
    {
        $d['fakultas'] = Fakultas::all()->count();
        $d['prodi'] = Prodi::all()->count();
        $d['jenis_matakuliah'] = DB::table('master_jenis_matakuliah')->get()->count();
        $d['kurikulum'] = Kurikulum::get()->count();
        $d['kurikulum_prodi'] = KurikulumProdi::get()->count();
        $d['kurikulum_makul'] = KurikulumMataKuliah::get()->count();
        $d['skala_nilai'] = SkalaNilai::get()->count();
        $d['krs'] = KRS::get()->count();
        $d['tahun_akademik'] = TahunAkademik::get()->count();
        $d['jadwal_perkuliahan'] = JadwalPerkuliahan::get()->count();
        $d['penerima_beasiswa'] = PenerimaBeasiswa::get()->count();
        $d['jadwal_pertemuan'] = JadwalPertemuan::get()->count();
        $d['jadwal_pertemuan_absensi'] = jadwalPertemuanAbsensi::get()->count();
        return view('master.view', $d);
    }
    public function prodi()
    {
        $now = now();
        $totalNew = 0;
        $existingCodes = Prodi::pluck('kode_program_studi')->toArray();

        try {
            DB::beginTransaction();
            DB::connection('siade_old')->table('prodi')
                ->orderBy('ProdiID')
                ->chunk(500, function ($rows) use (&$totalNew, $now, $existingCodes) {

                    $insertData = [];

                    foreach ($rows as $m) {
                        if (in_array($m->ProdiID, $existingCodes)) {
                            continue;
                        }

                        $insertData[] = [
                            'kode_program_studi' => $m->ProdiID,
                            'nama_program_studi_idn' => $m->nama_id,
                            'nama_program_studi_eng' => $m->nama_en,
                            'jenjang' => $m->PendidikanJenjang,
                            'fakultas_id' => $m->FakultasID,
                            'status' => 'A',
                            'program_perkuliahan_id' => json_encode([]),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        $totalNew++;
                    }

                    if (!empty($insertData)) {
                        Prodi::insert($insertData);
                    }
                });

            DB::commit();

            return redirect()->back()->with('success', "Sinkronisasi berhasil. Data baru: $totalNew");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Sinkronisasi gagal: ' . $e->getMessage());
        }
    }
    public function fakultas()
    {
        $now = now();
        $totalNew = 0;
        $existingCodes = Fakultas::pluck('id')->toArray();
        DB::connection('siade_old')->table('fakultas')->orderBy('FakultasID')->chunk(500, function ($rows) use (&$totalNew, $existingCodes, $now) {
            $data = [];
            foreach ($rows as $m) {
                if (in_array($m->FakultasID, $existingCodes)) {
                    continue;
                }
                $data[] = [
                    'id' => $m->FakultasID,
                    'kode_fakultas' => rand(1, 10),
                    'nama_fakultas_idn' => $m->nama_id,
                    'nama_fakultas_eng' => $m->nama_en,
                    'dekan_id' => $m->dekan,
                    'status' => 'A',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $totalNew++;
            }
            if (!empty($data)) {
                Fakultas::insert($data);
            }
        });
        return redirect()->back()->with('success', "Sinkronisasi berhasil. Data baru: $totalNew");
    }
    public function kurikulum()
    {
        $now = now();
        $totalNew = 0;
        $existingCodes = Kurikulum::pluck('id')->toArray();
        DB::connection('siade_old')->table('kurikulum')->orderBy('id')->chunk(500, function ($rows) use (&$totalNew, $existingCodes, $now) {
            $data = [];
            foreach ($rows as $m) {
                if (in_array($m->id, $existingCodes)) {
                    continue;
                }
                $data[] = [
                    'id' => $m->id,
                    'kode_kurikulum' => $m->nama,
                    'nama_kurikulum' => $m->nama,
                    'keterangan' => strip_tags($m->deskripsi),
                    'status' => $m->NA === 'N' ? 'N' : 'A',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $totalNew++;
            }
            if (!empty($data)) {
                Kurikulum::insert($data);
            }
        });
        return redirect()->back()->with('success', "Sinkronisasi berhasil. Data baru: $totalNew");
    }
    public function kurikulumProdi()
    {
        $now = now();
        $totalNew = 0;
        $existingCodes = KurikulumProdi::pluck('id')->toArray();
        DB::connection('siade_old')->table('kurikulum_prodi')->orderBy('id')->chunk(500, function ($rows) use (&$totalNew, $existingCodes, $now) {
            $data = [];
            foreach ($rows as $m) {
                if (in_array($m->id, $existingCodes)) {
                    continue;
                }
                $terms = collect(explode(',', $m->angkatan))
                    ->map(fn($v) => (int)trim($v))
                    ->filter()->unique()->values()->all();
                $data[] = [
                    'id' => $m->id,
                    'kurikulum_id' => $m->id_kurikulum,
                    'kode_program_studi' => $m->ProdiID,
                    'tahun_angkatan' => json_encode($terms),
                    'status' => $m->status === 'NON-AKTIF' ? 'N' : 'A',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $totalNew++;
            }
            if (!empty($data)) {
                KurikulumProdi::insert($data);
            }
        });
        return redirect()->back()->with('success', "Sinkronisasi berhasil. Data baru: $totalNew");
    }
    public function kurikulumMataKuliah()
    {
        $now = now();
        $totalNew = 0;
        $existingCodes = KurikulumMataKuliah::pluck('id')->toArray();
        DB::connection('siade_old')->table('mk')->where('NA', 'A')->orderBy('id')->chunk(500, function ($rows) use (&$totalNew, $existingCodes, $now) {
            $data = [];
            foreach ($rows as $m) {
                if (in_array($m->id, $existingCodes)) {
                    continue;
                }
                $data[] = [
                    'id' => $m->id,
                    'kurikulum_id' => $m->id_kurikulum,
                    'kode_program_studi' => $m->ProdiID,
                    'kode_mata_kuliah' => $m->MkKode,
                    'nama_mata_kuliah_idn' => $m->NamaID,
                    'nama_mata_kuliah_eng' => $m->NamaEn,
                    'sks_tatap_muka' => $m->sks_tatap_muka,
                    'sks_praktek' => $m->sks_praktek,
                    'sks_prak_lap' => $m->sks_praktek_lap,
                    'sks_simulasi' => $m->sks_simulasi,
                    'sks_mata_kuliah' => $m->sks,
                    'semester' => $m->sesi,
                    'jenis_matakuliah_id' => 0,
                    'prasyarat_lulus' => json_encode([]),
                    'status' => 'A',
                    'created_at' => $now,
                    'updated_at' => $now,
                    'mata_kuliah_tipe' => $m->MkTipe
                ];
                $totalNew++;
            }
            if (!empty($data)) {
                KurikulumMataKuliah::insert($data);
            }
        });
        return redirect()->back()->with('success', "Sinkronisasi berhasil. Data baru: $totalNew");
    }
    public function skalaNilai()
    {
        $now = now();
        $totalNew = 0;
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        SkalaNilai::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        DB::connection('siade_old')->table('nilai')->orderBy('id')->chunk(500, function ($rows) use (&$totalNew, $now) {
            $data = [];
            foreach ($rows as $m) {
                $data[] = [
                    'id' => $m->id,
                    'nama' => $m->nama,
                    'lulus' => $m->lulus === 'YA' ? 'Y' : 'T',
                    'kode_program_studi' => $m->ProdiID,
                    'nilai_mulai' => $m->nilai_mulai,
                    'nilai_sampai' => $m->nilai_sampai,
                    'deskripsi_idn' => $m->deskripsi,
                    'deskripsi_eng' => $m->deskripsi_en,
                    'bobot' => $m->bobot,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $totalNew++;
            }
            if (!empty($data)) {
                SkalaNilai::insert($data);
            }
        });
        return redirect()->back()->with('success', "Sinkronisasi berhasil. Data baru: $totalNew");
    }
    public function jadwalPerkuliahan()
    {
        $now = now();
        $totalNew = 0;
        $prodiIds = Prodi::pluck('kode_program_studi');
        $existingCodes = JadwalPerkuliahan::pluck('id')->toArray();
        DB::connection('siade_old')
            ->table('jadwal')
            ->whereIn('ProdiID', $prodiIds)
            ->where('NA', 'A')
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$totalNew, $existingCodes, $now) {
                $data = [];
                foreach ($rows as $m) {
                    if (in_array($m->id, $existingCodes)) {
                        continue;
                    }
                    $data[] = [
                        'id' => $m->id,
                        'kode_program_studi' => $m->ProdiID,
                        'tahun_akademik' => $m->TahunID ?: 0,
                        'program_kuliah_id' => match ((int)$m->KelasID) {
                            1, 2 => 1,
                            3 => 2,
                            4 => 3,
                            default => null
                        },
                        'hari_id' => $m->HariID,
                        'ruang_id' => $m->RuangID,
                        'dosen_id' => $m->DosenID,
                        'kelompok' => $m->Kelompok,
                        'mata_kuliah_id' => $m->MkID,
                        'jam_mulai' => $m->jam_mulai,
                        'jam_selesai' => $m->jam_selesai,
                        'status' => 'A',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $totalNew++;
                }
                if (!empty($data)) {
                    JadwalPerkuliahan::insert($data);
                }
            });
        return redirect()->back()->with('success', "Sinkronisasi berhasil. Data baru: $totalNew");
    }
    public function krs()
    {
        $now = now();
        $totalNew = 0;
        $existingCodes = KRS::pluck('id')->toArray();
        $npm = Mahasiswa::all()->pluck('npm');

        DB::connection('siade_old')->table('mhsw_krs')
            ->where('NA', 'A')
            ->whereIn('nim', $npm)
            ->orderBy('id')
            ->chunk(1000, function ($rows) use (&$totalNew, $now, $existingCodes) {
                $data = [];
                foreach ($rows as $m) {
                    if (in_array($m->id, $existingCodes)) {
                        continue;
                    }
                    $data[] = [
                        'id' => $m->id,
                        'jadwal_id' => $m->id_jadwal,
                        'npm' => $m->nim,
                        'kode_tahun_akademik' => $m->TahunID,
                        'persetujuan_pa' => $m->status_pa === 'DISETUJUI' ? 'Y' : 'T',
                        'datetime_persetujuan_pa' => $m->datetime_status_pa,
                        'nilai_angka' => $m->nilai_angka,
                        'nilai_huruf' => $m->nilai_huruf,
                        'nilai_bobot' => $m->nilai_bobot,
                        'nilai_mutu' => 0,
                        'lulus' => $m->lulus === 'YA' ? 'Y' : 'T',
                        'cek_edome' => $m->cek_edome ?? 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $totalNew++;
                }
                if (!empty($data)) {
                    KRS::insert($data);
                }
            });
        return redirect()->back()->with('success', "Sinkronisasi berhasil. Data baru: $totalNew");
    }
    public function tahunAkademik()
    {
        $now = now();
        $totalNew = 0;
        $existingCodes = TahunAkademik::pluck('kode_tahun_akademik')->toArray();
        $tahun = KRS::distinct()->pluck('kode_tahun_akademik');

        $data = [];
        foreach ($tahun as $m) {
            if (in_array($m, $existingCodes)) {
                continue;
            }
            $data[] = [
                'kode_tahun_akademik' => $m,
                'nama_tahun_akademik' => substr($m, 0, 4) . ' ' . (substr($m, -1) == 1 ? 'Ganjil' : 'Genap'),
                'tanggal_mulai' => $now,
                'tanggal_selesai' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $totalNew++;
        }
        TahunAkademik::insert($data);
        return redirect()->back()->with('success', "Sinkronisasi berhasil. Data baru: $totalNew");
    }
    public function jenisMataKuliah()
    {
        $existingCodes =  DB::table('master_jenis_matakuliah')->pluck('id')->toArray();
        $data = [
            ['id' => 1, 'nama' => 'Wajib Nasional', 'keterangan' => 'MK wajib pemerintah seperti Pancasila, Kewarganegaraan, Bahasa Indonesia', 'status' => 'A'],
            ['id' => 2, 'nama' => 'Wajib Program Studi', 'keterangan' => 'MK inti sesuai kurikulum prodi', 'status' => 'A'],
            ['id' => 3, 'nama' => 'Pilihan', 'keterangan' => 'MK pilihan dalam kurikulum prodi', 'status' => 'A'],
            ['id' => 4, 'nama' => 'Tugas Akhir / Skripsi / Thesis', 'keterangan' => 'MK akhir program studi', 'status' => 'A'],
            ['id' => 5, 'nama' => 'Kerja Praktek / PKL / Magang', 'keterangan' => 'Praktik lapangan, magang industri', 'status' => 'A'],
            ['id' => 6, 'nama' => 'Seminar', 'keterangan' => 'Seminar akademik, proposal, kolokium', 'status' => 'A'],
            ['id' => 7, 'nama' => 'Penunjang / MBKM / MKU', 'keterangan' => 'MK umum lintas prodi atau MBKM', 'status' => 'A'],
            ['id' => 8, 'nama' => 'Remedial / Pengayaan', 'keterangan' => 'MK remedial atau pengayaan hasil evaluasi', 'status' => 'A'],
            ['id' => 9, 'nama' => 'KKN / Pengabdian Masyarakat', 'keterangan' => 'Kuliah kerja nyata atau kegiatan pengabdian', 'status' => 'A'],
            ['id' => 10, 'nama' => 'Lainnya', 'keterangan' => 'Jenis mata kuliah lain di luar klasifikasi utama', 'status' => 'A'],
        ];
        $newData = collect($data)->filter(fn($item) => !in_array($item['id'], $existingCodes))->values()->all();
        $totalNew = count($newData);

        if ($totalNew > 0) {
            DB::table('master_jenis_matakuliah')->insert($newData);
        }
        return redirect()->back()->with('success', "Sinkronisasi berhasil. Data baru: $totalNew");
    }
    public function penerimaBeasiswa()
    {
        $totalNew = 0;
        $existingCodes = PenerimaBeasiswa::pluck('id')->toArray();

        DB::connection('siade_old')->table('mhsw_beasiswa')
            ->where('NA', 'A')
            ->orderBy('id')
            ->chunk(1000, function ($rows) use (&$totalNew, $existingCodes) {
                $data = [];
                foreach ($rows as $m) {
                    $tahun_akademik = explode(',', $m->tahun_semester);
                    $data[] = [
                        'id' => $m->id,
                        'npm' => $m->nim,
                        'id_lembaga' => $m->id_lembaga,
                        'tahun_akademik' => json_encode($tahun_akademik),
                    ];
                    $totalNew++;
                }
                if (!empty($data)) {
                    PenerimaBeasiswa::upsert(
                        $data,
                        ['id'],
                        ['tahun_akademik']
                    );
                }
            });
        return redirect()->back()->with('success', "Sinkronisasi berhasil. Data baru: $totalNew");
    }
    public function jadwalPertemuan()
    {
        $totalNew = 0;
        $existingCodes = JadwalPertemuan::pluck('id')->toArray();
        DB::connection('siade_old')->table('jadwal_pertemuan')
            ->where('NA', 'A')
            ->orderBy('id')
            ->chunk(1000, function ($rows) use (&$totalNew, $existingCodes) {
                $data = [];
                foreach ($rows as $m) {
                    if (in_array($m->id, $existingCodes)) {
                        continue;
                    }
                    $data[] = [
                        'id' => $m->id,
                        'jadwal_id' => $m->id_jadwal,
                        'pertemuan' => $m->pertemuan,
                        'bahan_kajian' => $m->bahan_kajian,
                        'tanggal_pelaksanaan' => $m->tanggal_pelaksanaan,
                        'verifikator' => $m->verifikator_nim,
                        'status_verifikasi' => $m->status_verifikasi,
                        'tanggal_verifikasi' => $m->datetime_verifikasi,
                    ];
                    $totalNew++;
                }
                if (!empty($data)) {
                    JadwalPertemuan::insert($data);
                }
            });
        return redirect()->back()->with('success', "Sinkronisasi berhasil. Data baru: $totalNew");
    }
    public function jadwalPertemuanAbsensi()
    {
        // set_time_limit(0);
        // ini_set('memory_limit', '512M');
        // DB::disableQueryLog();
        // $totalNew = 0;
        // DB::connection('siade_old')->table('jadwal_pertemuan_absensi')
        //     ->orderBy('kode')
        //     ->chunk(500, function ($rows) use (&$totalNew) {
        //         $data = [];
        //         foreach ($rows as $m) {
        //             $data[] = [
        //                 'jadwal_id' => $m->id_jadwal,
        //                 'jadwal_pertemuan_id' => $m->id_jadwal_pertemuan,
        //                 'npm' => $m->nim,
        //                 'status_kehadiran_id' => $m->id_status_kehadiran,
        //             ];
        //             $totalNew++;
        //         }
        //         if (!empty($data)) {
        //             JadwalPertemuanAbsensi::insert($data);
        //         }
        //     });
        // return redirect()->back()->with('success', "Sinkronisasi berhasil. Data baru: $totalNew");
    }
}
