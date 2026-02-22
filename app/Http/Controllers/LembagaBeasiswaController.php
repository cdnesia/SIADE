<?php

namespace App\Http\Controllers;

use App\Models\LembagaBeasiswa;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class LembagaBeasiswaController extends Controller
{
    private $modul = 'lembaga-beasiswa';
    public function __construct()
    {

        view()->share('modul', $this->modul);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $d['lembaga'] = LembagaBeasiswa::all();
        return view($this->modul . '.view', $d);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $d['data'] = null;
        return view($this->modul . '.form', $d);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_beasiswa' => 'required|string|max:255',
            'nama_lembaga' => 'required|string|max:255',
        ]);

        LembagaBeasiswa::insert([
            'nama_beasiswa' => $request->nama_beasiswa,
            'nama_lembaga' => $request->nama_lembaga,
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
            $d['data'] = LembagaBeasiswa::findOrFail($id);
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
            'nama_beasiswa' => 'required|string|max:255',
            'nama_lembaga' => 'required|string|max:255',
        ]);
        LembagaBeasiswa::where('id', $id)->update([
            'nama_beasiswa' => $request->nama_beasiswa,
            'nama_lembaga' => $request->nama_lembaga,
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

            $data = LembagaBeasiswa::findOrFail($id);
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
