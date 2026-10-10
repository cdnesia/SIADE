<?php

namespace App\Http\Controllers;

use App\Services\PenmaruMahasiswaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Daftar seluruh pendaftar PMB (sumber data sama dengan Generate NPM) dengan filter seperti Sinkron Mahasiswa,
 * tanpa memperhatikan status pembayaran. NPM bisa diterbitkan per pendaftar atau sekaligus untuk yang dicentang.
 */
class PencarianPendaftarController extends Controller
{
    private $modul = 'mahasiswa-baru.pencarian';

    public const MAKS_GENERATE = 500;

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

            $pendaftar = DB::connection('penmaru_old')->table('pmb_prodi')
                ->where('na', 'N')
                ->whereRaw('LEFT(pmb_gelombang, 8) = ?', [$d['tahun']])
                ->whereNotIn('pmb', MahasiswaBaruController::EXCLUDE)
                ->orderBy('nama_daftar')
                ->get([
                    // nomor = id pmb_prodi (dipakai import ke master), nomor_pmb = pmb.nomor (dipakai generate NPM)
                    'nomor', 'nomor_pmb', 'pmb', 'nim', 'nama_daftar', 'prodi', 'kelas', 'nama_kelas',
                    'nama_jalur', 'gelombang', 'sekolah_asal', 'hp_daftar', 'email',
                ])
                ->unique('pmb')
                ->values();

            $d['prodi'] = $this->penmaru->daftarProdi();
            $d['kelas'] = $this->penmaru->daftarKelas();
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', '✕ Gagal memuat data. Periksa koneksi Anda.');
        }

        // Tandai yang NIM-nya sudah ada di master_mahasiswa lokal
        $diMaster = DB::table('master_mahasiswa')
            ->whereIn('npm', $pendaftar->pluck('nim')->filter())
            ->pluck('npm')
            ->flip();
        foreach ($pendaftar as $p) {
            $p->di_master = !empty($p->nim) && isset($diMaster[$p->nim]);
            // NPM hanya bisa dibuat jika belum ber-NIM dan prodinya dikenal (kode prodi bagian dari NIM)
            $p->bisa_generate = empty($p->nim) && isset($d['prodi'][$p->prodi]);
        }

        $d['ringkasan'] = [
            'total' => $pendaftar->count(),
            'belum_nim' => $pendaftar->filter(fn($p) => empty($p->nim))->count(),
            'belum_master' => $pendaftar->filter(fn($p) => !empty($p->nim) && !$p->di_master)->count(),
            'sudah_master' => $pendaftar->where('di_master', true)->count(),
        ];

        // Filter
        $d['filter'] = $request->only(['prodi', 'kelas', 'status']);
        $d['pendaftar'] = $pendaftar
            ->when($request->filled('prodi'), fn($c) => $c->where('prodi', $request->prodi))
            ->when($request->filled('kelas'), fn($c) => $c->where('kelas', (int) $request->kelas))
            ->when($request->status === 'belum_nim', fn($c) => $c->filter(fn($p) => empty($p->nim)))
            ->when($request->status === 'belum_master', fn($c) => $c->filter(fn($p) => !empty($p->nim) && !$p->di_master))
            ->when($request->status === 'sudah_master', fn($c) => $c->where('di_master', true))
            ->values();

        return view('mahasiswa-baru.pencarian', $d);
    }

    /**
     * Terbitkan NPM untuk pendaftar terpilih (nomor = pmb.nomor) yang belum memiliki NIM, tanpa cek pembayaran.
     * Pendaftar yang sudah ber-NIM dilewati, tidak pernah ditimpa dari sini.
     */
    public function generateNim(Request $request)
    {
        $validator = validator($request->all(), [
            'nomor' => 'required|array|min:1|max:' . self::MAKS_GENERATE,
            'nomor.*' => 'integer',
        ], [
            'nomor.required' => 'Pilih minimal satu pendaftar.',
            'nomor.max' => 'Maksimal ' . self::MAKS_GENERATE . ' pendaftar sekali proses.',
        ]);
        if ($validator->fails()) {
            return back()->with('error', '⚠ ' . $validator->errors()->first());
        }

        try {
            // Diurutkan sesuai urutan daftar agar nomor urut NIM mengikuti urutan pendaftaran
            $daftar = DB::connection('penmaru_old')->table('pmb')
                ->whereIn('nomor', array_unique($request->input('nomor')))
                ->orderBy('nomor')
                ->get(['nomor', 'pmb', 'na', 'nim', 'nama']);
        } catch (\Exception $e) {
            return back()->with('error', '✕ Gagal memuat data. Periksa koneksi Anda.');
        }

        $berhasil = [];
        $gagal = [];
        foreach ($daftar as $pmb) {
            if ($pmb->na !== 'N' || in_array($pmb->pmb, MahasiswaBaruController::EXCLUDE) || !empty($pmb->nim)) {
                $gagal[] = $pmb->nama;
                continue;
            }

            try {
                $berhasil[] = $this->penmaru->terbitkanNim($pmb->nomor);
            } catch (\Exception $e) {
                $gagal[] = $pmb->nama;
            }
        }

        if (!$berhasil) {
            return back()->with('error', '✕ Gagal menerbitkan NPM. Pendaftar sudah ber-NIM atau prodinya tidak dikenal.');
        }

        $pesan = count($berhasil) === 1
            ? "✓ NPM {$berhasil[0]} berhasil diterbitkan"
            : '✓ ' . count($berhasil) . ' NPM berhasil diterbitkan';
        if ($gagal) {
            $pesan .= ', ' . count($gagal) . ' gagal/dilewati: ' . implode(', ', array_slice($gagal, 0, 5))
                . (count($gagal) > 5 ? ', ...' : '');
        }

        return back()->with('success', $pesan);
    }
}
