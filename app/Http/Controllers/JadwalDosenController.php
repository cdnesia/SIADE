<?php

namespace App\Http\Controllers;

use App\Models\Hari;
use App\Models\JadwalPerkuliahan;
use App\Models\KelasPerkuliahan;
use App\Models\Prodi;
use App\Services\MasterApiService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class JadwalDosenController extends Controller
{
    private $modul = 'jadwal-dosen';
    public function __construct()
    {
        view()->share('modul', $this->modul);
    }

    public function index(Request $request, MasterApiService $dataService)
    {
        $masterDosen = collect($dataService->dataDosen()['data']['data'])->keyBy('id')->toArray();

        if ($request->ajax()) {
            $query = JadwalPerkuliahan::from('tbl_jadwal_perkuliahan as j')
                ->leftJoin('master_program_studi as p', 'j.kode_program_studi', '=', 'p.kode_program_studi')
                ->leftJoin('master_kelas_perkuliahan as k', 'j.program_kuliah_id', '=', 'k.id')
                ->leftJoin('master_hari as h', 'j.hari_id', '=', 'h.id')
                ->leftJoin('master_kurikulum_matakuliah as m', 'j.mata_kuliah_id', '=', 'm.id')
                ->select(
                    'j.*',
                    'p.nama_program_studi_idn as nama_prodi',
                    'k.nama_program_perkuliahan as nama_kelas',
                    'h.nama_hari',
                    'm.kode_mata_kuliah',
                    'm.nama_mata_kuliah_idn'
                );

            if ($request->prodi) {
                $query->where('j.kode_program_studi', $request->prodi);
            }

            if ($request->tahun_akademik) {
                $query->where('j.tahun_akademik', $request->tahun_akademik);
            }

            if ($request->kelas) {
                $query->where('j.program_kuliah_id', $request->kelas);
            }

            if ($request->hari) {
                $query->where('j.hari_id', $request->hari);
            }

            if ($request->ruang) {
                $query->where('j.ruang_id', $request->ruang);
            }

            if ($request->dosen) {
                $query->where(function ($q) use ($request) {
                    $q->where('j.dosen_id', $request->dosen)
                        ->orWhereRaw("FIND_IN_SET(?, j.dosen_team)", [$request->dosen]);
                });
            }

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('nama_dosen', function ($row) use ($masterDosen) {
                    $nama = $masterDosen[$row->dosen_id]['nama_lengkap'] ?? '-';

                    if (!empty($row->dosen_team)) {
                        $namaTeam = collect(explode(',', $row->dosen_team))
                            ->map(fn($id) => trim($id))
                            ->filter(fn($id) => $id !== '' && $id != $row->dosen_id)
                            ->unique()
                            ->map(fn($id) => $masterDosen[$id]['nama_lengkap'] ?? null)
                            ->filter()
                            ->implode(', ');

                        if ($namaTeam !== '') {
                            $nama .= ' (Team: ' . $namaTeam . ')';
                        }
                    }

                    return $nama;
                })
                ->addColumn('mata_kuliah', function ($row) {
                    return trim(($row->kode_mata_kuliah ?? '-') . ' ' . ($row->nama_mata_kuliah_idn ?? ''));
                })
                ->addColumn('jam', function ($row) {
                    return substr($row->jam_mulai, 0, 5) . ' - ' . substr($row->jam_selesai, 0, 5);
                })
                ->make(true);
        }

        $prodi = Prodi::orderBy('nama_program_studi_idn')->get();
        $kelas = KelasPerkuliahan::orderBy('nama_program_perkuliahan')->get();
        $hari = Hari::orderBy('id')->get();
        $tahunAkademik = JadwalPerkuliahan::select('tahun_akademik')
            ->distinct()
            ->orderByDesc('tahun_akademik')
            ->pluck('tahun_akademik');
        $ruang = JadwalPerkuliahan::select('ruang_id')
            ->distinct()
            ->orderBy('ruang_id')
            ->pluck('ruang_id');
        $dosen = collect($dataService->dataDosen()['data']['data'])->sortBy('nama_lengkap')->values();

        return view('jadwal-dosen.view', compact('prodi', 'kelas', 'hari', 'tahunAkademik', 'ruang', 'dosen'));
    }
}
