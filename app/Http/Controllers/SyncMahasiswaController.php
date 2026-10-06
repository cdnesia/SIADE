<?php

namespace App\Http\Controllers;

use App\Models\Prodi;
use App\Services\PenmaruMahasiswaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Pratinjau mahasiswa baru dari penmaru_old sebelum dimasukkan ke master_mahasiswa.
 * Di sini kelas, prodi, dan jurusan bisa dikoreksi serta NIM bisa diterbitkan ulang.
 */
class SyncMahasiswaController extends Controller
{
    private $modul = 'mahasiswa.sync';

    public function __construct(protected PenmaruMahasiswaService $penmaru)
    {
        view()->share('modul', $this->modul);
    }

    public function index(Request $request)
    {
        try {
            $d['daftar_tahun'] = $this->penmaru->daftarTahun();
            $d['tahun'] = $d['daftar_tahun']->contains($request->query('tahun'))
                ? $request->query('tahun')
                : MahasiswaBaruController::TAHUN_FILTER;

            $pendaftar = $this->penmaru->pendaftarBerNim($d['tahun']);
            $d['prodi'] = $this->penmaru->daftarProdi();
            $d['kelas'] = $this->penmaru->daftarKelas();
            $d['jurusan'] = $this->penmaru->daftarJurusan();
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Gagal memuat data. Periksa koneksi Anda.');
        }

        // Tandai yang NIM-nya sudah ada di master_mahasiswa lokal
        $diMaster = DB::table('master_mahasiswa')
            ->whereIn('npm', $pendaftar->pluck('nim'))
            ->pluck('npm')
            ->flip();
        foreach ($pendaftar as $p) {
            $p->di_master = isset($diMaster[$p->nim]);
        }

        $d['ringkasan'] = [
            'total' => $pendaftar->count(),
            'di_master' => $pendaftar->where('di_master', true)->count(),
            'belum' => $pendaftar->where('di_master', false)->count(),
        ];

        // Filter
        $d['filter'] = $request->only(['prodi', 'kelas', 'status']);
        $d['pendaftar'] = $pendaftar
            ->when($request->filled('prodi'), fn($c) => $c->where('prodi', $request->prodi))
            ->when($request->filled('kelas'), fn($c) => $c->where('kelas', (int) $request->kelas))
            ->when($request->status === 'belum', fn($c) => $c->where('di_master', false))
            ->when($request->status === 'sudah', fn($c) => $c->where('di_master', true))
            ->values();

        $d['nama_prodi_lokal'] = Prodi::pluck('nama_program_studi_idn', 'kode_program_studi');

        return view('mahasiswa.sync', $d);
    }

    public function update(Request $request, $nomor)
    {
        $pmb = $this->cariPendaftar($nomor);
        if (!$pmb) {
            return back()->with('error', 'Data pendaftar tidak ditemukan.');
        }

        $prodi = $this->penmaru->daftarProdi();
        $kelas = $this->penmaru->daftarKelas();

        $validator = validator($request->all(), [
            'prodi' => ['required', Rule::in($prodi->keys())],
            'kelas' => ['required', Rule::in($kelas->keys())],
            'jurusan' => ['nullable', 'string', 'max:200'],
        ]);
        $validator->after(function ($v) use ($request, $kelas) {
            $k = $kelas->get((int) $request->kelas);
            if ($k && $request->prodi && !$this->penmaru->kelasTersediaUntukProdi($k, $request->prodi)) {
                $v->errors()->add('kelas', "Kelas {$k->nama} tidak dibuka untuk prodi ini.");
            }
        });
        if ($validator->fails()) {
            return back()->with('error', '⚠ Periksa kembali isian form Anda. ' . $validator->errors()->first());
        }

        $prodiBerubah = (string) $pmb->prodi !== (string) $request->prodi;
        $generate = $request->boolean('generate_nim') || $prodiBerubah;

        if ($generate && $this->sudahDiMaster($pmb->nim)) {
            return back()->with('error', "Gagal memperbarui data. NIM {$pmb->nim} sudah ada di master mahasiswa, prodi/NIM tidak bisa diubah dari sini.");
        }

        try {
            $hasil = $this->penmaru->perbarui($pmb->nomor, $request->only(['prodi', 'kelas', 'jurusan']), $generate);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui data. Coba lagi.');
        }

        $pesan = 'Data berhasil diperbarui';
        if ($hasil['nim_baru']) {
            $pesan .= ". NIM {$hasil['nim_lama']} → {$hasil['nim_baru']}";
        }

        return back()->with('success', $pesan);
    }

    public function import(Request $request)
    {
        $validator = validator($request->all(), [
            'nomor' => 'required|array|min:1',
            'nomor.*' => 'integer',
        ], [
            'nomor.required' => 'Pilih minimal satu mahasiswa.',
        ]);
        if ($validator->fails()) {
            return back()->with('error', '⚠ ' . $validator->errors()->first());
        }

        try {
            $hasil = $this->penmaru->masukkanKeMaster($request->input('nomor'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menambahkan data. Coba lagi.');
        }

        $pesan = "Data berhasil ditambahkan: {$hasil['ditambah']} mahasiswa masuk ke master";
        if ($hasil['dilewati']) {
            $pesan .= ', ' . count($hasil['dilewati']) . ' dilewati karena sudah ada';
        }

        return back()->with('success', $pesan);
    }

    public function generateNim($nomor)
    {
        $pmb = $this->cariPendaftar($nomor);
        if (!$pmb) {
            return back()->with('error', 'Data pendaftar tidak ditemukan.');
        }

        if ($this->sudahDiMaster($pmb->nim)) {
            return back()->with('error', "Gagal memperbarui data. NIM {$pmb->nim} sudah ada di master mahasiswa.");
        }

        try {
            $nimBaru = $this->penmaru->terbitkanNim($pmb->nomor);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui data. Coba lagi.');
        }

        return back()->with('success', "Data berhasil diperbarui. NIM {$pmb->nim} → {$nimBaru}");
    }

    /**
     * Hanya pendaftar ber-NIM yang tidak dikecualikan yang boleh diubah dari halaman ini.
     */
    private function cariPendaftar($nomor)
    {
        if (!ctype_digit((string) $nomor)) {
            return null;
        }

        $pmb = $this->penmaru->pendaftar((int) $nomor);
        if (!$pmb || $pmb->na !== 'N' || empty($pmb->nim) || in_array($pmb->pmb, MahasiswaBaruController::EXCLUDE)) {
            return null;
        }

        return $pmb;
    }

    private function sudahDiMaster(?string $nim): bool
    {
        return $nim && DB::table('master_mahasiswa')->where('npm', $nim)->exists();
    }
}
