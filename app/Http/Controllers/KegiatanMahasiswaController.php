<?php

namespace App\Http\Controllers;

use App\Models\KegiatanMahasiswa;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Services\DataService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class KegiatanMahasiswaController extends Controller
{
    private $modul = 'kegiatan-mahasiswa';
    public function __construct()
    {

        view()->share('modul', $this->modul);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(DataService $dataService)
    {
        $kegiatans = DB::table('tbl_kegiatan_mahasiswa as km')
            ->select('km.*', 'kp.nama_program_perkuliahan')
            ->leftJoin('master_kelas_perkuliahan as kp', 'km.kelas_perkuliahan_id', '=', 'kp.id')
            ->get();
        $allProdi = Prodi::all()->keyBy('kode_program_studi');
        $allbipot = collect($dataService->bipot())->keyBy('id');

        foreach ($kegiatans as $item) {
            $kodeProdi = $item->kode_program_studi
                ? json_decode($item->kode_program_studi, true)
                : [];

            $item->nama_bipot = $allbipot[$item->id_bipot]['nama_bipot'] ?? null;
            $item->prodis = collect($kodeProdi)
                ->map(fn($kode) => $allProdi[$kode] ?? null)
                ->filter()
                ->values();
        }
        $d['kegiatan'] = $kegiatans;
        return view('kegiatan-mahasiswa.view', $d);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(DataService $dataService)
    {
        $d['kelas_perkuliahan'] = DB::table('master_kelas_perkuliahan')->get();
        $d['tahun_angkatan'] = Mahasiswa::distinct()->pluck('tahun_angkatan');
        $d['prodi'] = Prodi::orderBy('nama_program_studi_idn')->get();
        $d['bipot'] = $dataService->bipot();
        $d['data'] = null;
        return view('kegiatan-mahasiswa.form', $d);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tahun_angkatan' => 'required|array',
            'kode_program_studi' => 'required|array',
            'tipe' => 'required',
            'minimal_semester' => 'required',
            'minimal_sks' => 'required',
            'maksimal_nilai_d' => 'required',
            'nama_kegiatan' => 'required|string|max:255',
            'nama_biaya' => 'required',
            'biaya_pendaftaran' => 'required',
            'kelas_perkuliahan_id' => 'required',
        ]);

        KegiatanMahasiswa::insert([
            'tahun_angkatan' => json_encode($request->tahun_angkatan),
            'nama_kegiatan' => $request->nama_kegiatan,
            'minimal_sks' => $request->minimal_sks,
            'minimal_sks' => $request->minimal_sks,
            'maksimal_nilai_d' => $request->maksimal_nilai_d,
            'kode_program_studi' => json_encode($request->kode_program_studi),
            'tipe' => $request->tipe,
            'id_bipot' => $request->nama_biaya,
            'biaya_pendaftaran' => $request->biaya_pendaftaran,
            'kelas_perkuliahan_id' => $request->kelas_perkuliahan_id,
        ]);

        return redirect()
            ->route($this->modul . '.index')
            ->with('success', 'Data berhasil disimpan');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id, DataService $dataService)
    {
        try {
            $id = Crypt::decrypt($id);
            $d['data'] = KegiatanMahasiswa::findOrFail($id);
            $d['tahun_angkatan'] = Mahasiswa::distinct()->pluck('tahun_angkatan');
            $d['prodi'] = Prodi::orderBy('nama_program_studi_idn')->get();
            $d['bipot'] = $dataService->bipot();
            $d['kelas_perkuliahan'] = DB::table('master_kelas_perkuliahan')->get();
            return view('kegiatan-mahasiswa.form', $d);
        } catch (DecryptException $e) {
            return redirect()
                ->route($this->modul . '.index')
                ->with('error', 'ID tidak valid.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $id = Crypt::decrypt($id);

        $request->validate([
            'tahun_angkatan' => 'required|array',
            'kode_program_studi' => 'required|array',
            'tipe' => 'required',
            'minimal_semester' => 'required',
            'minimal_sks' => 'required',
            'maksimal_nilai_d' => 'required',
            'nama_kegiatan' => 'required|string|max:255',
            'nama_biaya' => 'required',
            'biaya_pendaftaran' => 'required',
            'kelas_perkuliahan_id' => 'required',

        ]);

        KegiatanMahasiswa::where('id', $id)->update([
            'tahun_angkatan' => json_encode($request->tahun_angkatan),
            'nama_kegiatan' => $request->nama_kegiatan,
            'minimal_semester' => $request->minimal_semester,
            'minimal_sks' => $request->minimal_sks,
            'maksimal_nilai_d' => $request->maksimal_nilai_d,
            'kode_program_studi' => json_encode($request->kode_program_studi),
            'tipe' => $request->tipe,
            'id_bipot' => $request->nama_biaya,
            'biaya_pendaftaran' => $request->biaya_pendaftaran,
            'kelas_perkuliahan_id' => $request->kelas_perkuliahan_id,
        ]);

        return redirect()->route($this->modul . '.index')
            ->with('success', 'Data berhasil diupdate');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $id = Crypt::decrypt($id);

            $data = KegiatanMahasiswa::findOrFail($id);
            $data->delete();

            return redirect()
                ->route($this->modul . '.index')
                ->with('success', 'Data berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()
                ->route($this->modul . '.index')
                ->with('error', 'Data gagal dihapus');
        }
    }
}
