<?php

namespace App\Http\Controllers;

use App\Models\LembagaBeasiswa;
use App\Models\Mahasiswa;
use App\Models\PenerimaBeasiswa;
use App\Models\TahunAkademik;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class PenerimaBeasiswaController extends Controller
{
    private $modul = 'penerima-beasiswa';
    public function __construct()
    {

        view()->share('modul', $this->modul);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $allMahasiswa = Mahasiswa::with('prodi')->get()->keyBy('npm');
        $allLembaga = LembagaBeasiswa::pluck('nama_lembaga', 'id');
        $penerima = PenerimaBeasiswa::orderBy('npm', 'DESC')->get()->map(function ($item) {
            $tahun = json_decode($item->tahun_akademik, true);
            if (is_array($tahun)) {
                rsort($tahun);
            }
            $item->tahun_akademik_array = json_encode($tahun);
            return $item;
        });
        foreach ($penerima as $item) {
            $item->nama_lembaga = $allLembaga[$item->id_lembaga] ?? null;
            $item->nama_mahasiswa = $allMahasiswa[$item->npm]->nama_mahasiswa ?? null;
            $item->program_studi = $allMahasiswa[$item->npm]->prodi->nama_program_studi_idn ?? null;
        }
        $d['penerima'] = $penerima;
        return view($this->modul . '.view', $d);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $d['mahasiswa'] = Mahasiswa::pluck('nama_mahasiswa', 'npm');
        $d['lembaga'] = LembagaBeasiswa::pluck('nama_lembaga', 'id');
        $d['tahun_akademik'] = TahunAkademik::all();
        $d['data'] = null;
        return view($this->modul . '.form', $d);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tahun_akademik' => 'required|array',
            'lembaga' => 'required',
            'jumlah_jaminan' => 'required',
            'npm' => 'required|string|max:255',
        ]);

        PenerimaBeasiswa::insert([
            'npm' => $request->npm,
            'id_lembaga' => $request->lembaga,
            'tahun_akademik' => json_encode($request->tahun_akademik),
            'jumlah_jaminan' => $request->jumlah_jaminan,
        ]);

        return redirect()
            ->route($this->modul . '.index')
            ->with('success', 'Data berhasil disimpan');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $id = Crypt::decrypt($id);
            $d['mahasiswa'] = Mahasiswa::pluck('nama_mahasiswa', 'npm');
            $d['lembaga'] = LembagaBeasiswa::pluck('nama_lembaga', 'id');
            $d['tahun_akademik'] = TahunAkademik::all();
            $d['data'] = PenerimaBeasiswa::findOrFail($id);
            return view($this->modul . '.form', $d);
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
            'tahun_akademik' => 'required|array',
            'lembaga' => 'required',
            'jumlah_jaminan' => 'required',
            'npm' => 'required|string|max:255',
        ]);
        PenerimaBeasiswa::where('id', $id)->update([
            'npm' => $request->npm,
            'id_lembaga' => $request->lembaga,
            'tahun_akademik' => json_encode($request->tahun_akademik),
            'jumlah_jaminan' => $request->jumlah_jaminan,
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

            $data = PenerimaBeasiswa::findOrFail($id);
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
