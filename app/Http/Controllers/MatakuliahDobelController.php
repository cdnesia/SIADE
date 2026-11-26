<?php

namespace App\Http\Controllers;

use App\Models\Prodi;
use App\Models\SkalaNilai;
use App\Services\MatakuliahDobelService;
use Illuminate\Http\Request;

/**
 * Menu Perkuliahan › MK Dobel Mahasiswa: daftar mahasiswa yang mengambil
 * matakuliah yang sama lebih dari sekali, beserta update nilai & hapus KRS dobel.
 */
class MatakuliahDobelController extends Controller
{
    public function __construct(protected MatakuliahDobelService $dobel) {}

    /**
     * Query: prodi, angkatan, cari, per_halaman, page.
     */
    public function index(Request $request)
    {
        $jumlahProdi = $this->dobel->jumlahPerProdi();
        $prodi = $jumlahProdi->has($request->query('prodi')) ? (string) $request->query('prodi') : null;
        $jumlahAngkatan = $this->dobel->jumlahPerAngkatan($prodi);
        $angkatan = $jumlahAngkatan->has($request->query('angkatan')) ? (string) $request->query('angkatan') : null;
        $cari = trim((string) $request->query('cari', ''));
        $perHalaman = in_array((int) $request->query('per_halaman'), [10, 20, 50, 100], true) ? (int) $request->query('per_halaman') : 10;

        try {
            $hasil = $this->dobel->daftar($prodi, $angkatan, $cari, $perHalaman);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', '✕ Gagal memuat data. Periksa koneksi Anda.');
        }

        // Skala nilai prodi yang tampil di halaman ini, untuk pratinjau huruf saat update nilai
        $skala = SkalaNilai::whereIn('kode_program_studi', $hasil->pluck('kode_program_studi')->unique())
            ->orderByDesc('nilai_mulai')
            ->get(['kode_program_studi', 'nama', 'bobot', 'nilai_mulai', 'nilai_sampai', 'lulus'])
            ->groupBy('kode_program_studi');

        return view('perkuliahan.mk-dobel', [
            'hasil' => $hasil,
            'prodi' => $prodi,
            'angkatan' => $angkatan,
            'cari' => $cari,
            'per_halaman' => $perHalaman,
            'daftar_prodi' => Prodi::whereIn('kode_program_studi', $jumlahProdi->keys())
                ->orderBy('nama_program_studi_idn')
                ->get(['kode_program_studi', 'nama_program_studi_idn']),
            'jumlah_prodi' => $jumlahProdi,
            'jumlah_angkatan' => $jumlahAngkatan,
            'skala_nilai' => $skala,
        ]);
    }

    public function update(Request $request, int $krs)
    {
        $valid = $request->validate(['nilai_angka' => 'required|numeric|min:0|max:100']);

        try {
            $alasan = $this->dobel->update($krs, (float) $valid['nilai_angka'], $request->user()?->id);
        } catch (\Throwable $e) {
            report($e);

            return $this->balas($request, false, '✕ Gagal memperbarui data. Coba lagi.', 500);
        }

        return $alasan
            ? $this->balas($request, false, '✕ Gagal memperbarui data. '.$alasan)
            : $this->balas($request, true, '✓ Data berhasil diperbarui');
    }

    public function destroy(Request $request, int $krs)
    {
        try {
            $alasan = $this->dobel->hapus($krs, $request->user()?->id);
        } catch (\Throwable $e) {
            report($e);

            return $this->balas($request, false, '✕ Gagal menghapus data. Coba lagi.', 500);
        }

        return $alasan
            ? $this->balas($request, false, '✕ Gagal menghapus data. '.$alasan)
            : $this->balas($request, true, '✓ Data berhasil dihapus');
    }

    /**
     * JSON untuk AJAX, redirect + flash untuk form biasa.
     */
    private function balas(Request $request, bool $berhasil, string $pesan, int $statusGagal = 422)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $berhasil, 'message' => $pesan], $berhasil ? 200 : $statusGagal);
        }

        return back()->with($berhasil ? 'success' : 'error', $pesan);
    }
}
