<?php

namespace App\Http\Controllers;

use App\Models\Prodi;
use App\Models\TahunAkademik;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class TahunAkademikController extends Controller
{
    private $modul = 'tahun-akademik';
    public function __construct()
    {

        view()->share('modul', $this->modul);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $datas = TahunAkademik::all();
        $allProdi = Prodi::all()->keyBy('kode_program_studi');
        foreach ($datas as $item) {
            $kodeProdi = $item->kode_program_studi
                ? json_decode($item->kode_program_studi, true)
                : [];
            $item->prodis = collect($kodeProdi)
                ->map(fn($kode) => $allProdi[$kode] ?? null)
                ->filter()
                ->values();
        }

        $d['tahun_akademik'] = $datas;
        return view('tahun-akademik.view', $d);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $d['prodi'] = Prodi::orderBy('nama_program_studi_idn')->get();
        $d['data'] = null;
        return view('tahun-akademik.form', $d);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'kode_tahun_akademik' => [
                'required',
                'regex:/^\d{4}[123]$/'
            ],
            'kode_program_studi' => 'required|array',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status' => 'required'
        ]);

        $kode = $request->kode_tahun_akademik;

        $tahun = substr($kode, 0, 4);
        $semester = substr($kode, -1);
        $map = [
            '1' => 'Ganjil',
            '2' => 'Genap',
            '3' => 'Pendek',
        ];

        $nama = $tahun . ' ' . $map[$semester];

        TahunAkademik::insert([
            'kode_tahun_akademik' => $request->kode_tahun_akademik,
            'nama_tahun_akademik' => $nama,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'kode_program_studi' => json_encode($request->kode_program_studi),
            'status' => $request->status
        ]);

        return redirect()
            ->route($this->modul . '.index')
            ->with('success', 'Data berhasil disimpan');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $d['data'] = TahunAkademik::findOrFail($id);
            $d['prodi'] = Prodi::orderBy('nama_program_studi_idn')->get();
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
    public function update(Request $request, $id)
    {
        $id = Crypt::decrypt($id);
        $request->validate([
            'kode_tahun_akademik' => [
                'required',
                'regex:/^\d{4}[123]$/'
            ],
            'kode_program_studi' => 'required|array',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status' => 'required'

        ]);

        $kode = $request->kode_tahun_akademik;

        $tahun = substr($kode, 0, 4);
        $semester = substr($kode, -1);
        $map = [
            '1' => 'Ganjil',
            '2' => 'Genap',
            '3' => 'Pendek',
        ];

        $nama = $tahun . ' ' . $map[$semester];

        TahunAkademik::where('id', $id)->update([
            'kode_tahun_akademik' => $request->kode_tahun_akademik,
            'nama_tahun_akademik' => $nama,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'kode_program_studi' => json_encode($request->kode_program_studi),
            'status' => $request->status
        ]);

        return redirect()
            ->route($this->modul . '.index')
            ->with('success', 'Data berhasil diupdate');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $data = TahunAkademik::findOrFail($id);
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
