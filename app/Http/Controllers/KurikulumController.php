<?php

namespace App\Http\Controllers;

use App\Models\Kurikulum;
use App\Models\KurikulumMataKuliah;
use App\Models\KurikulumProdi;
use App\Models\Prodi;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KurikulumController extends Controller
{
    private $modul = 'kurikulum';

    public function __construct()
    {
        view()->share('modul', $this->modul);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $d['kurikulum'] = Kurikulum::withCount([
            'kurikulumProdi' => fn($q) => $q->where('status', 'A'),
            'mataKuliah',
        ])->orderBy('nama_kurikulum')->get();
        return view('kurikulum.view', $d);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $d = $this->formData(null);
        $d['data'] = null;
        return view('kurikulum.form', $d);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validateRequest($request);

        try {
            DB::transaction(function () use ($request) {
                $kurikulum = Kurikulum::create($request->only([
                    'kode_kurikulum',
                    'nama_kurikulum',
                    'status',
                    'keterangan',
                ]));
                $this->simpanProdi($kurikulum, $request);
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal menambahkan data. Coba lagi.');
        }

        return redirect()
            ->route($this->modul . '.index')
            ->with('success', 'Data berhasil ditambahkan');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $data = Kurikulum::findOrFail($id);
        } catch (DecryptException $e) {
            return redirect()
                ->route($this->modul . '.index')
                ->with('error', 'ID tidak valid.');
        }

        $d = $this->formData($data);
        $d['data'] = $data;
        return view('kurikulum.form', $d);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $kurikulum = Kurikulum::findOrFail(Crypt::decrypt($id));
        } catch (DecryptException $e) {
            return redirect()
                ->route($this->modul . '.index')
                ->with('error', 'ID tidak valid.');
        }

        $this->validateRequest($request, $kurikulum);

        try {
            DB::transaction(function () use ($request, $kurikulum) {
                $kurikulum->update($request->only([
                    'kode_kurikulum',
                    'nama_kurikulum',
                    'status',
                    'keterangan',
                ]));
                $this->simpanProdi($kurikulum, $request);
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memperbarui data. Coba lagi.');
        }

        return redirect()
            ->route($this->modul . '.index')
            ->with('success', 'Data berhasil diperbarui');
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
                ->with('error', 'Gagal menghapus data. Coba lagi.');
        }
    }

    private function formData(?Kurikulum $kurikulum)
    {
        $d['prodi'] = Prodi::orderBy('nama_program_studi_idn')->get();

        // Prodi yang sudah masuk kurikulum beserta angkatannya
        $d['prodi_kurikulum'] = $kurikulum
            ? KurikulumProdi::where('kurikulum_id', $kurikulum->id)->where('status', 'A')->get()
                ->mapWithKeys(fn($p) => [$p->kode_program_studi => json_decode($p->tahun_angkatan, true) ?: []])
            : collect();

        $d['jumlah_mk'] = $kurikulum
            ? KurikulumMataKuliah::where('kurikulum_id', $kurikulum->id)
                ->selectRaw('kode_program_studi, count(*) as jumlah')
                ->groupBy('kode_program_studi')
                ->pluck('jumlah', 'kode_program_studi')
            : collect();

        // Pilihan angkatan dari tahun akademik semester ganjil (kode berakhiran 1)
        $d['angkatan'] = DB::table('master_tahun_akademik')
            ->where('kode_tahun_akademik', 'like', '%1')
            ->orderByDesc('kode_tahun_akademik')
            ->pluck('kode_tahun_akademik')
            ->map(fn($kode) => (int) $kode)
            ->merge($d['prodi_kurikulum']->flatten())
            ->unique()
            ->sortDesc()
            ->values();

        return $d;
    }

    private function validateRequest(Request $request, ?Kurikulum $kurikulum = null)
    {
        $request->validate([
            'kode_kurikulum' => ['required', 'string', 'max:20', Rule::unique('master_kurikulum')->ignore($kurikulum?->id)],
            'nama_kurikulum' => 'required|string|max:150',
            'status' => 'required|in:A,N',
            'keterangan' => 'nullable|string',
            'prodi' => 'nullable|array',
            'prodi.*' => 'exists:master_program_studi,kode_program_studi',
            'angkatan' => 'nullable|array',
            'angkatan.*' => 'nullable|array',
            'angkatan.*.*' => 'integer|digits:5',
        ], [
            'kode_kurikulum.unique' => 'Kode kurikulum sudah dipakai.',
        ]);

        // Satu angkatan pada satu prodi hanya boleh memakai satu kurikulum,
        // karena KRS/transfer nilai mencari kurikulum berdasarkan prodi + angkatan.
        $errors = [];
        foreach ($request->input('prodi', []) as $kode) {
            $angkatan = array_map('intval', $request->input("angkatan.$kode", []));
            if (!$angkatan) {
                continue;
            }

            $bentrok = KurikulumProdi::with('kurikulum')
                ->where('kode_program_studi', $kode)
                ->where('status', 'A')
                ->when($kurikulum, fn($q) => $q->where('kurikulum_id', '!=', $kurikulum->id))
                ->get()
                ->map(fn($p) => [
                    'kurikulum' => $p->kurikulum->nama_kurikulum ?? '-',
                    'angkatan' => array_intersect($angkatan, json_decode($p->tahun_angkatan, true) ?: []),
                ])
                ->filter(fn($p) => $p['angkatan']);

            foreach ($bentrok as $b) {
                $tahun = implode(', ', array_map(fn($a) => substr($a, 0, 4), $b['angkatan']));
                $errors["angkatan.$kode"] = "Angkatan {$tahun} sudah memakai kurikulum {$b['kurikulum']} untuk prodi ini.";
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Prodi dicentang disimpan/diperbarui, prodi yang tidak dicentang dikeluarkan dari kurikulum.
     */
    private function simpanProdi(Kurikulum $kurikulum, Request $request)
    {
        $dipilih = collect($request->input('prodi', []))->unique()->values();

        KurikulumProdi::where('kurikulum_id', $kurikulum->id)
            ->whereNotIn('kode_program_studi', $dipilih)
            ->delete();

        foreach ($dipilih as $kode) {
            $angkatan = collect($request->input("angkatan.$kode", []))
                ->map(fn($a) => (int) $a)
                ->unique()
                ->sortDesc()
                ->values()
                ->all();

            $row = KurikulumProdi::firstOrNew([
                'kurikulum_id' => $kurikulum->id,
                'kode_program_studi' => $kode,
            ]);
            $row->tahun_angkatan = json_encode($angkatan);
            $row->status = 'A';
            $row->save();
        }
    }
}
