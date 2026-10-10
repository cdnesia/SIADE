<?php

namespace App\Http\Controllers;

use App\Services\PenmaruMahasiswaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pencarian seluruh pendaftar PMB (sumber data sama dengan Generate NPM),
 * tanpa memperhatikan status pembayaran. NPM bisa diterbitkan manual per pendaftar.
 */
class PencarianPendaftarController extends Controller
{
    private $modul = 'mahasiswa-baru.pencarian';

    public const MIN_KATA_KUNCI = 3;

    public const MAKS_HASIL = 100;

    public function __construct(protected PenmaruMahasiswaService $penmaru)
    {
        view()->share('modul', $this->modul);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        try {
            $d['daftar_tahun'] = $this->penmaru->daftarTahun();
            $d['prodi'] = $this->penmaru->daftarProdi();
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', '✕ Gagal memuat data. Periksa koneksi Anda.');
        }

        $d['tahun'] = $d['daftar_tahun']->contains($request->query('tahun'))
            ? $request->query('tahun')
            : MahasiswaBaruController::TAHUN_FILTER;
        $d['q'] = $q;
        $d['pendaftar'] = collect();
        $d['terpotong'] = false;

        // Pencarian baru dijalankan jika kata kunci cukup panjang, agar tidak memuat seluruh data sekaligus
        if (mb_strlen($q) < self::MIN_KATA_KUNCI) {
            return view('mahasiswa-baru.pencarian', $d);
        }

        try {
            $hasil = DB::connection('penmaru_old')->table('pmb_prodi')
                ->where('na', 'N')
                ->whereRaw('LEFT(pmb_gelombang, 8) = ?', [$d['tahun']])
                ->whereNotIn('pmb', MahasiswaBaruController::EXCLUDE)
                ->where(function ($w) use ($q) {
                    $like = '%' . $q . '%';
                    $w->where('nama_daftar', 'like', $like)
                        ->orWhere('pmb', 'like', $like)
                        ->orWhere('nim', 'like', $like)
                        ->orWhere('hp_daftar', 'like', $like)
                        ->orWhere('email', 'like', $like);
                })
                ->orderBy('nama_daftar')
                ->limit(self::MAKS_HASIL * 3)
                ->get([
                    // nomor = id pmb_prodi (dipakai import ke master), nomor_pmb = pmb.nomor (dipakai generate NPM)
                    'nomor', 'nomor_pmb', 'pmb', 'nim', 'nama_daftar', 'prodi', 'nama_kelas', 'pmb_gelombang',
                    'nama_jalur', 'gelombang', 'sekolah_asal', 'hp_daftar', 'email',
                ])
                ->unique('pmb')
                ->values();
        } catch (\Exception $e) {
            session()->now('error', '✕ Gagal memuat data. Periksa koneksi Anda.');
            return view('mahasiswa-baru.pencarian', $d);
        }

        $d['terpotong'] = $hasil->count() > self::MAKS_HASIL;
        $hasil = $hasil->take(self::MAKS_HASIL);

        // Tandai yang NIM-nya sudah ada di master_mahasiswa lokal
        $diMaster = DB::table('master_mahasiswa')
            ->whereIn('npm', $hasil->pluck('nim')->filter())
            ->pluck('npm')
            ->flip();
        foreach ($hasil as $p) {
            $p->di_master = !empty($p->nim) && isset($diMaster[$p->nim]);
        }

        $d['pendaftar'] = $hasil;

        return view('mahasiswa-baru.pencarian', $d);
    }

    /**
     * Terbitkan NPM untuk pendaftar yang belum memiliki NIM, tanpa cek pembayaran.
     * Pendaftar yang sudah ber-NIM tidak diubah dari sini (gunakan menu Sinkron Mahasiswa).
     */
    public function generateNim($nomor)
    {
        if (!ctype_digit((string) $nomor)) {
            return back()->with('error', '✕ Data pendaftar tidak ditemukan.');
        }

        $pmb = $this->penmaru->pendaftar((int) $nomor);
        if (!$pmb || $pmb->na !== 'N' || in_array($pmb->pmb, MahasiswaBaruController::EXCLUDE)) {
            return back()->with('error', '✕ Data pendaftar tidak ditemukan.');
        }

        if (!empty($pmb->nim)) {
            return back()->with('error', "✕ Pendaftar sudah memiliki NIM {$pmb->nim}.");
        }

        try {
            $nimBaru = $this->penmaru->terbitkanNim($pmb->nomor);
        } catch (\RuntimeException $e) {
            return back()->with('error', '✕ Gagal menerbitkan NPM. ' . $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', '✕ Gagal memperbarui data. Coba lagi.');
        }

        return back()->with('success', "✓ NPM {$nimBaru} berhasil diterbitkan untuk {$pmb->nama}");
    }
}
