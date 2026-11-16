<?php

namespace App\Http\Controllers;

use App\Exports\WisudaRekapExport;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\SkalaNilai;
use App\Models\TahunAkademik;
use App\Services\KurikulumFeederService;
use App\Services\TranskripWisudaService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Cek transkrip & IPK banyak mahasiswa sekaligus per prodi per angkatan.
 * Nilai dari SIADE, kurikulum pembanding dari Neo Feeder.
 */
class WisudaRekapController extends Controller
{
    /** Opsi filter status: kunci => label */
    private const FILTER = [
        'semua' => 'Semua status',
        'siap' => 'Siap wisuda',
        'perlu-dicek' => 'Perlu dicek',
        'nilai-kosong' => 'Ada nilai kosong',
        'dobel' => 'Dobel belum dipilih',
        'wajib-belum' => 'MK wajib belum terpenuhi',
        'sks-kurang' => 'SKS kurang',
        'tidak-lulus' => 'Ada nilai tidak lulus',
    ];

    public function __construct(
        protected TranskripWisudaService $transkrip,
        protected KurikulumFeederService $kurikulumFeeder,
    ) {}

    public function index(Request $request)
    {
        $d = $this->hitung($request);
        if (! isset($d['tersaring'])) {
            return view('wisuda.rekap', $d);
        }

        $tersaring = $d['tersaring'];
        unset($d['tersaring']);
        $perHalaman = $d['per_halaman'];
        $halaman = max(1, (int) $request->query('page', 1));
        $d['hasil'] = new LengthAwarePaginator(
            $tersaring->forPage($halaman, $perHalaman)->values(),
            $tersaring->count(),
            $perHalaman,
            $halaman,
            ['path' => $request->url(), 'query' => $request->except('page', 'segarkan')]
        );

        $d['skala_nilai'] = SkalaNilai::where('kode_program_studi', $d['prodi'])
            ->orderByDesc('nilai_mulai')
            ->get(['nama', 'bobot', 'nilai_mulai', 'nilai_sampai', 'lulus']);
        $d['pilihan_periode'] = TahunAkademik::where('kode_tahun_akademik', '>=', $d['angkatan'])
            ->orderByDesc('kode_tahun_akademik')
            ->pluck('nama_tahun_akademik', 'kode_tahun_akademik');

        return view('wisuda.rekap', $d);
    }

    /**
     * Export Excel: satu baris per mahasiswa, kode MK memanjang ke kanan.
     * Mengikuti NIM, kurikulum, filter status, dan pencarian yang sedang aktif.
     */
    public function export(Request $request)
    {
        $d = $this->hitung($request);
        if (! isset($d['tersaring']) || $d['tersaring']->isEmpty()) {
            return redirect()->route('wisuda.rekap.index', $request->query())
                ->with('error', '✕ Tidak ada data mahasiswa untuk diexport.');
        }

        $namaProdi = $d['daftar_prodi']->firstWhere('kode_program_studi', $d['prodi'])->nama_program_studi_idn ?? $d['prodi'];
        $namaFile = 'Rekap-Transkrip-'.preg_replace('/[^A-Za-z0-9]+/', '-', $namaProdi).'-'.substr($d['angkatan'], 0, 4).'.xlsx';

        return Excel::download(new WisudaRekapExport(
            $d['tersaring'],
            $d['kurikulum'],
            $d['kurikulum_matakuliah'],
            ['prodi' => $namaProdi, 'angkatan' => substr($d['angkatan'], 0, 4), 'filter' => self::FILTER[$d['status']]],
        ), $namaFile);
    }

    /**
     * Hitung rekap untuk prodi + angkatan + daftar NIM pada request.
     * 'tersaring' hanya ada jika semua parameter wajib terisi.
     */
    private function hitung(Request $request): array
    {
        $kodeProdi = (string) $request->query('prodi', '');
        $angkatan = preg_match('/^\d{5}$/', (string) $request->query('angkatan')) ? $request->query('angkatan') : '';
        $perHalaman = in_array((int) $request->query('per_halaman'), [10, 20, 50, 100], true) ? (int) $request->query('per_halaman') : 20;
        $filter = array_key_exists($request->query('status'), self::FILTER) ? $request->query('status') : 'semua';
        $cari = trim((string) $request->query('cari', ''));
        // Daftar NIM yang akan dicek: boleh dipisah baris baru, koma, titik koma, atau spasi
        $daftarNim = collect(preg_split('/[\s,;]+/', (string) $request->query('nim', ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($n) => trim($n))
            ->filter(fn ($n) => preg_match('/^[0-9A-Za-z.\-]{1,30}$/', $n))
            ->unique()
            ->values();

        $d = [
            'daftar_prodi' => Prodi::orderBy('nama_program_studi_idn')->get(['kode_program_studi', 'nama_program_studi_idn']),
            'daftar_angkatan' => collect(),
            'prodi' => $kodeProdi,
            'angkatan' => $angkatan,
            'daftar_nim' => $daftarNim,
            'nim_teks' => $daftarNim->implode("\n"),
            'nim_tidak_ada' => [],
            'nim_beda' => [],
            'per_halaman' => $perHalaman,
            'status' => $filter,
            'cari' => $cari,
            'opsi_filter' => self::FILTER,
            'jumlah_per_filter' => array_fill_keys(array_keys(self::FILTER), 0),
            'kurikulum' => null,
            'daftar_kurikulum' => collect(),
            'kurikulum_dipilih' => '',
            'galat_feeder' => null,
            'hasil' => null,
            'kurikulum_matakuliah' => collect(),
        ];

        if ($kodeProdi !== '') {
            $d['daftar_angkatan'] = Mahasiswa::where('kode_program_studi', $kodeProdi)
                ->selectRaw('tahun_angkatan, COUNT(*) as jumlah')
                ->groupBy('tahun_angkatan')
                ->orderByDesc('tahun_angkatan')
                ->pluck('jumlah', 'tahun_angkatan');
        }

        if ($kodeProdi === '' || $angkatan === '' || $daftarNim->isEmpty()) {
            return $d;
        }

        // Pisahkan NIM: cocok prodi+angkatan (dicek), beda prodi/angkatan, dan tidak terdaftar
        $ditemukan = Mahasiswa::with('prodi')->whereIn('npm', $daftarNim)->get()->keyBy('npm');
        $mahasiswa = $ditemukan
            ->filter(fn ($m) => $m->kode_program_studi === $kodeProdi && (string) $m->tahun_angkatan === $angkatan)
            ->sortBy(fn ($m) => $daftarNim->search($m->npm))
            ->values();
        $d['nim_tidak_ada'] = $daftarNim->reject(fn ($n) => $ditemukan->has($n))->values()->all();
        $d['nim_beda'] = $ditemukan->reject(fn ($m) => $mahasiswa->contains('npm', $m->npm))
            ->map(fn ($m) => [
                'npm' => $m->npm,
                'nama' => $m->nama_mahasiswa,
                'prodi' => $m->prodi->nama_program_studi_idn ?? $m->kode_program_studi,
                'angkatan' => substr((string) $m->tahun_angkatan, 0, 4),
            ])->values()->all();

        $transkrip = $this->transkrip->susunBanyak($mahasiswa);

        $kur = null;
        try {
            $kur = $this->kurikulumFeeder->tentukan($kodeProdi, $angkatan, $request->query('kurikulum'), $request->boolean('segarkan'));
            $d['daftar_kurikulum'] = $kur['daftar'];
            $d['kurikulum'] = $kur['dipakai'];
            $d['kurikulum_matakuliah'] = $kur['matakuliah'];
            $d['kurikulum_otomatis'] = $kur['otomatis'];
            $d['kurikulum_dipilih'] = $kur['manual'] ? $kur['dipakai']['id_kurikulum'] : '';
            if (! $kur['dipakai']) {
                $d['galat_feeder'] = 'Kurikulum untuk prodi ini tidak ditemukan di Neo Feeder.';
            }
        } catch (\Throwable $e) {
            report($e);
            $d['galat_feeder'] = 'Gagal memuat kurikulum dari Neo Feeder. Rekap ditampilkan tanpa pencocokan kurikulum. Alasan: '.$e->getMessage();
        }

        $mkLokal = $this->transkrip->matakuliahLokal($kodeProdi, $angkatan);

        $baris = $mahasiswa->map(function ($mhs) use ($transkrip, $kur, $mkLokal) {
            $data = $transkrip[$mhs->npm];
            if ($kur && $kur['dipakai']) {
                $data = $this->transkrip->cocokkanKurikulum($data, $kur['matakuliah'], $kur['dipakai']);
            }
            $r = $data['ringkasan'];
            $adaKurikulum = isset($r['syarat_sks_lulus']);

            $masalah = array_filter([
                'nilai-kosong' => $r['tanpa_nilai'] > 0,
                'dobel' => $r['dobel_belum_dipilih'] > 0,
                'wajib-belum' => $adaKurikulum && $r['wajib_belum'] > 0,
                'sks-kurang' => $adaKurikulum && $r['total_sks'] < $r['syarat_sks_lulus'],
                'tidak-lulus' => $r['tidak_lulus'] > 0,
            ]);

            return [
                'npm' => $mhs->npm,
                'nama' => $mhs->nama_mahasiswa,
                'ringkasan' => $r,
                'belum_diambil' => collect($data['mk_belum_terpenuhi'] ?? [])->where('status', 'belum_diambil')->count(),
                // Bahan untuk jendela "Isi Data": KRS bernilai kosong & MK kurikulum tanpa KRS
                'daftar_nilai_kosong' => $data['baris']->where('punya_nilai', false)->map(fn ($b) => [
                    'krs_id' => $b['krs_id'],
                    'kode_mk' => $b['kode_mk'],
                    'nama_mk' => $b['nama_mk'],
                    'periode' => $b['nama_periode'],
                    'sks' => $b['sks'],
                ])->values()->all(),
                // Nilai tidak lulus pada MK yang dihitung → bisa diperbaiki
                'daftar_tidak_lulus' => $data['baris']->where('diambil', true)->where('tidak_lulus', true)->map(fn ($b) => [
                    'krs_id' => $b['krs_id'],
                    'kode_mk' => $b['kode_mk'],
                    'nama_mk' => $b['nama_mk'],
                    'periode' => $b['nama_periode'],
                    'sks' => $b['sks'],
                    'huruf' => $b['nilai_huruf'],
                    'angka' => $b['nilai_angka'] !== null ? number_format((float) $b['nilai_angka'], 2) : null,
                ])->values()->all(),
                'daftar_belum_diambil' => collect($data['mk_belum_terpenuhi'] ?? [])->where('status', 'belum_diambil')->map(fn ($m) => [
                    'kode_mk' => $m['kode_mk'],
                    'nama_mk' => $m['nama_mk'],
                    'sks' => $m['sks'],
                    'semester' => $m['semester'],
                    'wajib' => $m['wajib'],
                    'ada_di_master' => $mkLokal->has($m['kode_mk']),
                ])->values()->all(),
                'masalah' => array_keys($masalah),
                'siap' => $adaKurikulum && empty($masalah),
                'angkatan' => $mhs->tahun_angkatan,
                'prodi' => $mhs->prodi->nama_program_studi_idn ?? $mhs->kode_program_studi,
                'transkrip' => $data['baris'],
            ];
        });

        // Jumlah data per opsi filter (ditampilkan di dropdown)
        foreach (array_keys(self::FILTER) as $kunci) {
            $d['jumlah_per_filter'][$kunci] = $baris->filter(fn ($b) => $this->cocokFilter($b, $kunci))->count();
        }

        $tersaring = $baris
            ->filter(fn ($b) => $this->cocokFilter($b, $filter))
            ->filter(fn ($b) => $cari === '' || str_contains(strtolower($b['npm'].' '.$b['nama']), strtolower($cari)))
            ->values();

        $d['tersaring'] = $tersaring;
        $d['total_mahasiswa'] = $baris->count();
        $d['ringkasan_angkatan'] = [
            'siap' => $baris->where('siap', true)->count(),
            'rata_ipk' => round($baris->filter(fn ($b) => $b['ringkasan']['total_sks'] > 0)->avg(fn ($b) => $b['ringkasan']['ipk']) ?? 0, 2),
            'nilai_kosong' => $baris->sum(fn ($b) => $b['ringkasan']['tanpa_nilai']),
            'mhs_nilai_kosong' => $d['jumlah_per_filter']['nilai-kosong'],
        ];

        return $d;
    }

    /**
     * Simpan sekaligus dari jendela "Isi Data" di rekap: nilai untuk KRS kosong
     * dan KRS baru untuk MK kurikulum yang belum diambil. Baris yang dikosongkan dilewati.
     */
    public function simpan(Request $request)
    {
        $valid = $request->validate([
            'npm' => 'required|string|max:30',
            'angkatan' => 'required|digits:5',
            'nilai' => 'nullable|array',
            'nilai.*' => 'nullable|numeric|min:0|max:100',
            'krs' => 'nullable|array',
            'krs.*.tambah' => 'nullable|in:1',
            'krs.*.periode' => 'nullable|string|max:10',
            'krs.*.nilai' => 'nullable|numeric|min:0|max:100',
        ]);

        parse_str((string) $request->input('kembali', ''), $kembali);
        $kembali = array_intersect_key($kembali, array_flip(['prodi', 'angkatan', 'nim', 'kurikulum', 'status', 'cari', 'per_halaman', 'page']));
        $redirect = redirect()->route('wisuda.rekap.index', $kembali);

        $nilaiTersimpan = 0;
        $krsTersimpan = 0;
        $gagal = [];

        try {
            foreach ($valid['nilai'] ?? [] as $krsId => $angka) {
                if ($angka === null || $angka === '') {
                    continue;
                }
                $alasan = $this->transkrip->simpanNilai($valid['npm'], (int) $krsId, (float) $angka, $request->user()?->id);
                $alasan ? $gagal[] = $alasan : $nilaiTersimpan++;
            }

            foreach ($valid['krs'] ?? [] as $kodeMk => $isian) {
                if (empty($isian['tambah'])) {
                    continue;
                }
                if (empty($isian['periode'])) {
                    $gagal[] = "Periode KRS $kodeMk belum dipilih.";

                    continue;
                }
                $nilai = isset($isian['nilai']) && $isian['nilai'] !== '' ? (float) $isian['nilai'] : null;
                $alasan = $this->transkrip->tambahKrs($valid['npm'], (string) $kodeMk, $isian['periode'], $nilai, $valid['angkatan']);
                $alasan ? $gagal[] = $alasan : $krsTersimpan++;
            }
        } catch (\Throwable $e) {
            report($e);

            return $redirect->with('error', '✕ Gagal menyimpan data. Coba lagi.');
        }

        if ($nilaiTersimpan === 0 && $krsTersimpan === 0 && empty($gagal)) {
            return $redirect->with('error', '⚠ Tidak ada isian yang disimpan. Isi nilai atau centang KRS yang ingin ditambahkan.');
        }

        $pesan = [];
        if ($nilaiTersimpan) {
            $pesan[] = "$nilaiTersimpan nilai diperbarui";
        }
        if ($krsTersimpan) {
            $pesan[] = "$krsTersimpan KRS ditambahkan";
        }
        if ($pesan) {
            $redirect->with('success', '✓ Data berhasil disimpan ('.implode(', ', $pesan).') untuk NPM '.$valid['npm']);
        }
        if ($gagal) {
            $redirect->with('error', '✕ Sebagian gagal: '.implode(' ', array_unique($gagal)));
        }

        return $redirect;
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
}
