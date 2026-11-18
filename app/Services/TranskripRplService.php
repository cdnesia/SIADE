<?php

namespace App\Services;

use App\Models\SkalaNilai;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Nilai konversi mahasiswa RPL (Rekognisi Pembelajaran Lampau) langsung dari Neo Feeder.
 * NIM mahasiswa RPL di Feeder berbeda dengan data SIADE, jadi biodata & nilai konversi
 * diambil dari Feeder dan hanya dibaca.
 *
 * Yang diambil HANYA nilai konversi RPL (GetNilaiTransferPendidikanMahasiswa).
 * Nilai matakuliah lain sengaja dikosongkan karena diisi manual di Excel hasil export.
 */
class TranskripRplService
{
    private const LAMA_CACHE = 600; // detik

    /** Jenis daftar Feeder: 13 = RPL Perolehan SKS, 16 = RPL Transfer SKS */
    public const JENIS_DAFTAR_RPL = ['13', '16'];

    /** Jumlah id registrasi per panggilan Feeder agar filter tidak terlalu panjang */
    private const POTONGAN = 50;

    public function __construct(protected NeofeederService $feeder) {}

    /**
     * Mahasiswa RPL satu prodi + nilai konversinya dari Feeder, di-cache per prodi.
     *
     * @return array{mahasiswa: array, transfer: array}
     *
     * @throws RuntimeException jika Feeder gagal dihubungi
     */
    public function dataProdi(string $kodeProdi, bool $segarkan = false): array
    {
        $kodeProdi = preg_replace('/[^0-9A-Za-z]/', '', $kodeProdi);
        $kunci = "feeder.rpl-konversi.$kodeProdi";
        if ($segarkan) {
            Cache::forget($kunci);
        }

        return Cache::remember($kunci, self::LAMA_CACHE, function () use ($kodeProdi) {
            $prodi = $this->panggil('GetProdi', "kode_program_studi='$kodeProdi'");
            if (empty($prodi)) {
                return ['mahasiswa' => [], 'transfer' => []];
            }
            $idProdi = $this->uuid($prodi[0]['id_prodi']);
            $jenis = "'".implode("','", self::JENIS_DAFTAR_RPL)."'";

            $mahasiswa = $this->panggil('GetListRiwayatPendidikanMahasiswa', "id_prodi='$idProdi' and id_jenis_daftar in ($jenis)");

            $transfer = [];
            foreach (array_chunk(array_column($mahasiswa, 'id_registrasi_mahasiswa'), self::POTONGAN) as $potongan) {
                $daftar = "'".implode("','", array_map(fn ($id) => $this->uuid($id), $potongan))."'";
                $transfer = array_merge($transfer, $this->panggil('GetNilaiTransferPendidikanMahasiswa', "id_registrasi_mahasiswa in ($daftar)"));
            }

            return [
                'mahasiswa' => array_map(fn ($m) => [
                    'id_registrasi' => $m['id_registrasi_mahasiswa'],
                    'npm' => trim((string) $m['nim']),
                    'nama' => trim((string) $m['nama_mahasiswa']),
                    'angkatan' => (string) $m['id_periode_masuk'],
                    'jenis_daftar' => $m['nama_jenis_daftar'],
                    'sks_diakui' => (float) ($m['sks_diakui'] ?? 0),
                    'pt_asal' => $m['nama_perguruan_tinggi_asal'] ?? null,
                    'prodi_asal' => $m['nama_program_studi_asal'] ?? null,
                    'keterangan_keluar' => trim((string) ($m['keterangan_keluar'] ?? '')) ?: null,
                ], $mahasiswa),
                'transfer' => array_map(fn ($t) => [
                    'id_registrasi' => $t['id_registrasi_mahasiswa'],
                    'periode' => (string) ($t['id_semester'] ?: $t['id_periode_masuk']),
                    'nama_periode' => $t['nama_semester'] ?? null,
                    'kode_mk' => strtoupper(trim((string) $t['kode_matkul_diakui'])),
                    'nama_mk' => $t['nama_mata_kuliah_diakui'],
                    'sks' => (float) $t['sks_mata_kuliah_diakui'],
                    'nilai_huruf' => trim((string) $t['nilai_huruf_diakui']),
                    'nilai_indeks' => $t['nilai_angka_diakui'] !== null && $t['nilai_angka_diakui'] !== '' ? (float) $t['nilai_angka_diakui'] : null,
                    'asal' => trim(($t['kode_mata_kuliah_asal'] ?? '').' '.($t['nama_mata_kuliah_asal'] ?? '')).' ('.($t['nilai_huruf_asal'] ?? '-').')',
                ], $transfer),
            ];
        });
    }

    /**
     * Susun transkrip tiap mahasiswa. Bentuk baris & ringkasan sama dengan
     * TranskripWisudaService agar bisa dicocokkan kurikulum dan diexport dengan format yang sama.
     *
     * @param  array  $mahasiswa  baris 'mahasiswa' dari dataProdi()
     * @return array<string, array{baris: Collection, ringkasan: array}> key = id registrasi
     */
    public function susun(array $data, array $mahasiswa, string $kodeProdi): array
    {
        $hurufTidakLulus = SkalaNilai::where('kode_program_studi', $kodeProdi)->where('lulus', 'T')
            ->pluck('nama')->map(fn ($h) => strtoupper(trim($h)))->all();

        $transfer = collect($data['transfer'])->groupBy('id_registrasi');
        $namaPeriode = \App\Models\TahunAkademik::pluck('nama_tahun_akademik', 'kode_tahun_akademik');

        $hasil = [];
        foreach ($mahasiswa as $m) {
            $baris = collect($transfer->get($m['id_registrasi'], []))->map(fn ($t) => $t + ['sumber' => 'konversi'])
                ->values()
                ->map(function ($b, $i) use ($hurufTidakLulus, $namaPeriode) {
                    $huruf = strtoupper($b['nilai_huruf'] ?? '');
                    $punyaNilai = $huruf !== '';

                    return [
                        'krs_id' => null,
                        'urut' => $i,
                        'sumber' => $b['sumber'],
                        'periode' => $b['periode'],
                        'nama_periode' => $namaPeriode[$b['periode']] ?? ($b['nama_periode'] ?? $b['periode']),
                        'kode_mk' => $b['kode_mk'] !== '' ? $b['kode_mk'] : '-',
                        'nama_mk' => $b['nama_mk'],
                        'semester_mk' => null,
                        'sks' => $b['sks'],
                        'nilai_angka' => $b['nilai_angka'] ?? null,
                        'nilai_huruf' => $b['nilai_huruf'] ?? null,
                        'nilai_bobot' => $b['nilai_indeks'],
                        'mutu' => $b['nilai_indeks'] !== null ? round($b['nilai_indeks'] * $b['sks'], 2) : null,
                        'asal' => $b['asal'] ?? null,
                        'punya_nilai' => $punyaNilai,
                        'tidak_lulus' => $punyaNilai && (in_array($huruf, $hurufTidakLulus, true) || $b['nilai_indeks'] === 0.0),
                        'dobel' => false,
                        'diambil' => true,
                        'dihitung' => false,
                    ];
                });

            $baris = $this->pilihDobel($baris);
            $hasil[$m['id_registrasi']] = ['baris' => $baris, 'ringkasan' => $this->ringkasan($baris)];
        }

        return $hasil;
    }

    /**
     * Kode MK yang dikonversi lebih dari sekali:
     * yang dipakai adalah nilai dengan indeks tertinggi, sisanya tidak dihitung.
     */
    private function pilihDobel(Collection $baris): Collection
    {
        $dipakai = $baris->filter(fn ($b) => $b['kode_mk'] !== '-')
            ->groupBy('kode_mk')
            ->filter(fn ($g) => $g->count() > 1)
            ->map(fn ($g) => $g->sortByDesc(fn ($b) => [$b['punya_nilai'] ? 1 : 0, $b['nilai_bobot'] ?? -1, $b['periode']])->first()['urut']);

        return $baris->map(function ($b) use ($dipakai) {
            if ($dipakai->has($b['kode_mk'])) {
                $b['dobel'] = true;
                $b['diambil'] = $dipakai[$b['kode_mk']] === $b['urut'];
            }
            $b['dihitung'] = $b['diambil'] && $b['punya_nilai'] && $b['sks'] > 0 && $b['nilai_bobot'] !== null;

            return $b;
        })->sortBy([['periode', 'asc'], ['sumber', 'asc'], ['kode_mk', 'asc']])->values();
    }

    private function ringkasan(Collection $baris): array
    {
        $dihitung = $baris->where('dihitung', true);
        $totalSks = $dihitung->sum('sks');
        $totalMutu = $dihitung->sum(fn ($b) => $b['nilai_bobot'] * $b['sks']);
        $konversi = $baris->where('sumber', 'konversi');

        return [
            'ipk' => $totalSks > 0 ? round($totalMutu / $totalSks, 2) : 0,
            'total_sks' => $totalSks,
            'total_mutu' => round($totalMutu, 2),
            'jumlah_mk' => $dihitung->count(),
            'jumlah_krs' => $baris->where('diambil', true)->count(),
            'mk_konversi' => $konversi->count(),
            'sks_konversi' => $konversi->sum('sks'),
            'jumlah_dobel' => $baris->where('dobel', true)->where('diambil', true)->count(),
            'dobel_belum_dipilih' => 0,
            'tanpa_nilai' => $baris->where('diambil', true)->where('punya_nilai', false)->count(),
            'tidak_lulus' => $dihitung->where('tidak_lulus', true)->count(),
        ];
    }

    private function uuid(string $id): string
    {
        return preg_replace('/[^0-9a-f-]/i', '', $id);
    }

    private function panggil(string $aksi, string $filter): array
    {
        $parameter = ['act' => $aksi, 'filter' => $filter, 'order' => '', 'limit' => 0, 'offset' => 0];

        // Feeder kadang timeout saat minta token, jadi coba sekali lagi sebelum menyerah
        $hasil = $this->feeder->getData($parameter);
        if ((int) ($hasil['error_code'] ?? 1) !== 0) {
            $hasil = $this->feeder->getData($parameter);
        }
        if ((int) ($hasil['error_code'] ?? 1) !== 0) {
            throw new RuntimeException("Neo Feeder $aksi gagal: ".($hasil['error_desc'] ?? 'tanpa keterangan'));
        }

        return $hasil['data'] ?? [];
    }
}
