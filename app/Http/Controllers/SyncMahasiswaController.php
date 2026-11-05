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

        // Cocokkan dengan master_mahasiswa SIADE (NPM sama, bentrok, atau orang sama dengan NPM lain)
        $this->penmaru->cocokkanDenganMaster($pendaftar, $d['tahun']);
        foreach ($pendaftar as $p) {
            $p->di_master = $p->status_master === 'sudah';
        }

        $d['ringkasan'] = [
            'total' => $pendaftar->count(),
            'di_master' => $pendaftar->where('status_master', 'sudah')->count(),
            'belum' => $pendaftar->where('status_master', 'belum')->count(),
            'cek' => $pendaftar->whereIn('status_master', ['bentrok', 'beda_nim'])->count(),
        ];

        // Filter
        $d['filter'] = $request->only(['prodi', 'kelas', 'status']);
        $d['pendaftar'] = $pendaftar
            ->when($request->filled('prodi'), fn($c) => $c->where('prodi', $request->prodi))
            ->when($request->filled('kelas'), fn($c) => $c->where('kelas', (int) $request->kelas))
            ->when($request->status === 'belum', fn($c) => $c->where('status_master', 'belum'))
            ->when($request->status === 'sudah', fn($c) => $c->where('status_master', 'sudah'))
            ->when($request->status === 'cek', fn($c) => $c->whereIn('status_master', ['bentrok', 'beda_nim']))
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
            'nim' => ['nullable', 'regex:/^[A-Za-z0-9]{5,20}$/'],
        ], [
            'nim.regex' => 'NIM hanya boleh huruf dan angka, 5–20 karakter.',
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

        if ($this->terkunci($pmb)) {
            return back()->with('error', "Gagal memperbarui data. NIM {$pmb->nim} sudah ada di master mahasiswa atas nama pendaftar ini.");
        }

        $prodiBerubah = (string) $pmb->prodi !== (string) $request->prodi;
        $generate = $request->boolean('generate_nim') || $prodiBerubah;

        // NIM manual hanya dipakai bila tidak generate ulang
        $nimManual = strtoupper(trim((string) $request->nim));
        $nimManual = !$generate && $nimManual !== '' && $nimManual !== $pmb->nim ? $nimManual : null;
        if ($nimManual && ($pesanNim = $this->cekNimManual($pmb, $nimManual))) {
            return back()->with('error', '⚠ Periksa kembali isian form Anda. ' . $pesanNim);
        }

        try {
            $hasil = $this->penmaru->perbarui($pmb->nomor, $request->only(['prodi', 'kelas', 'jurusan']), $generate);
            if ($nimManual) {
                $this->penmaru->ubahNim($pmb->nomor, $nimManual);
                $hasil = ['nim_lama' => $pmb->nim, 'nim_baru' => $nimManual];
            }
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

        if ($this->terkunci($pmb)) {
            return back()->with('error', "Gagal memperbarui data. NIM {$pmb->nim} sudah ada di master mahasiswa atas nama pendaftar ini.");
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

    /**
     * Terkunci jika NIM penmaru sudah ada di master dan dipakai oleh orang yang sama.
     * NIM yang bentrok dengan mahasiswa lain tetap boleh diubah agar bisa diperbaiki.
     */
    private function terkunci($pmb): bool
    {
        $master = $pmb->nim ? DB::table('master_mahasiswa')->where('npm', $pmb->nim)->first() : null;

        return $master && $this->penmaru->orangSama($pmb, $master);
    }

    /**
     * Validasi NIM yang diketik manual. Mengembalikan pesan error, atau null jika boleh dipakai.
     * NIM yang sudah ada di master hanya boleh dipakai jika milik orang yang sama (menyamakan dengan SIADE).
     */
    private function cekNimManual($pmb, string $nim): ?string
    {
        if ($this->penmaru->nimDipakaiPendaftarLain($nim, $pmb->nomor)) {
            return "NIM {$nim} sudah dipakai pendaftar lain di PMB.";
        }

        $master = DB::table('master_mahasiswa')->where('npm', $nim)->first();
        if ($master && !$this->penmaru->orangSama($pmb, $master)) {
            return "NIM {$nim} sudah dipakai {$master->nama_mahasiswa} di master mahasiswa.";
        }

        return null;
    }
}
