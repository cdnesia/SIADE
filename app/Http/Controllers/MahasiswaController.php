<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
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
                    if (auth('web')->user()->can($this->modul . '.edit')) {
                        $btn .= '<a href="' . route($this->modul . '.edit', Crypt::encrypt($row->id)) . '" class="btn btn-sm btn-primary me-1"><i class="bx bx-message-square-edit me-0"></i></a>';
                    }
                    if (auth('web')->user()->can($this->modul . '.destroy')) {
                        $btn .= '<form action="' . route($this->modul . '.destroy', Crypt::encrypt($row->id)) . '" method="POST" style="display:inline-block;">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                            <button type="submit" class="btn btn-sm btn-danger me-1" onclick="return confirm(\'Yakin ingin menghapus?\')"><i class="bx bx-message-square-x me-0"></i></button>
                         </form>';
                    }
                    if (auth('web')->user()->can($this->modul . '.detail.krs')) {
                        $btn .= '<form action="' . route($this->modul . '.detail.krs', Crypt::encrypt($row->npm)) . '" method="POST" style="display:inline-block;">
                            ' . csrf_field() . '
                            <button type="submit" class="btn btn-sm btn-info me-1"><i class="bx bx-search-alt mr-1"></i>KRS</button>
                         </form>';
                    }
                    if (auth('web')->user()->can($this->modul . '.detail.khs')) {
                        $btn .= '<form action="' . route($this->modul . '.detail.khs', Crypt::encrypt($row->npm)) . '" method="POST" style="display:inline-block;">
                            ' . csrf_field() . '
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
    public function show() {}
    public function edit() {}
    public function update() {}
    public function krs($id, DataService $service)
    {
        $d['krs'] = $service->krs($id);
        return view('mahasiswa.krs', $d);
    }
    public function khs($id, DataService $service)
    {
        $d['krs'] = $service->krs($id);
        return view('mahasiswa.khs', $d);
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
