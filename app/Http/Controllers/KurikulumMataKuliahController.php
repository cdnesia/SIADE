<?php

namespace App\Http\Controllers;

use App\Models\Kurikulum;
use App\Models\KurikulumMataKuliah;
use App\Models\KurikulumMataKuliahPertemuan;
use App\Models\KurikulumProdi;
use App\Models\Prodi;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class KurikulumMataKuliahController extends Controller
{
    private $modul = 'kurikulum.matakuliah';

    public function __construct()
    {
        view()->share('modul', $this->modul);
    }

    /**
     * Daftar mata kuliah dalam kurikulum, difilter per program studi.
     */
    public function index(Request $request, $kurikulum)
    {
        $kurikulum = $this->findKurikulum($kurikulum);
        if (!$kurikulum) {
            return $this->invalidId();
        }

        $jumlahMk = KurikulumMataKuliah::where('kurikulum_id', $kurikulum->id)
            ->selectRaw('kode_program_studi, count(*) as jumlah')
            ->groupBy('kode_program_studi')
            ->pluck('jumlah', 'kode_program_studi');

        $d['prodi'] = $this->prodiKurikulum($kurikulum)
            ->each(fn($p) => $p->jumlah_mk = (int) ($jumlahMk[$p->kode_program_studi] ?? 0));

        // Prodi tidak valid/kosong -> pakai prodi pertama yang sudah punya mata kuliah
        $kode = $request->query('prodi');
        if (!$d['prodi']->contains('kode_program_studi', $kode)) {
            $kode = ($d['prodi']->firstWhere('jumlah_mk', '>', 0) ?? $d['prodi']->first())->kode_program_studi ?? null;
        }

        $d['kurikulum'] = $kurikulum;
        $d['kode_program_studi'] = $kode;

        $d['matakuliah'] = KurikulumMataKuliah::where('kurikulum_id', $kurikulum->id)
            ->where('kode_program_studi', $d['kode_program_studi'])
            ->orderBy('semester')
            ->orderBy('kode_mata_kuliah')
            ->withSum('pertemuanNilai as total_bobot', 'bobot')
            ->get();

        $d['tipe'] = DB::table('master_tipe_matakuliah')->pluck('nama', 'id');
        $d['jenis'] = DB::table('master_jenis_matakuliah')->pluck('nama', 'id');

        return view('kurikulum.matakuliah.view', $d);
    }

    /**
     * Form tambah mata kuliah.
     */
    public function create(Request $request, $kurikulum)
    {
        $kurikulum = $this->findKurikulum($kurikulum);
        if (!$kurikulum) {
            return $this->invalidId();
        }

        $d = $this->formData($kurikulum, $request->query('prodi'));
        $d['data'] = null;

        return view('kurikulum.matakuliah.form', $d);
    }

    /**
     * Simpan mata kuliah baru.
     */
    public function store(Request $request, $kurikulum)
    {
        $kurikulum = $this->findKurikulum($kurikulum);
        if (!$kurikulum) {
            return $this->invalidId();
        }

        $data = $this->validated($request, $kurikulum);

        try {
            KurikulumMataKuliah::create($data);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal menambahkan data. Coba lagi.');
        }

        return redirect()
            ->route($this->modul . '.index', [Crypt::encrypt($kurikulum->id), 'prodi' => $data['kode_program_studi']])
            ->with('success', 'Data berhasil ditambahkan');
    }

    /**
     * Form edit mata kuliah.
     */
    public function edit($kurikulum, $matakuliah)
    {
        $kurikulum = $this->findKurikulum($kurikulum);
        $data = $kurikulum ? $this->findMataKuliah($kurikulum, $matakuliah) : null;
        if (!$data) {
            return $this->invalidId();
        }

        $d = $this->formData($kurikulum, $data->kode_program_studi);
        $d['data'] = $data;

        return view('kurikulum.matakuliah.form', $d);
    }

    /**
     * Perbarui mata kuliah.
     */
    public function update(Request $request, $kurikulum, $matakuliah)
    {
        $kurikulum = $this->findKurikulum($kurikulum);
        $mk = $kurikulum ? $this->findMataKuliah($kurikulum, $matakuliah) : null;
        if (!$mk) {
            return $this->invalidId();
        }

        $data = $this->validated($request, $kurikulum, $mk);

        try {
            $mk->update($data);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memperbarui data. Coba lagi.');
        }

        return redirect()
            ->route($this->modul . '.index', [Crypt::encrypt($kurikulum->id), 'prodi' => $data['kode_program_studi']])
            ->with('success', 'Data berhasil diperbarui');
    }

    /**
     * Hapus mata kuliah, ditolak jika sudah dipakai di jadwal atau KRS.
     */
    public function destroy($kurikulum, $matakuliah)
    {
        $kurikulum = $this->findKurikulum($kurikulum);
        $mk = $kurikulum ? $this->findMataKuliah($kurikulum, $matakuliah) : null;
        if (!$mk) {
            return $this->invalidId();
        }

        $redirect = redirect()->route($this->modul . '.index', [
            Crypt::encrypt($kurikulum->id),
            'prodi' => $mk->kode_program_studi,
        ]);

        $dipakai = DB::table('tbl_jadwal_perkuliahan')->where('mata_kuliah_id', $mk->id)->exists()
            || DB::table('tbl_mahasiswa_krs')->where('mata_kuliah_id', $mk->id)->exists();

        if ($dipakai) {
            return $redirect->with('error', 'Gagal menghapus data. Mata kuliah sudah dipakai di jadwal/KRS, ubah status menjadi Tidak Aktif.');
        }

        try {
            $mk->delete();
        } catch (\Exception $e) {
            return $redirect->with('error', 'Gagal menghapus data. Coba lagi.');
        }

        return $redirect->with('success', 'Data berhasil dihapus');
    }

    /**
     * Form setting bobot penilaian per pertemuan.
     */
    public function bobot($kurikulum, $matakuliah)
    {
        $kurikulum = $this->findKurikulum($kurikulum);
        $mk = $kurikulum ? $this->findMataKuliah($kurikulum, $matakuliah) : null;
        if (!$mk) {
            return $this->invalidId();
        }

        $setting = $mk->pertemuanNilai->keyBy('pertemuan');

        // Tampilkan pertemuan 1..max_pertemuan (atau lebih jika sudah ada setting di atasnya)
        $jumlah = max((int) $mk->max_pertemuan, (int) $setting->keys()->max());

        $d['kurikulum'] = $kurikulum;
        $d['data'] = $mk;
        $d['jumlah_pertemuan'] = $jumlah;
        $d['setting'] = $setting;
        $d['jenis'] = KurikulumMataKuliahPertemuan::JENIS;
        $d['jumlah_jadwal'] = DB::table('tbl_jadwal_perkuliahan')->where('mata_kuliah_id', $mk->id)->count();

        return view('kurikulum.matakuliah.bobot', $d);
    }

    /**
     * Simpan bobot penilaian per pertemuan. Total bobot wajib 100%, atau semua 0 untuk mengosongkan.
     */
    public function bobotUpdate(Request $request, $kurikulum, $matakuliah)
    {
        $kurikulum = $this->findKurikulum($kurikulum);
        $mk = $kurikulum ? $this->findMataKuliah($kurikulum, $matakuliah) : null;
        if (!$mk) {
            return $this->invalidId();
        }

        $request->validate([
            'pertemuan' => 'required|array|min:1|max:255',
            'pertemuan.*.jenis_penilaian' => ['required', Rule::in(array_keys(KurikulumMataKuliahPertemuan::JENIS))],
            'pertemuan.*.bobot' => 'nullable|numeric|min:0|max:100',
            'pertemuan.*.keterangan' => 'nullable|string|max:255',
        ], [], [
            'pertemuan.*.jenis_penilaian' => 'jenis penilaian',
            'pertemuan.*.bobot' => 'bobot',
            'pertemuan.*.keterangan' => 'keterangan',
        ]);

        $rows = [];
        $errors = [];
        foreach ($request->input('pertemuan') as $no => $item) {
            $no = (int) $no;
            if ($no < 1 || $no > 255) {
                continue;
            }
            $bobot = round((float) ($item['bobot'] ?? 0), 2);
            $jenis = $item['jenis_penilaian'];

            if ($jenis === KurikulumMataKuliahPertemuan::TIDAK_DINILAI && $bobot > 0) {
                $errors["pertemuan.$no.bobot"] = "Pertemuan $no: pilih jenis penilaian jika bobot lebih dari 0.";
            }
            if ($jenis !== KurikulumMataKuliahPertemuan::TIDAK_DINILAI && $bobot <= 0) {
                $errors["pertemuan.$no.bobot"] = "Pertemuan $no: isi bobot untuk jenis penilaian yang dipilih.";
            }

            $rows[] = [
                'mata_kuliah_id' => $mk->id,
                'pertemuan' => $no,
                'jenis_penilaian' => $jenis,
                'bobot' => $bobot,
                'keterangan' => trim((string) ($item['keterangan'] ?? '')) ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $total = round(array_sum(array_column($rows, 'bobot')), 2);
        if ($total != 0 && $total != 100) {
            $errors['total'] = "Total bobot harus 100% (saat ini {$total}%).";
        }

        // Penilaian OBE hanya bisa aktif jika bobot per pertemuan sudah lengkap 100%
        $obe = $request->boolean('is_penilaian_obe');
        if ($obe && $total != 100) {
            $errors['is_penilaian_obe'] = 'Penilaian OBE hanya bisa diaktifkan jika total bobot 100%.';
        }

        if ($errors) {
            return back()->withInput()->withErrors($errors)->with('error', 'Periksa kembali isian form Anda.');
        }

        try {
            DB::transaction(function () use ($mk, $rows, $total, $obe) {
                KurikulumMataKuliahPertemuan::where('mata_kuliah_id', $mk->id)->delete();
                // Total 0 = setting dikosongkan
                if ($total > 0) {
                    KurikulumMataKuliahPertemuan::insert($rows);
                }
                $mk->update(['is_penilaian_obe' => $obe]);
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memperbarui data. Coba lagi.');
        }

        return redirect()
            ->route($this->modul . '.index', [Crypt::encrypt($kurikulum->id), 'prodi' => $mk->kode_program_studi])
            ->with('success', 'Data berhasil diperbarui');
    }

    private function findKurikulum($id)
    {
        try {
            return Kurikulum::find(Crypt::decrypt($id));
        } catch (DecryptException $e) {
            return null;
        }
    }

    private function findMataKuliah(Kurikulum $kurikulum, $id)
    {
        try {
            return KurikulumMataKuliah::where('kurikulum_id', $kurikulum->id)->find(Crypt::decrypt($id));
        } catch (DecryptException $e) {
            return null;
        }
    }

    private function invalidId()
    {
        return redirect()
            ->route('kurikulum.index')
            ->with('error', 'ID tidak valid.');
    }

    /**
     * Hanya prodi yang masuk kurikulum (diatur di form Edit Kurikulum) yang bisa dipilih.
     */
    private function prodiKurikulum(Kurikulum $kurikulum)
    {
        $kode = KurikulumProdi::where('kurikulum_id', $kurikulum->id)
            ->where('status', 'A')
            ->pluck('kode_program_studi');

        return Prodi::whereIn('kode_program_studi', $kode)
            ->orderBy('nama_program_studi_idn')
            ->get();
    }

    private function formData(Kurikulum $kurikulum, $kodeProdi)
    {
        $d['kurikulum'] = $kurikulum;
        $d['prodi'] = $this->prodiKurikulum($kurikulum);
        $d['kode_program_studi'] = $kodeProdi;
        $d['tipe'] = DB::table('master_tipe_matakuliah')->orderBy('id')->get();
        $d['jenis'] = DB::table('master_jenis_matakuliah')->where('status', 'A')->orderBy('id')->get();

        // Kandidat prasyarat: mata kuliah lain dalam kurikulum yang sama (semua prodi kurikulum ini)
        $d['kandidat_prasyarat'] = KurikulumMataKuliah::where('kurikulum_id', $kurikulum->id)
            ->orderBy('kode_program_studi')
            ->orderBy('semester')
            ->orderBy('kode_mata_kuliah')
            ->get(['id', 'kode_program_studi', 'kode_mata_kuliah', 'nama_mata_kuliah_idn', 'semester']);

        return $d;
    }

    private function validated(Request $request, Kurikulum $kurikulum, ?KurikulumMataKuliah $mk = null)
    {
        $request->validate([
            'kode_program_studi' => ['required', Rule::in($this->prodiKurikulum($kurikulum)->pluck('kode_program_studi'))],
            'kode_mata_kuliah' => [
                'required',
                'string',
                'max:20',
                Rule::unique('master_kurikulum_matakuliah')
                    ->where('kurikulum_id', $kurikulum->id)
                    ->where('kode_program_studi', $request->kode_program_studi)
                    ->ignore($mk?->id),
            ],
            'nama_mata_kuliah_idn' => 'required|string|max:200',
            'nama_mata_kuliah_eng' => 'nullable|string|max:200',
            'mata_kuliah_tipe' => 'required|integer|exists:master_tipe_matakuliah,id',
            'jenis_matakuliah_id' => 'nullable|integer|exists:master_jenis_matakuliah,id',
            'semester' => 'required|integer|min:1|max:14',
            'sks_tatap_muka' => 'required|integer|min:0|max:24',
            'sks_praktek' => 'required|integer|min:0|max:24',
            'sks_prak_lap' => 'required|integer|min:0|max:24',
            'sks_simulasi' => 'required|integer|min:0|max:24',
            'min_pertemuan' => 'required|integer|min:1|max:255',
            'max_pertemuan' => 'required|integer|min:1|max:255|gte:min_pertemuan',
            'status' => 'required|in:A,N',
            'prasyarat_lulus' => 'nullable|array',
            'prasyarat_lulus.*' => [
                'integer',
                Rule::exists('master_kurikulum_matakuliah', 'id')->where('kurikulum_id', $kurikulum->id),
                Rule::notIn(array_filter([$mk?->id])),
            ],
        ], [
            'kode_mata_kuliah.unique' => 'Kode mata kuliah sudah ada pada kurikulum dan prodi ini.',
            'kode_program_studi.in' => 'Program studi tidak terdaftar pada kurikulum ini.',
            'max_pertemuan.gte' => 'Maksimal pertemuan harus lebih besar atau sama dengan minimal pertemuan.',
        ]);

        $data = $request->only([
            'kode_program_studi',
            'kode_mata_kuliah',
            'nama_mata_kuliah_idn',
            'nama_mata_kuliah_eng',
            'mata_kuliah_tipe',
            'jenis_matakuliah_id',
            'semester',
            'sks_tatap_muka',
            'sks_praktek',
            'sks_prak_lap',
            'sks_simulasi',
            'min_pertemuan',
            'max_pertemuan',
            'status',
        ]);

        $data['kurikulum_id'] = $kurikulum->id;
        $data['kode_mata_kuliah'] = strtoupper(trim($data['kode_mata_kuliah']));
        // Total SKS selalu dihitung dari komponennya agar konsisten
        $data['sks_mata_kuliah'] = (int) $data['sks_tatap_muka'] + (int) $data['sks_praktek']
            + (int) $data['sks_prak_lap'] + (int) $data['sks_simulasi'];
        $data['is_mbkm'] = $request->boolean('is_mbkm');
        $data['is_mk_universitas'] = $request->boolean('is_mk_universitas');
        $data['prasyarat_lulus'] = array_values(array_map('intval', $request->input('prasyarat_lulus', [])));

        return $data;
    }
}
