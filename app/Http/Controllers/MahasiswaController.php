<?php

namespace App\Http\Controllers;

use App\Models\Fakultas;
use App\Models\KelasPerkuliahan;
use App\Models\KRS;
use App\Models\KurikulumMataKuliah;
use App\Models\KurikulumProdi;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\SkalaNilai;
use App\Services\DataService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class MahasiswaController extends Controller
{
    private $modul = 'mahasiswa';
    public function __construct()
    {
        view()->share('modul', $this->modul);
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Mahasiswa::from('master_mahasiswa as m')
                ->leftJoin('master_program_studi as p', 'm.kode_program_studi', '=', 'p.kode_program_studi')
                ->leftJoin('master_kelas_perkuliahan as k', 'm.program_kuliah_id', '=', 'k.id')
                ->select(
                    'm.*',
                    'p.nama_program_studi_idn as nama_prodi',
                    'k.nama_program_perkuliahan as nama_kelas'
                );

            if ($request->prodi) {
                $query->where('m.kode_program_studi', $request->prodi);
            }

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('aksi', function ($row) {
                    $btn = '';
                    // if (auth('web')->user()->can($this->modul . '.edit')) {
                    //     $btn .= '<a href="' . route($this->modul . '.edit', Crypt::encrypt($row->id)) . '" class="btn btn-sm btn-primary me-1"><i class="bx bx-message-square-edit me-0"></i></a>';
                    // }
                    // if (auth('web')->user()->can($this->modul . '.destroy')) {
                    //     $btn .= '<form action="' . route($this->modul . '.destroy', Crypt::encrypt($row->id)) . '" method="POST" style="display:inline-block;">
                    //         ' . csrf_field() . '
                    //         ' . method_field('DELETE') . '
                    //         <button type="submit" class="btn btn-sm btn-danger me-1" onclick="return confirm(\'Yakin ingin menghapus?\')"><i class="bx bx-message-square-x me-0"></i></button>
                    //      </form>';
                    // }
                    $btn .= '<a href="' . route($this->modul . '.show', Crypt::encrypt($row->id)) . '" class="btn btn-sm btn-info me-1"><i class="bx bx-search-alt me-0"></i>Detail</a>';
                    if (auth('web')->user()->can($this->modul . '.detail.krs')) {
                        $btn .= '<form action="' . route($this->modul . '.detail.krs', Crypt::encrypt($row->npm)) . '" method="POST" style="display:inline-block;">
                            ' . csrf_field() . '
                            <button type="submit" class="btn btn-sm btn-info me-1"><i class="bx bx-search-alt mr-1"></i>KRS</button>
                         </form>';
                    }
                    if (auth('web')->user()->can($this->modul . '.detail.khs')) {
                        $btn .= '<form action="' . route($this->modul . '.detail.khs', Crypt::encrypt($row->npm)) . '" method="get" style="display:inline-block;">
                            <button type="submit" class="btn btn-sm btn-info me-1"><i class="bx bx-search-alt mr-1"></i>KHS</button>
                         </form>';
                    }
                    return $btn ?: '-';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }
        return view('mahasiswa.view');
    }
    public function create() {}
    public function store() {}
    public function show(Request $request, $id, DataService $dataService)
    {
        $page = $request->input('p');

        $id = Crypt::decrypt($id);

        $masterProdi = Prodi::all()->keyBy('kode_program_studi');
        $masterFakultas = Fakultas::all()->keyBy('id');
        $masterKelas = KelasPerkuliahan::all()->keyBy('id');
        $masterJenisPendaftaran = DB::table('master_jenis_pendaftaran')->get()->keyBy('id');
        $masterDosen = collect($dataService->dataDosen())
            ->map(function ($item) {
                return [
                    'id' => $item['id'],
                    'nama_lengkap' => $item['nama_lengkap'],
                    'nidn' => $item['nidn'] ?? $item['nik']
                ];
            })
            ->keyBy('id');

        $mahasiswa = Mahasiswa::where('id', $id)->get()->map(function ($item) use ($masterProdi, $masterKelas, $masterJenisPendaftaran, $masterDosen, $masterFakultas) {
            return [
                'id' => Crypt::encrypt($item->id),
                'nama_mahasiswa' => $item->nama_mahasiswa,
                'npm' => $item->npm,
                'tahun_angkatan' => $item->tahun_angkatan,
                'nama_fakultas' => $masterFakultas[$masterProdi[$item->kode_program_studi]->fakultas_id]->nama_fakultas_idn,
                'kode_program_studi' => $item->kode_program_studi,
                'nama_program_studi' => $masterProdi[$item->kode_program_studi]->nama_program_studi_idn,
                'program_kuliah_id' => $item->program_kuliah_id,
                'nama_program_kuliah' => $masterKelas[$item->program_kuliah_id]->nama_program_perkuliahan,
                'jenis_pendaftaran_id' => $item->jenis_pendaftaran_id,
                'nama_jenis_pendaftaran' => $masterJenisPendaftaran[$item->jenis_pendaftaran_id]->nama_jenis_pendaftaran,
                'pa_id' => $item->pa_id,
                'nama_pa' => $masterDosen[$item->pa_id]['nama_lengkap'],
                'nidn_pa' => $masterDosen[$item->pa_id]['nidn'],
            ];
        })->first();

        $d['krs'] = $dataService->krs(Crypt::encrypt($mahasiswa['npm']));
        $d['mahasiswa'] = $mahasiswa;
        $d['page'] = $page;
        return view('mahasiswa.show', $d);
    }
    public function edit() {}
    public function update() {}
    public function krsCreate(Request $request, $npm)
    {
        $npm = Crypt::decrypt($npm);
        $kode_tahun_akademik = $request->kode_tahun_akademik;
        $matakuliah = $request->matakuliah;

        $mahasiswa = Mahasiswa::where('npm', $npm)->firstOrFail();

        foreach ($matakuliah as $key => $value) {
            DB::table('tbl_mahasiswa_krs')->insert([
                'npm' => $npm,
                'mata_kuliah_id' => $value,
                'jadwal_id' => 0,
                'kode_tahun_akademik' => $kode_tahun_akademik,
                'persetujuan_pa' => now(),
                'datetime_persetujuan_pa' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return back()->with('success', 'Mata kuliah berhasil dikontrak');
    }
    public function krsDestroy($id)
    {
        $id = Crypt::decrypt($id);
        KRS::where('id', $id)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Data KRS berhasil dihapus'
        ]);
    }
    public function krs($id, DataService $service)
    {
        $krs = $service->krs($id);

        $d['krs'] = $krs;
        return view('mahasiswa.krs', $d);
    }
    public function khs($id, DataService $service)
    {
        $krs = $service->krs($id);
        $flatKrs = collect($krs)
            ->pluck('krs')
            ->flatten(1);

        $kodeMkArray = $flatKrs->map(function ($item) {
            return $item['kode_mata_kuliah'];
        });

        $dataMahasiswa = Mahasiswa::where('npm', Crypt::decrypt($id))->first();
        $tahun_angkatan = $dataMahasiswa->tahun_angkatan;
        $kode_program_studi = $dataMahasiswa->kode_program_studi;

        $kurikulum_id = KurikulumProdi::whereJsonContains('tahun_angkatan', (int)$tahun_angkatan)->where('kode_program_studi', $kode_program_studi)->pluck('kurikulum_id')->first();

        $mataKuliah = KurikulumMataKuliah::where('kode_program_studi', $kode_program_studi)
            ->where('kurikulum_id', $kurikulum_id)
            ->whereNotIn('kode_mata_kuliah', $kodeMkArray)
            ->orderBy('semester')
            ->get();

        $d['krs'] = $krs;
        $d['matakuliah'] = $mataKuliah;
        return view('mahasiswa.khs', $d);
    }
    public function khsUpdateNilai($id, Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'nilai' => 'required|numeric|min:0|max:100',
                'mahasiswa' => 'required'
            ]);

            try {
                $krsId = Crypt::decrypt($id);
                $npm   = Crypt::decrypt($request->input('mahasiswa'));
            } catch (DecryptException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter tidak valid'
                ], 400);
            }

            $mahasiswa = Mahasiswa::select('kode_program_studi')
                ->where('npm', $npm)
                ->first();

            if (!$mahasiswa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mahasiswa tidak ditemukan',
                ], 404);
            }

            $nilaiAngka = $request->input('nilai');

            $skalaNilai = SkalaNilai::where('nilai_mulai', '<=', $nilaiAngka)
                ->where('nilai_sampai', '>=', $nilaiAngka)
                ->where('kode_program_studi', $mahasiswa->kode_program_studi)
                ->first();

            if (!$skalaNilai) {
                return response()->json([
                    'success' => false,
                    'message' => 'Skala nilai tidak ditemukan'
                ], 404);
            }

            $updated = DB::table('tbl_mahasiswa_krs')
                ->where('id', $krsId)
                ->where('npm', $npm)
                ->update([
                    'nilai_angka' => $nilaiAngka,
                    'nilai_huruf' => $skalaNilai->nama,
                    'nilai_bobot' => $skalaNilai->bobot,
                    'lulus'       => $skalaNilai->lulus,
                    'updated_at'  => now(),
                ]);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data KRS tidak ditemukan atau gagal diupdate'
                ], 404);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Nilai berhasil diupdate',
                'data' => [
                    'nilai_angka' => $nilaiAngka,
                    'nilai_huruf' => $skalaNilai->nama,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    public function destroy($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $deleted = DB::table('master_mahasiswa')->where('id', $id)->delete();

            if ($deleted) {
                return redirect()
                    ->route($this->modul . '.index')
                    ->with('success', 'Data mahasiswa berhasil dihapus');
            } else {
                return redirect()
                    ->route($this->modul . '.index')
                    ->with('error', 'Data mahasiswa tidak ditemukan');
            }

            return redirect()
                ->route($this->modul . '.index')
                ->with('success', 'Data mahasiswa berhasil dihapus');
        } catch (DecryptException $e) {
            return redirect()
                ->route($this->modul . '.index')
                ->with('error', 'ID tidak valid');
        }
    }
    public function sync()
    {
        $total = 0;
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            $existingNpm = Mahasiswa::pluck('npm')->toArray();
            DB::connection('siade_old')->table('mhsw')
                ->orderBy('nim')
                ->chunk(1000, function ($rows) use (&$total, $existingNpm) {
                    $data = [];
                    foreach ($rows as $m) {
                        if (!in_array($m->nim, $existingNpm)) {
                            $data[] = [
                                'npm' => $m->nim,
                                'va_code' => $m->va_code,
                                'nama_mahasiswa' => strtoupper($m->nama_lengkap),
                                'tahun_angkatan' => (int)$m->TahunID,
                                'kode_program_studi' => $m->ProdiID,
                                'program_kuliah_id' => match ((int)$m->KelasID) {
                                    1, 2 => 1,
                                    3 => 2,
                                    4 => 3,
                                    default => null
                                },
                                'jenis_pendaftaran_id' => match ((int)$m->JenisPendaftaranID) {
                                    1 => 1,
                                    3 => 2,
                                    4 => 13,
                                    default => null
                                },
                                'pa_id' => $m->PaID,
                            ];
                            $existingNpm[] = $m->nim;
                        }
                    }
                    if (!empty($data)) {
                        Mahasiswa::insert($data);
                        $total += count($data);
                    }
                });

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return redirect()
                ->route('mahasiswa.index')
                ->with('success', "Sinkronisasi berhasil. Total mahasiswa: $total.");
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return redirect()
                ->route('mahasiswa.index')
                ->with('error', "Sinkronisasi gagal: {$e->getMessage()}");
        }
    }
}
