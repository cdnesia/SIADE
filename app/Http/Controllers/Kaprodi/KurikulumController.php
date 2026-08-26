<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\Kurikulum;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class KurikulumController extends Controller
{
    private $modul = 'kaprodi.kurikulum';

    public function __construct()
    {
        view()->share('modul', $this->modul);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $d['kurikulum'] = Kurikulum::orderBy('nama_kurikulum')->get();
        return view('kaprodi.kurikulum.view', $d);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $d['data'] = null;
        return view('kaprodi.kurikulum.form', $d);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'kode_kurikulum' => 'required|string|max:255',
            'nama_kurikulum' => 'required|string|max:255',
            'status' => 'required|in:A,N',
            'keterangan' => 'nullable|string',
        ]);

        Kurikulum::create($request->only([
            'kode_kurikulum',
            'nama_kurikulum',
            'status',
            'keterangan',
        ]));

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
            $d['data'] = Kurikulum::findOrFail($id);
            return view('kaprodi.kurikulum.form', $d);
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
            'kode_kurikulum' => 'required|string|max:255',
            'nama_kurikulum' => 'required|string|max:255',
            'status' => 'required|in:A,N',
            'keterangan' => 'nullable|string',
        ]);

        Kurikulum::where('id', $id)->update($request->only([
            'kode_kurikulum',
            'nama_kurikulum',
            'status',
            'keterangan',
        ]));

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
            $data = Kurikulum::findOrFail($id);
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
