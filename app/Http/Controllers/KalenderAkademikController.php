<?php

namespace App\Http\Controllers;

use App\Models\KalenderAkademik;
use App\Models\Prodi;
use App\Models\TahunAkademik;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class KalenderAkademikController extends Controller
{
    private $modul = 'kalender-akademik';
    public function __construct()
    {

        view()->share('modul', $this->modul);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kalenders = KalenderAkademik::all();

        $allProdi = Prodi::all()->keyBy('kode_program_studi');

        foreach ($kalenders as $kalender) {
            $kodeProdi = $kalender->kode_program_studi
                ? json_decode($kalender->kode_program_studi, true)
                : [];

            $kalender->prodis = collect($kodeProdi)
                ->map(fn($kode) => $allProdi[$kode] ?? null)
                ->filter()
                ->values();
        }
        $d['kalender_akademik'] = $kalenders;
        return view('kalender-akademik.view', $d);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $d['tahun_akademik'] = TahunAkademik::orderBy('kode_tahun_akademik', 'DESC')->get();
        $d['prodi'] = Prodi::orderBy('nama_program_studi_idn')->get();
        $d['data'] = null;
        return view('kalender-akademik.form', $d);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'kode_tahun_akademik' => 'required',
            'nama_kegiatan' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keg_input_jadwal' => 'required|in:0,1',
            'keg_input_jadwal_sp' => 'required|in:0,1',
            'keg_kontrak_krs' => 'required|in:0,1',
            'keg_input_nilai' => 'required|in:0,1',
            'keg_pendaftaran_kkn' => 'required|in:0,1',
            'keg_pendaftaran_pkl' => 'required|in:0,1',
            'keg_pendaftaran_seminar_proposal' => 'required|in:0,1',
            'keg_pendaftaran_sidang_akhir' => 'required|in:0,1',
            'keg_pendaftaran_wisuda' => 'required|in:0,1',
        ]);

        KalenderAkademik::insert([
            'kode_tahun_akademik' => $request->kode_tahun_akademik,
            'nama_kegiatan' => $request->nama_kegiatan,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'kode_program_studi' => json_encode($request->kode_program_studi),
            'keg_input_jadwal' => $request->keg_input_jadwal,
            'keg_input_jadwal_sp' => $request->keg_input_jadwal_sp,
            'keg_kontrak_krs' => $request->keg_kontrak_krs,
            'keg_input_nilai' => $request->keg_input_nilai,
            'keg_pendaftaran_kkn' => $request->keg_pendaftaran_kkn,
            'keg_pendaftaran_pkl' => $request->keg_pendaftaran_pkl,
            'keg_pendaftaran_seminar_proposal' => $request->keg_pendaftaran_seminar_proposal,
            'keg_pendaftaran_sidang_akhir' => $request->keg_pendaftaran_sidang_akhir,
            'keg_pendaftaran_wisuda' => $request->keg_pendaftaran_wisuda,
        ]);

        return redirect()
            ->route($this->modul . '.index')
            ->with('success', 'Data berhasil disimpan');
    }

    /**
     * Display the specified resource.
     */
    public function show(KalenderAkademik $kalenderAkademik)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $d['data'] = KalenderAkademik::findOrFail($id);
            $d['tahun_akademik'] = TahunAkademik::orderBy('kode_tahun_akademik', 'DESC')->get();
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
            'kode_tahun_akademik' => 'required',
            'nama_kegiatan' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keg_input_jadwal' => 'required|in:0,1',
            'keg_input_jadwal_sp' => 'required|in:0,1',
            'keg_kontrak_krs' => 'required|in:0,1',
            'keg_input_nilai' => 'required|in:0,1',
            'keg_pendaftaran_kkn' => 'required|in:0,1',
            'keg_pendaftaran_pkl' => 'required|in:0,1',
            'keg_pendaftaran_seminar_proposal' => 'required|in:0,1',
            'keg_pendaftaran_sidang_akhir' => 'required|in:0,1',
            'keg_pendaftaran_wisuda' => 'required|in:0,1',
        ]);

        $data = KalenderAkademik::findOrFail($id);

        $data->kode_tahun_akademik = $request->kode_tahun_akademik;
        $data->nama_kegiatan = $request->nama_kegiatan;
        $data->tanggal_mulai = $request->tanggal_mulai;
        $data->tanggal_selesai = $request->tanggal_selesai;
        $data->kode_program_studi = json_encode($request->kode_program_studi);
        $data->keg_input_jadwal = $request->keg_input_jadwal;
        $data->keg_input_jadwal_sp = $request->keg_input_jadwal_sp;
        $data->keg_kontrak_krs = $request->keg_kontrak_krs;
        $data->keg_input_nilai = $request->keg_input_nilai;
        $data->keg_pendaftaran_kkn = $request->keg_pendaftaran_kkn;
        $data->keg_pendaftaran_pkl = $request->keg_pendaftaran_pkl;
        $data->keg_pendaftaran_seminar_proposal = $request->keg_pendaftaran_seminar_proposal;
        $data->keg_pendaftaran_sidang_akhir = $request->keg_pendaftaran_sidang_akhir;
        $data->keg_pendaftaran_wisuda = $request->keg_pendaftaran_wisuda;

        $data->save();

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

            $data = KalenderAkademik::findOrFail($id);
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
