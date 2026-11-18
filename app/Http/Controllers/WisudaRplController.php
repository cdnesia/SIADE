<?php

namespace App\Http\Controllers;

use App\Exports\WisudaRplExport;
use App\Models\Prodi;
use App\Services\KurikulumFeederService;
use App\Services\TranskripRplService;
use App\Services\TranskripWisudaService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Nilai konversi mahasiswa RPL dari Neo Feeder, dicocokkan dengan kurikulum Feeder
 * sesuai periode masuk. Matakuliah kurikulum yang belum dikonversi dibiarkan kosong
 * di export agar diisi manual di Excel. Hanya membaca — tidak ada data SIADE yang diubah.
 */
class WisudaRplController extends Controller
{
    /** Opsi filter status kelengkapan: kunci => label */
    private const FILTER = [
        'semua' => 'Semua status',
        'siap' => 'Lengkap (semua MK kurikulum sudah dikonversi)',
        'perlu-dicek' => 'Perlu diisi manual',
        'nilai-kosong' => 'Ada nilai konversi kosong',
        'mk-kurang' => 'Ada MK belum bernilai',
        'sks-kurang' => 'SKS konversi kurang dari syarat',
        'tidak-lulus' => 'Ada nilai tidak lulus',
    ];

    /** Opsi filter status mahasiswa di Feeder */
    private const FILTER_KELUAR = [
        'semua' => 'Semua mahasiswa',
        'belum-lulus' => 'Belum lulus',
        'lulus' => 'Sudah lulus',
        'keluar' => 'Keluar/mengundurkan diri',
    ];

    public function __construct(
        protected TranskripRplService $rpl,
        protected TranskripWisudaService $transkrip,
        protected KurikulumFeederService $kurikulumFeeder,
    ) {}

    public function index(Request $request)
    {
        $d = $this->hitung($request);
        if (isset($d['tersaring'])) {
            $halaman = max(1, (int) $request->query('page', 1));
            $d['hasil'] = new LengthAwarePaginator(
                $d['tersaring']->forPage($halaman, $d['per_halaman'])->values(),
                $d['tersaring']->count(),
                $d['per_halaman'],
                $halaman,
                ['path' => $request->url(), 'query' => $request->except('page', 'segarkan')]
            );
            unset($d['tersaring']);
        }

        return view('wisuda.rpl', $d);
    }

    /**
     * Export Excel sesuai prodi, periode masuk, NIM, filter, dan pencarian yang aktif.
     */
    public function export(Request $request)
    {
        $d = $this->hitung($request);
        if (empty($d['tersaring']) || $d['tersaring']->isEmpty()) {
            return redirect()->route('wisuda.rpl.index', $request->except('segarkan'))
                ->with('error', $d['galat_feeder'] ? '✕ Gagal memuat data. Periksa koneksi Anda.' : '✕ Tidak ada data mahasiswa untuk diexport.');
        }

        $namaProdi = $d['daftar_prodi']->firstWhere('kode_program_studi', $d['prodi'])->nama_program_studi_idn ?? $d['prodi'];
        $semua = $d['angkatan'] === 'semua';
        $labelAngkatan = $semua
            ? 'Semua periode masuk ('.collect($d['kurikulum_per_angkatan'])->keys()->map(fn ($a) => $this->labelPeriode($a))->implode(', ').')'
            : $this->labelPeriode($d['angkatan']);
        $kurikulum = ['nama_kurikulum' => collect($d['kurikulum_per_angkatan'])
            ->map(fn ($k, $a) => $this->labelPeriode($a).': '.($k['nama_kurikulum'] ?? 'tidak tersedia'))->implode('; ') ?: 'tidak tersedia'];

        $namaFile = 'Rekap-Transkrip-RPL-'.preg_replace('/[^A-Za-z0-9]+/', '-', $namaProdi).'-'
            .($semua ? 'Semua' : $d['angkatan']).'.xlsx';

        return Excel::download(new WisudaRplExport(
            $d['tersaring'],
            $kurikulum,
            $d['kurikulum_matakuliah'],
            ['prodi' => $namaProdi.' (RPL — nilai konversi)', 'angkatan' => $labelAngkatan, 'filter' => self::FILTER[$d['status']].' · '.self::FILTER_KELUAR[$d['keluar']], 'isi_manual' => true],
        ), $namaFile);
    }

    private function hitung(Request $request): array
    {
        $kodeProdi = (string) $request->query('prodi', '');
        $angkatan = preg_match('/^(\d{5}|semua)$/', (string) $request->query('angkatan')) ? $request->query('angkatan') : 'semua';
        $perHalaman = in_array((int) $request->query('per_halaman'), [10, 20, 50, 100], true) ? (int) $request->query('per_halaman') : 20;
        $filter = array_key_exists($request->query('status'), self::FILTER) ? $request->query('status') : 'semua';
        $keluar = array_key_exists($request->query('keluar'), self::FILTER_KELUAR) ? $request->query('keluar') : 'semua';
        $cari = trim((string) $request->query('cari', ''));
        // NIM opsional untuk menyaring; boleh dipisah baris baru, koma, titik koma, atau spasi
        $daftarNim = collect(preg_split('/[\s,;]+/', strtoupper((string) $request->query('nim', '')), -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn ($n) => preg_match('/^[0-9A-Z.\-]{1,30}$/', $n))->unique()->values();

        $d = [
            'daftar_prodi' => Prodi::orderBy('nama_program_studi_idn')->get(['kode_program_studi', 'nama_program_studi_idn']),
            'daftar_angkatan' => collect(),
            'prodi' => $kodeProdi,
            'angkatan' => $angkatan,
            'daftar_nim' => $daftarNim,
            'nim_teks' => $daftarNim->implode("\n"),
            'nim_tidak_ada' => [],
            'per_halaman' => $perHalaman,
            'status' => $filter,
            'keluar' => $keluar,
            'cari' => $cari,
            'opsi_filter' => self::FILTER,
            'opsi_keluar' => self::FILTER_KELUAR,
            'jumlah_per_filter' => array_fill_keys(array_keys(self::FILTER), 0),
            'jumlah_per_keluar' => array_fill_keys(array_keys(self::FILTER_KELUAR), 0),
            'kurikulum_per_angkatan' => [],
            'kurikulum_matakuliah' => collect(),
            'galat_feeder' => null,
            'hasil' => null,
        ];

        if ($kodeProdi === '') {
            return $d;
        }

        try {
            $data = $this->rpl->dataProdi($kodeProdi, $request->boolean('segarkan'));
        } catch (\Throwable $e) {
            report($e);
            $d['galat_feeder'] = 'Gagal memuat data RPL dari Neo Feeder. Alasan: '.$e->getMessage();

            return $d;
        }

        $semuaMhs = collect($data['mahasiswa']);
        $d['daftar_angkatan'] = $semuaMhs->countBy('angkatan')->sortKeysDesc();

        $mahasiswa = $semuaMhs
            ->filter(fn ($m) => $angkatan === 'semua' || $m['angkatan'] === $angkatan)
            ->filter(fn ($m) => $daftarNim->isEmpty() || $daftarNim->contains(strtoupper($m['npm'])))
            ->sortBy([['angkatan', 'asc'], ['npm', 'asc']])
            ->values();
        $d['nim_tidak_ada'] = $daftarNim->reject(fn ($n) => $mahasiswa->contains(fn ($m) => strtoupper($m['npm']) === $n))->values()->all();

        // Kurikulum Feeder per periode masuk
        $kurPerAngkatan = [];
        try {
            foreach ($mahasiswa->pluck('angkatan')->unique()->sort() as $angk) {
                $kurPerAngkatan[$angk] = $this->kurikulumFeeder->tentukan($kodeProdi, $angk, null, $request->boolean('segarkan') && empty($kurPerAngkatan));
            }
        } catch (\Throwable $e) {
            report($e);
            $kurPerAngkatan = [];
            $d['galat_feeder'] = 'Gagal memuat kurikulum dari Neo Feeder. Transkrip tetap ditampilkan tanpa pencocokan kurikulum. Alasan: '.$e->getMessage();
        }
        $d['kurikulum_per_angkatan'] = collect($kurPerAngkatan)->map(fn ($k) => $k['dipakai'])->all();
        $d['kurikulum_matakuliah'] = collect($kurPerAngkatan)->reduce(fn ($gabung, $k) => $gabung->union($k['matakuliah']), collect());

        $transkrip = $this->rpl->susun($data, $mahasiswa->all(), $kodeProdi);

        $baris = $mahasiswa->map(function ($m) use ($transkrip, $kurPerAngkatan) {
            $t = $transkrip[$m['id_registrasi']];
            $kur = $kurPerAngkatan[$m['angkatan']] ?? null;
            if ($kur && $kur['dipakai']) {
                $t = $this->transkrip->cocokkanKurikulum($t, $kur['matakuliah'], $kur['dipakai']);
            }
            $r = $t['ringkasan'];
            $adaKurikulum = isset($r['syarat_sks_lulus']);
            $masalah = array_keys(array_filter([
                'nilai-kosong' => $r['tanpa_nilai'] > 0,
                'mk-kurang' => $adaKurikulum && $r['mk_kurang'] > 0,
                'sks-kurang' => $adaKurikulum && $r['total_sks'] < $r['syarat_sks_lulus'],
                'tidak-lulus' => $r['tidak_lulus'] > 0,
            ]));

            return $m + [
                'ringkasan' => $r,
                'transkrip' => $t['baris'],
                'mk_belum' => collect($t['mk_belum_terpenuhi'] ?? [])->values(),
                'kurikulum' => $kur['dipakai'] ?? null,
                'kode_kurikulum' => $kur && $kur['dipakai'] ? $kur['matakuliah']->keys()->all() : null,
                // Semua MK kurikulum mahasiswa ini (export detail: baris kosong untuk diisi manual)
                'mk_kurikulum' => $kur && $kur['dipakai'] ? $kur['matakuliah']->values() : collect(),
                'masalah' => $masalah,
                'siap' => $adaKurikulum && empty($masalah),
            ];
        });

        foreach (array_keys(self::FILTER_KELUAR) as $k) {
            $d['jumlah_per_keluar'][$k] = $baris->filter(fn ($b) => $this->cocokKeluar($b, $k))->count();
        }
        $sesuaiKeluar = $baris->filter(fn ($b) => $this->cocokKeluar($b, $keluar));
        foreach (array_keys(self::FILTER) as $k) {
            $d['jumlah_per_filter'][$k] = $sesuaiKeluar->filter(fn ($b) => $this->cocokFilter($b, $k))->count();
        }

        $d['tersaring'] = $sesuaiKeluar
            ->filter(fn ($b) => $this->cocokFilter($b, $filter))
            ->filter(fn ($b) => $cari === '' || str_contains(strtolower($b['npm'].' '.$b['nama']), strtolower($cari)))
            ->values();
        $d['total_mahasiswa'] = $sesuaiKeluar->count();
        $d['ringkasan_rpl'] = $this->ringkasanSemua($sesuaiKeluar);

        return $d;
    }

    private function ringkasanSemua(Collection $baris): array
    {
        return [
            'siap' => $baris->where('siap', true)->count(),
            'lulus' => $baris->where('keterangan_keluar', 'Lulus')->count(),
            'rata_ipk' => round($baris->filter(fn ($b) => $b['ringkasan']['total_sks'] > 0)->avg(fn ($b) => $b['ringkasan']['ipk']) ?? 0, 2),
            'rata_sks_konversi' => round($baris->avg(fn ($b) => $b['ringkasan']['sks_konversi']) ?? 0),
            'mk_perlu_diisi' => $baris->sum(fn ($b) => $b['ringkasan']['mk_kurang'] ?? 0),
        ];
    }

    private function cocokFilter(array $b, string $filter): bool
    {
        return match ($filter) {
            'semua' => true,
            'siap' => $b['siap'],
            'perlu-dicek' => ! $b['siap'],
            default => in_array($filter, $b['masalah'], true),
        };
    }

    private function cocokKeluar(array $b, string $keluar): bool
    {
        return match ($keluar) {
            'semua' => true,
            'belum-lulus' => $b['keterangan_keluar'] === null,
            'lulus' => $b['keterangan_keluar'] === 'Lulus',
            'keluar' => $b['keterangan_keluar'] !== null && $b['keterangan_keluar'] !== 'Lulus',
        };
    }

    private function labelPeriode(string $kode): string
    {
        return substr($kode, 0, 4).(substr($kode, 4) === '2' ? ' Genap' : (substr($kode, 4) === '1' ? ' Ganjil' : ''));
    }
}
