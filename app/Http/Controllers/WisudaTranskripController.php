<?php

namespace App\Http\Controllers;

use App\Models\SkalaNilai;
use App\Models\TahunAkademik;
use App\Services\KurikulumFeederService;
use App\Services\NilaiPddiktiService;
use App\Services\TranskripWisudaService;
use Illuminate\Http\Request;

class WisudaTranskripController extends Controller
{
    public function __construct(
        protected TranskripWisudaService $transkrip,
        protected KurikulumFeederService $kurikulumFeeder,
        protected NilaiPddiktiService $nilaiPddikti,
    ) {}

    /**
     * Form cek NPM sekaligus hasil transkrip.
     * Query: npm, angkatan (default dari data mahasiswa), kurikulum (id kurikulum Feeder,
     * kosong = otomatis sesuai angkatan), segarkan=1 (ambil ulang dari Neo Feeder).
     */
    public function index(Request $request)
    {
        $npm = trim((string) $request->query('npm', ''));

        $d['npm'] = $npm;
        $d['data'] = null;
        $d['tidak_ditemukan'] = false;
        $d['daftar_kurikulum'] = collect();
        $d['pilihan_angkatan'] = [];
        $d['angkatan'] = null;
        $d['kurikulum_dipilih'] = '';
        $d['kurikulum_otomatis'] = null;
        $d['galat_feeder'] = null;
        $d['url_rekap'] = null;

        if ($npm === '') {
            return view('wisuda.transkrip', $d);
        }

        $data = $this->transkrip->susun($npm);
        if (! $data) {
            $d['tidak_ditemukan'] = true;

            return view('wisuda.transkrip', $d);
        }

        $mhs = $data['mahasiswa'];
        $angkatanMhs = (string) $mhs->tahun_angkatan;
        $angkatan = preg_match('/^\d{5}$/', (string) $request->query('angkatan')) ? $request->query('angkatan') : $angkatanMhs;
        $d['angkatan'] = $angkatan;
        $d['angkatan_mahasiswa'] = $angkatanMhs;

        try {
            $kur = $this->kurikulumFeeder->tentukan(
                $mhs->kode_program_studi, $angkatan, $request->query('kurikulum'), $request->boolean('segarkan')
            );
            $d['daftar_kurikulum'] = $kur['daftar'];
            $d['kurikulum_otomatis'] = $kur['otomatis'];
            $d['kurikulum_dipilih'] = $kur['manual'] ? $kur['dipakai']['id_kurikulum'] : '';

            if ($kur['dipakai']) {
                $data = $this->transkrip->cocokkanKurikulum($data, $kur['matakuliah'], $kur['dipakai']);
            } else {
                $d['galat_feeder'] = 'Kurikulum untuk prodi ini tidak ditemukan di Neo Feeder.';
            }
        } catch (\Throwable $e) {
            report($e);
            $d['galat_feeder'] = 'Gagal memuat kurikulum dari Neo Feeder. Transkrip tetap ditampilkan tanpa pencocokan kurikulum. Alasan: '.$e->getMessage();
        }

        // Untuk tambah KRS: MK yang tersedia di master SIADE dan periode yang bisa dipilih
        $d['mk_lokal'] = $this->transkrip->matakuliahLokal($mhs->kode_program_studi, $angkatan);
        $d['pilihan_periode'] = TahunAkademik::where('kode_tahun_akademik', '>=', $angkatanMhs)
            ->orderByDesc('kode_tahun_akademik')
            ->pluck('nama_tahun_akademik', 'kode_tahun_akademik');
        $d['skala_nilai'] = SkalaNilai::where('kode_program_studi', $mhs->kode_program_studi)
            ->orderByDesc('nilai_mulai')
            ->get(['nama', 'bobot', 'nilai_mulai', 'nilai_sampai', 'lulus']);

        // Link kembali ke rekap per angkatan (hanya parameter filter rekap yang dikenal)
        parse_str((string) $request->query('rekap', ''), $rekap);
        $rekap = array_intersect_key($rekap, array_flip(['prodi', 'angkatan', 'nim', 'kurikulum', 'status', 'cari', 'per_halaman', 'page']));
        $d['rekap'] = $request->query('rekap');
        $d['url_rekap'] = $rekap ? route('wisuda.rekap.index', $rekap) : null;

        // Pilihan angkatan: dari tahun kurikulum tertua s.d. tahun ini (semester ganjil),
        // ditambah angkatan asli mahasiswa jika belum ada di daftar.
        $tahunAwal = (int) substr($d['daftar_kurikulum']->first()['id_semester'] ?? $angkatanMhs, 0, 4);
        $tahunAwal = min($tahunAwal, (int) substr($angkatanMhs, 0, 4));
        $pilihan = [];
        for ($t = (int) date('Y'); $t >= $tahunAwal; $t--) {
            $pilihan[$t.'1'] = (string) $t;
        }
        $pilihan[$angkatanMhs] ??= substr($angkatanMhs, 0, 4).' (genap)';
        krsort($pilihan);
        $d['pilihan_angkatan'] = $pilihan;

        $d['data'] = $data;

        return view('wisuda.transkrip', $d);
    }

    /**
     * Simpan matakuliah mana yang diambil dari satu grup dobel (AJAX).
     */
    public function pilih(Request $request)
    {
        $valid = $request->validate([
            'npm' => 'required|string|max:30',
            'krs_id' => 'required|integer',
        ]);

        try {
            $berhasil = $this->transkrip->pilih($valid['npm'], (int) $valid['krs_id'], $request->user()?->id);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Gagal memperbarui data. Coba lagi.'], 500);
        }

        if (! $berhasil) {
            return response()->json(['success' => false, 'message' => 'Matakuliah bukan bagian dari grup dobel mahasiswa ini.'], 422);
        }

        $data = $this->transkrip->susun($valid['npm']);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui',
            'ringkasan' => $data['ringkasan'],
            'baris' => $data['baris']->map(fn ($b) => [
                'krs_id' => $b['krs_id'],
                'diambil' => $b['diambil'],
                'dihitung' => $b['dihitung'],
            ])->values(),
            'grup' => collect($data['grup'])->map(fn ($g) => [
                'krs_ids' => $g['krs_ids'],
                'sudah_dipilih' => $g['sudah_dipilih'],
            ])->values(),
        ]);
    }

    /**
     * Hapus permanen satu matakuliah dobel dari KRS mahasiswa.
     */
    public function hapus(Request $request)
    {
        $valid = $request->validate([
            'npm' => 'required|string|max:30',
            'krs_id' => 'required|integer',
        ]);

        try {
            $alasan = $this->transkrip->hapus($valid['npm'], (int) $valid['krs_id'], $request->user()?->id);
        } catch (\Throwable $e) {
            report($e);

            return $this->balas($request, false, '✕ Gagal menghapus data. Coba lagi.', 500);
        }

        return $alasan !== null
            ? $this->balas($request, false, '✕ Gagal menghapus data. '.$alasan)
            : $this->balas($request, true, '✓ Data berhasil dihapus');
    }

    /**
     * Isi nilai untuk KRS yang nilainya masih kosong.
     */
    public function nilai(Request $request)
    {
        $valid = $request->validate([
            'npm' => 'required|string|max:30',
            'krs_id' => 'required|integer',
            'nilai_angka' => 'required|numeric|min:0|max:100',
        ]);

        try {
            $alasan = $this->transkrip->simpanNilai($valid['npm'], (int) $valid['krs_id'], (float) $valid['nilai_angka'], $request->user()?->id);
        } catch (\Throwable $e) {
            report($e);

            return $this->balas($request, false, '✕ Gagal memperbarui data. Coba lagi.', 500);
        }

        return $alasan
            ? $this->balas($request, false, '✕ Gagal memperbarui data. '.$alasan)
            : $this->balas($request, true, '✓ Data berhasil diperbarui');
    }

    /**
     * Tambah KRS untuk matakuliah kurikulum yang belum diambil (nilai opsional).
     */
    public function krs(Request $request)
    {
        $valid = $request->validate([
            'npm' => 'required|string|max:30',
            'kode_mk' => 'required|string|max:30',
            'periode' => 'required|string|max:10',
            'nilai_angka' => 'nullable|numeric|min:0|max:100',
            'angkatan' => 'nullable|digits:5',
        ]);

        try {
            $alasan = $this->transkrip->tambahKrs(
                $valid['npm'],
                $valid['kode_mk'],
                $valid['periode'],
                isset($valid['nilai_angka']) ? (float) $valid['nilai_angka'] : null,
                $valid['angkatan'] ?? '',
            );
        } catch (\Throwable $e) {
            report($e);

            return $this->balas($request, false, '✕ Gagal menambahkan data. Coba lagi.', 500);
        }

        return $alasan
            ? $this->balas($request, false, '✕ Gagal menambahkan data. '.$alasan)
            : $this->balas($request, true, '✓ Data berhasil ditambahkan');
    }

    /**
     * Nilai mahasiswa di PDDikti (Neo Feeder), per kode MK, untuk dibandingkan (AJAX).
     * Query: npm, segarkan=1 (abaikan cache).
     */
    public function pddikti(Request $request)
    {
        $valid = $request->validate(['npm' => 'required|string|max:30']);

        try {
            $nilai = $this->nilaiPddikti->nilaiMahasiswa($valid['npm'], $request->boolean('segarkan'));
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => '✕ Gagal memuat data dari PDDikti. Periksa koneksi Anda. ('.$e->getMessage().')',
            ], 502);
        }

        return response()->json(['success' => true, 'nilai' => $nilai]);
    }

    /**
     * Salin satu nilai PDDikti ke KRS SIADE (timpa KRS yang ada atau buat KRS baru).
     */
    public function pddiktiSimpan(Request $request)
    {
        $valid = $request->validate([
            'npm' => 'required|string|max:30',
            'kode_mk' => 'required|string|max:30',
            'periode' => 'required|string|max:10',
            'krs_id' => 'nullable|integer',
            'angkatan' => 'nullable|digits:5',
        ]);

        try {
            // Nilai diambil ulang dari server (cache), bukan dari isian browser
            $baris = $this->nilaiPddikti->baris($valid['npm'], $valid['kode_mk'], $valid['periode']);
            if (! $baris) {
                return $this->balas($request, false, '✕ Nilai '.$valid['kode_mk'].' periode '.$valid['periode'].' tidak ditemukan di PDDikti.');
            }

            $alasan = $this->transkrip->sinkronPddikti(
                $valid['npm'], $valid['kode_mk'], $valid['krs_id'] ?? null, $baris, $valid['angkatan'] ?? '', $request->user()?->id
            );
        } catch (\Throwable $e) {
            report($e);

            return $this->balas($request, false, '✕ Gagal memperbarui data. Coba lagi.', 500);
        }

        if ($alasan) {
            return $this->balas($request, false, '✕ Gagal memperbarui data. '.$alasan);
        }

        return $this->balas($request, true, empty($valid['krs_id'])
            ? '✓ Data berhasil ditambahkan dari PDDikti'
            : '✓ Data berhasil diperbarui dari PDDikti');
    }

    /**
     * Hapus semua pilihan dobel milik NPM sehingga kembali ke pilihan otomatis.
     */
    public function reset(Request $request)
    {
        $valid = $request->validate(['npm' => 'required|string|max:30']);

        try {
            $this->transkrip->reset($valid['npm']);
        } catch (\Throwable $e) {
            report($e);

            return $this->balas($request, false, '✕ Gagal menghapus data. Coba lagi.', 500);
        }

        return $this->balas($request, true, '✓ Data berhasil dihapus — pilihan dobel dikembalikan ke otomatis');
    }

    /**
     * Jawaban aksi: JSON untuk AJAX, redirect + flash untuk form biasa (cadangan tanpa JS).
     */
    private function balas(Request $request, bool $berhasil, string $pesan, int $statusGagal = 422)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $berhasil, 'message' => $pesan], $berhasil ? 200 : $statusGagal);
        }

        return redirect()->route('wisuda.transkrip.index', $this->parameterKembali($request))
            ->with($berhasil ? 'success' : 'error', $pesan);
    }

    /**
     * Parameter halaman (NPM + filter angkatan/kurikulum) untuk redirect kembali.
     */
    private function parameterKembali(Request $request): array
    {
        return array_filter($request->only(['npm', 'angkatan', 'kurikulum', 'rekap']), fn ($v) => $v !== null && $v !== '');
    }
}
