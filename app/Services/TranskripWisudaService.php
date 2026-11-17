<?php

namespace App\Services;

use App\Models\KRS;
use App\Models\KurikulumMataKuliah;
use App\Models\KurikulumProdi;
use App\Models\Mahasiswa;
use App\Models\SkalaNilai;
use App\Models\TahunAkademik;
use App\Models\WisudaPilihanMatakuliah;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun transkrip nilai untuk keperluan wisuda dari tbl_mahasiswa_krs,
 * mendeteksi matakuliah dobel, lalu menghitung IPK dari matakuliah yang diambil.
 *
 * Matakuliah dianggap dobel hanya jika kode matakuliahnya sama.
 */
class TranskripWisudaService
{
    /**
     * @return array{mahasiswa: Mahasiswa, baris: Collection, grup: array, ringkasan: array}|null
     */
    public function susun(string $npm): ?array
    {
        $mahasiswa = Mahasiswa::with('prodi')->where('npm', $npm)->first();

        return $mahasiswa ? $this->susunBanyak(collect([$mahasiswa]))[$mahasiswa->npm] : null;
    }

    /**
     * Susun transkrip banyak mahasiswa sekaligus (untuk rekap per prodi/angkatan).
     * Data dimuat sekali untuk semua NPM agar jumlah query tetap sedikit.
     *
     * @return array<string, array> key = NPM
     */
    public function susunBanyak(Collection $daftarMahasiswa): array
    {
        $npm = $daftarMahasiswa->pluck('npm')->all();

        $krsPerNpm = collect();
        foreach (array_chunk($npm, 500) as $potongan) {
            $krsPerNpm = $krsPerNpm->concat(
                KRS::with(['mataKuliahJadwal', 'mataKuliahLangsung'])->whereIn('npm', $potongan)->get()
            );
        }
        $krsPerNpm = $krsPerNpm->groupBy('npm');

        $namaPeriode = TahunAkademik::pluck('nama_tahun_akademik', 'kode_tahun_akademik');

        // Huruf yang dinyatakan tidak lulus menurut skala nilai tiap prodi
        $hurufTidakLulusProdi = SkalaNilai::whereIn('kode_program_studi', $daftarMahasiswa->pluck('kode_program_studi')->unique())
            ->where('lulus', 'T')
            ->get(['kode_program_studi', 'nama'])
            ->groupBy('kode_program_studi')
            ->map(fn ($g) => $g->pluck('nama')->map(fn ($h) => strtoupper(trim($h)))->all());

        $pilihanPerNpm = WisudaPilihanMatakuliah::whereIn('npm', $npm)->get(['npm', 'krs_id', 'diambil'])
            ->groupBy('npm')
            ->map(fn ($g) => $g->pluck('diambil', 'krs_id'));

        $hasil = [];
        foreach ($daftarMahasiswa as $mhs) {
            $hasil[$mhs->npm] = $this->bangun(
                $mhs,
                $krsPerNpm->get($mhs->npm, collect()),
                $namaPeriode,
                $hurufTidakLulusProdi->get($mhs->kode_program_studi, []),
                $pilihanPerNpm->get($mhs->npm, collect()),
            );
        }

        return $hasil;
    }

    private function bangun(Mahasiswa $mahasiswa, Collection $krs, Collection $namaPeriode, array $hurufTidakLulus, Collection $pilihan): array
    {
        $baris = $krs->map(function (KRS $row) use ($namaPeriode, $hurufTidakLulus) {
            $mk = $row->mata_kuliah;
            $sks = (int) ($mk->sks_mata_kuliah ?? 0);
            $bobot = $row->nilai_bobot !== null ? (float) $row->nilai_bobot : null;

            return [
                'krs_id' => $row->id,
                'periode' => $row->kode_tahun_akademik,
                'nama_periode' => $namaPeriode[$row->kode_tahun_akademik] ?? $row->kode_tahun_akademik,
                'kode_mk' => $mk->kode_mata_kuliah ?? '-',
                'nama_mk' => $mk->nama_mata_kuliah_idn ?? '(Matakuliah tidak ditemukan)',
                // KRS rusak: jadwal/matakuliah yang dirujuk sudah tidak ada
                'mk_hilang' => $mk === null,
                'semester_mk' => $mk->semester ?? null,
                'sks' => $sks,
                'nilai_angka' => $row->nilai_angka,
                'nilai_huruf' => $row->nilai_huruf,
                'nilai_bobot' => $bobot,
                'mutu' => $bobot !== null ? round($bobot * $sks, 2) : null,
                'punya_nilai' => $row->nilai_huruf !== null && $row->nilai_huruf !== '',
                'tidak_lulus' => in_array(strtoupper(trim((string) $row->nilai_huruf)), $hurufTidakLulus, true),
                'grup' => null,
                'diambil' => true,
                'dihitung' => false,
            ];
        })->values()->all();

        $grup = $this->cariDobel($baris);
        $grup = $this->terapkanPilihan($pilihan, $baris, $grup);

        foreach ($baris as &$b) {
            $b['dihitung'] = $b['diambil'] && $b['punya_nilai'] && $b['sks'] > 0;
        }
        unset($b);

        // Urutkan per periode; anggota grup dobel diletakkan berdampingan
        // mengikuti periode percobaan pertamanya agar mudah dibandingkan.
        $periodeAwalGrup = [];
        foreach ($grup as $idx => $g) {
            $periodeAwalGrup[$idx] = collect($g['anggota'])->map(fn ($i) => $baris[$i]['periode'])->min();
        }
        $baris = collect($baris)->sortBy([
            fn ($a, $b) => ($a['grup'] !== null ? $periodeAwalGrup[$a['grup']] : $a['periode'])
                <=> ($b['grup'] !== null ? $periodeAwalGrup[$b['grup']] : $b['periode']),
            fn ($a, $b) => ($a['grup'] ?? -1) <=> ($b['grup'] ?? -1),
            fn ($a, $b) => $a['periode'] <=> $b['periode'],
            fn ($a, $b) => $a['kode_mk'] <=> $b['kode_mk'],
        ])->values();

        return [
            'mahasiswa' => $mahasiswa,
            'baris' => $baris,
            'grup' => $grup,
            'ringkasan' => $this->ringkasan($baris, $grup),
        ];
    }

    /**
     * Simpan pilihan: krs_id terpilih jadi "diambil", anggota grup lain "tidak diambil".
     * Mengembalikan false jika krs_id bukan anggota grup dobel milik NPM tersebut.
     */
    public function pilih(string $npm, int $krsId, ?int $userId): bool
    {
        $anggotaKrsId = $this->anggotaGrup($npm, $krsId);
        if ($anggotaKrsId === null) {
            return false;
        }

        foreach ($anggotaKrsId as $id) {
            WisudaPilihanMatakuliah::updateOrCreate(
                ['npm' => $npm, 'krs_id' => $id],
                ['diambil' => $id === $krsId ? 'Y' : 'T', 'user_id' => $userId]
            );
        }

        return true;
    }

    public function reset(string $npm): int
    {
        return WisudaPilihanMatakuliah::where('npm', $npm)->delete();
    }

    /**
     * Hapus permanen satu baris KRS yang boleh dihapus dari menu Wisuda:
     *  - anggota grup dobel (minimal satu baris matakuliah itu tetap tersisa),
     *  - KRS rusak (jadwal/matakuliahnya sudah tidak ada), atau
     *  - matakuliah di luar kurikulum Feeder yang dipakai ($mkKurikulum, key = kode MK).
     * Isi baris disalin dulu ke tbl_wisuda_hapus_krs_log agar bisa dipulihkan.
     *
     * @param  Collection|null  $mkKurikulum  null jika kurikulum Feeder tidak tersedia
     * @return string|null pesan alasan jika ditolak, null jika berhasil
     */
    public function hapus(string $npm, int $krsId, ?int $userId, ?Collection $mkKurikulum = null): ?string
    {
        $baris = collect($this->susun($npm)['baris'] ?? [])->firstWhere('krs_id', $krsId);
        if (! $baris) {
            return 'KRS tidak ditemukan untuk NPM ini.';
        }

        $bolehDihapus = $baris['grup'] !== null
            || $baris['mk_hilang']
            || ($mkKurikulum !== null && ! $mkKurikulum->has(strtoupper(trim($baris['kode_mk']))));
        if (! $bolehDihapus) {
            return $mkKurikulum === null
                ? 'Kurikulum Neo Feeder belum termuat, jadi hanya matakuliah dobel atau KRS rusak yang bisa dihapus.'
                : 'Hanya matakuliah dobel, di luar kurikulum, atau KRS rusak yang bisa dihapus.';
        }

        DB::transaction(function () use ($npm, $krsId, $userId) {
            $krs = KRS::where('id', $krsId)->where('npm', $npm)->lockForUpdate()->firstOrFail();

            DB::table('tbl_wisuda_hapus_krs_log')->insert([
                'krs_id' => $krs->id,
                'npm' => $npm,
                'data_krs' => json_encode($krs->getAttributes()),
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $krs->delete();
            WisudaPilihanMatakuliah::where('npm', $npm)->where('krs_id', $krsId)->delete();
        });

        return null;
    }

    /**
     * Isi nilai untuk KRS yang nilainya masih kosong. Nilai angka dikonversi ke
     * huruf/bobot memakai skala nilai prodi (sama seperti update nilai di KHS).
     *
     * @return string|null pesan alasan jika ditolak, null jika berhasil
     */
    public function simpanNilai(string $npm, int $krsId, float $nilaiAngka, ?int $userId = null): ?string
    {
        $mahasiswa = Mahasiswa::where('npm', $npm)->first();
        if (! $mahasiswa) {
            return 'Mahasiswa tidak ditemukan.';
        }

        $krs = KRS::where('id', $krsId)->where('npm', $npm)->first();
        if (! $krs) {
            return 'Data KRS tidak ditemukan.';
        }

        // Yang boleh diubah dari menu ini: nilai kosong, atau nilai tidak lulus (perbaikan)
        $kosong = $krs->nilai_huruf === null || $krs->nilai_huruf === '';
        if (! $kosong && ! $this->hurufTidakLulus($mahasiswa->kode_program_studi, $krs->nilai_huruf)) {
            return "Nilai {$krs->nilai_huruf} sudah lulus, tidak bisa diubah dari sini.";
        }

        $skala = $this->skalaUntuk($mahasiswa->kode_program_studi, $nilaiAngka);
        if (! $skala) {
            return 'Skala nilai prodi untuk nilai '.$nilaiAngka.' tidak ditemukan.';
        }

        DB::transaction(function () use ($krs, $npm, $nilaiAngka, $skala, $kosong, $userId) {
            // Nilai baru ditulis langsung ke KRS
            KRS::where('id', $krs->id)->update($this->kolomNilai($nilaiAngka, $skala));

            DB::table('tbl_wisuda_perbaikan_nilai_log')->insert([
                'krs_id' => $krs->id,
                'npm' => $npm,
                'nilai_angka_lama' => $kosong ? null : $krs->nilai_angka,
                'nilai_huruf_lama' => $kosong ? null : $krs->nilai_huruf,
                'nilai_bobot_lama' => $kosong ? null : $krs->nilai_bobot,
                'nilai_angka_baru' => $nilaiAngka,
                'nilai_huruf_baru' => $skala->nama,
                'nilai_bobot_baru' => $skala->bobot,
                'jenis' => $kosong ? 'isi_kosong' : 'perbaikan',
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return null;
    }

    private function hurufTidakLulus(string $kodeProdi, string $huruf): bool
    {
        return SkalaNilai::where('kode_program_studi', $kodeProdi)
            ->where('lulus', 'T')
            ->whereRaw('UPPER(TRIM(nama)) = ?', [strtoupper(trim($huruf))])
            ->exists();
    }

    /**
     * Tambah KRS untuk matakuliah kurikulum yang belum diambil (jadwal_id = 0,
     * sama seperti kontrak matakuliah manual di menu Mahasiswa). Nilai opsional.
     *
     * @return string|null pesan alasan jika ditolak, null jika berhasil
     */
    public function tambahKrs(string $npm, string $kodeMk, string $periode, ?float $nilaiAngka, string $angkatan, ?int &$idBaru = null): ?string
    {
        $mahasiswa = Mahasiswa::where('npm', $npm)->first();
        if (! $mahasiswa) {
            return 'Mahasiswa tidak ditemukan.';
        }
        if (! TahunAkademik::where('kode_tahun_akademik', $periode)->exists()) {
            return 'Periode tidak valid.';
        }

        $kodeMk = strtoupper(trim($kodeMk));
        $mk = $this->matakuliahLokal($mahasiswa->kode_program_studi, $angkatan ?: (string) $mahasiswa->tahun_angkatan)->get($kodeMk);
        if (! $mk) {
            return "Matakuliah $kodeMk belum ada di master kurikulum SIADE.";
        }

        $sudahAda = collect($this->susun($npm)['baris'])
            ->contains(fn ($b) => strtoupper(trim($b['kode_mk'])) === $kodeMk);
        if ($sudahAda) {
            return "Mahasiswa sudah memiliki KRS $kodeMk.";
        }

        $kolom = [
            'npm' => $npm,
            'mata_kuliah_id' => $mk->id,
            'jadwal_id' => 0,
            'kode_tahun_akademik' => $periode,
            'persetujuan_pa' => 'Y',
            'datetime_persetujuan_pa' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($nilaiAngka !== null) {
            $skala = $this->skalaUntuk($mahasiswa->kode_program_studi, $nilaiAngka);
            if (! $skala) {
                return 'Skala nilai prodi untuk nilai '.$nilaiAngka.' tidak ditemukan.';
            }
            $kolom = array_merge($kolom, $this->kolomNilai($nilaiAngka, $skala));
        }

        $idBaru = DB::table('tbl_mahasiswa_krs')->insertGetId($kolom);

        return null;
    }

    /**
     * Salin nilai PDDikti (satu baris hasil NilaiPddiktiService) ke KRS SIADE.
     * $krsId diisi → nilai KRS tsb ditimpa; null → dibuat KRS baru pada periode PDDikti.
     * Huruf & bobot dihitung dari skala nilai prodi SIADE; nilai lama dicatat di log.
     *
     * @return string|null pesan alasan jika ditolak, null jika berhasil
     */
    public function sinkronPddikti(string $npm, string $kodeMk, ?int $krsId, array $nilaiPddikti, string $angkatan, ?int $userId = null): ?string
    {
        $mahasiswa = Mahasiswa::where('npm', $npm)->first();
        if (! $mahasiswa) {
            return 'Mahasiswa tidak ditemukan.';
        }
        $kodeMk = strtoupper(trim($kodeMk));

        $angka = $nilaiPddikti['nilai_angka'];
        if ($angka !== null) {
            $skala = $this->skalaUntuk($mahasiswa->kode_program_studi, $angka);
            if (! $skala) {
                return 'Skala nilai prodi untuk nilai '.$angka.' tidak ditemukan.';
            }
            $kolom = $this->kolomNilai($angka, $skala);
        } elseif (! empty($nilaiPddikti['nilai_huruf'])) {
            // PDDikti hanya punya huruf: cari huruf yang sama di skala prodi
            $skala = SkalaNilai::where('kode_program_studi', $mahasiswa->kode_program_studi)
                ->whereRaw('UPPER(TRIM(nama)) = ?', [strtoupper($nilaiPddikti['nilai_huruf'])])
                ->first();
            if (! $skala) {
                return "Huruf {$nilaiPddikti['nilai_huruf']} tidak ada di skala nilai prodi.";
            }
            $kolom = ['nilai_angka' => null, 'nilai_huruf' => $skala->nama, 'nilai_bobot' => $skala->bobot, 'lulus' => $skala->lulus, 'updated_at' => now()];
        } else {
            return 'Nilai di PDDikti masih kosong.';
        }

        $krs = null;
        if ($krsId) {
            $krs = KRS::with(['mataKuliahJadwal', 'mataKuliahLangsung'])->where('id', $krsId)->where('npm', $npm)->first();
            if (! $krs) {
                return 'Data KRS tidak ditemukan.';
            }
            if (strtoupper(trim($krs->mata_kuliah->kode_mata_kuliah ?? '')) !== $kodeMk) {
                return 'Kode matakuliah KRS tidak sama dengan nilai PDDikti.';
            }
            $samaAngka = ($krs->nilai_angka === null && $kolom['nilai_angka'] === null)
                || ($krs->nilai_angka !== null && $kolom['nilai_angka'] !== null && abs((float) $krs->nilai_angka - (float) $kolom['nilai_angka']) < 0.005);
            if ($samaAngka && strtoupper(trim((string) $krs->nilai_huruf)) === strtoupper($kolom['nilai_huruf'])) {
                return 'Nilai SIADE sudah sama dengan PDDikti.';
            }
        }

        $alasan = null;
        DB::transaction(function () use ($krs, $npm, $kodeMk, $nilaiPddikti, $angkatan, $kolom, $userId, &$alasan) {
            $id = $krs?->id;
            if (! $krs) {
                $alasan = $this->tambahKrs($npm, $kodeMk, $nilaiPddikti['periode'], null, $angkatan, $id);
                if ($alasan) {
                    return;
                }
            }

            KRS::where('id', $id)->update($kolom);

            DB::table('tbl_wisuda_perbaikan_nilai_log')->insert([
                'krs_id' => $id,
                'npm' => $npm,
                'nilai_angka_lama' => $krs?->nilai_angka,
                'nilai_huruf_lama' => $krs?->nilai_huruf ?: null,
                'nilai_bobot_lama' => $krs?->nilai_bobot,
                'nilai_angka_baru' => $kolom['nilai_angka'] ?? 0, // PDDikti tanpa angka dicatat 0
                'nilai_huruf_baru' => $kolom['nilai_huruf'],
                'nilai_bobot_baru' => $kolom['nilai_bobot'],
                'jenis' => 'sinkron_pddikti',
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return $alasan;
    }

    /**
     * Matakuliah di master kurikulum SIADE untuk prodi, key = kode MK (huruf besar).
     * Jika satu kode ada di beberapa kurikulum, diutamakan kurikulum lokal milik angkatan.
     */
    public function matakuliahLokal(string $kodeProdi, string $angkatan): Collection
    {
        $kurikulumAngkatan = KurikulumProdi::where('kode_program_studi', $kodeProdi)
            ->whereJsonContains('tahun_angkatan', (int) $angkatan)
            ->value('kurikulum_id');

        return KurikulumMataKuliah::where('kode_program_studi', $kodeProdi)
            ->get(['id', 'kurikulum_id', 'kode_mata_kuliah', 'nama_mata_kuliah_idn', 'sks_mata_kuliah'])
            ->sortBy(fn ($mk) => [$mk->kurikulum_id == $kurikulumAngkatan ? 1 : 0, $mk->id])
            ->keyBy(fn ($mk) => strtoupper(trim($mk->kode_mata_kuliah)));
    }

    private function skalaUntuk(string $kodeProdi, float $nilaiAngka): ?SkalaNilai
    {
        return SkalaNilai::where('kode_program_studi', $kodeProdi)
            ->where('nilai_mulai', '<=', $nilaiAngka)
            ->where('nilai_sampai', '>=', $nilaiAngka)
            ->first();
    }

    private function kolomNilai(float $nilaiAngka, SkalaNilai $skala): array
    {
        return [
            'nilai_angka' => $nilaiAngka,
            'nilai_huruf' => $skala->nama,
            'nilai_bobot' => $skala->bobot,
            'lulus' => $skala->lulus,
            'updated_at' => now(),
        ];
    }

    /**
     * Daftar krs_id satu grup dobel yang memuat $krsId, atau null jika tidak dobel.
     */
    private function anggotaGrup(string $npm, int $krsId): ?array
    {
        $data = $this->susun($npm);

        foreach ($data['grup'] ?? [] as $g) {
            if (in_array($krsId, $g['krs_ids'], true)) {
                return $g['krs_ids'];
            }
        }

        return null;
    }

    /**
     * Cocokkan transkrip dengan matakuliah kurikulum (dari Neo Feeder) berdasarkan kode MK.
     * Menambah penanda per baris dan daftar MK kurikulum yang belum terpenuhi.
     *
     * @param  Collection  $mkKurikulum  hasil KurikulumFeederService::matakuliah(), key = kode MK
     */
    public function cocokkanKurikulum(array $data, Collection $mkKurikulum, array $kurikulum): array
    {
        $data['baris'] = $data['baris']->map(function ($b) use ($mkKurikulum) {
            $mk = $mkKurikulum->get(strtoupper(trim($b['kode_mk'])));
            $b['di_kurikulum'] = $mk !== null;
            $b['sks_kurikulum'] = $mk['sks'] ?? null;
            $b['sks_beda'] = $mk !== null && (float) $b['sks'] !== (float) $mk['sks'];

            return $b;
        });

        // Status tiap MK kurikulum berdasarkan baris transkrip yang diambil
        $diambil = $data['baris']->where('diambil', true)
            ->groupBy(fn ($b) => strtoupper(trim($b['kode_mk'])));

        $belum = $mkKurikulum->map(function ($mk) use ($diambil) {
            $baris = $diambil->get($mk['kode_mk']);
            if (! $baris) {
                return $mk + ['status' => 'belum_diambil', 'krs_id' => null];
            }
            $lulus = $baris->contains(fn ($b) => $b['punya_nilai'] && ! $b['tidak_lulus']);
            if ($lulus) {
                return null;
            }
            $kosong = $baris->firstWhere('punya_nilai', false);
            $gagal = $baris->firstWhere('tidak_lulus', true);

            return $mk + [
                'status' => $kosong ? 'belum_ada_nilai' : 'tidak_lulus',
                // KRS yang bisa diisi (kosong) atau diperbaiki (tidak lulus) dari menu Wisuda
                'krs_id' => ($kosong ?? $gagal)['krs_id'] ?? null,
                'nilai_lama' => $kosong ? null : ($gagal['nilai_huruf'] ?? null),
            ];
        })->filter()->values();

        $sksLulusKurikulum = $mkKurikulum->filter(function ($mk) use ($belum) {
            return ! $belum->contains('kode_mk', $mk['kode_mk']);
        });

        $data['kurikulum'] = $kurikulum;
        $data['mk_belum_terpenuhi'] = $belum;
        $data['ringkasan'] += [
            'luar_kurikulum' => $data['baris']->where('diambil', true)->where('di_kurikulum', false)->count(),
            'sks_beda' => $data['baris']->where('diambil', true)->where('sks_beda', true)->count(),
            // Semua MK kurikulum Feeder (wajib & pilihan) harus ada di KRS; yang tidak ada = kurang
            'mk_kurang' => $belum->where('status', 'belum_diambil')->count(),
            'sks_wajib_terpenuhi' => $sksLulusKurikulum->where('wajib', true)->sum('sks'),
            'syarat_sks_lulus' => $kurikulum['jumlah_sks_lulus'],
            'syarat_sks_wajib' => $kurikulum['jumlah_sks_wajib'],
        ];

        return $data;
    }

    /**
     * Kelompokkan baris yang dobel (union-find berdasarkan kode & nama matakuliah).
     */
    private function cariDobel(array &$baris): array
    {
        // Dobel hanya jika kode matakuliahnya sama (huruf besar/kecil dan spasi di awal/akhir diabaikan)
        $kelompok = [];
        foreach ($baris as $i => $b) {
            $kode = strtolower(trim($b['kode_mk']));
            if ($kode !== '' && $kode !== '-') {
                $kelompok[$kode][] = $i;
            }
        }

        $grup = [];
        foreach ($kelompok as $anggota) {
            if (count($anggota) < 2) {
                continue;
            }
            $idx = count($grup);
            foreach ($anggota as $i) {
                $baris[$i]['grup'] = $idx;
            }
            $grup[$idx] = [
                'anggota' => $anggota,
                'krs_ids' => array_map(fn ($i) => $baris[$i]['krs_id'], $anggota),
                'nama_mk' => $baris[$anggota[0]]['nama_mk'],
                'sudah_dipilih' => false,
            ];
        }

        return $grup;
    }

    /**
     * Tandai baris mana yang diambil di setiap grup. Grup dianggap sudah dipilih
     * hanya jika semua anggotanya punya catatan pilihan dan tepat satu "Y"
     * (KRS baru yang muncul belakangan membuat grup kembali perlu dipilih).
     * Grup yang belum dipilih memakai default: bobot tertinggi, lalu periode terbaru.
     */
    private function terapkanPilihan(Collection $pilihan, array &$baris, array $grup): array
    {
        if (empty($grup)) {
            return $grup;
        }

        foreach ($grup as $idx => &$g) {
            $catatan = collect($g['krs_ids'])->map(fn ($id) => $pilihan[$id] ?? null);
            $lengkap = $catatan->every(fn ($v) => $v !== null);
            $jumlahY = $catatan->filter(fn ($v) => $v === 'Y')->count();

            if ($lengkap && $jumlahY === 1) {
                $terpilih = $g['krs_ids'][$catatan->search('Y')];
                $g['sudah_dipilih'] = true;
            } else {
                $terpilih = collect($g['anggota'])
                    ->sortByDesc(fn ($i) => sprintf(
                        '%06.2f|%s|%012d',
                        $baris[$i]['nilai_bobot'] ?? -1,
                        $baris[$i]['periode'],
                        $baris[$i]['krs_id']
                    ))
                    ->map(fn ($i) => $baris[$i]['krs_id'])
                    ->first();
            }

            $g['terpilih'] = $terpilih;
            foreach ($g['anggota'] as $i) {
                $baris[$i]['diambil'] = $baris[$i]['krs_id'] === $terpilih;
            }
        }
        unset($g);

        return $grup;
    }

    private function ringkasan(Collection $baris, array $grup): array
    {
        $dihitung = $baris->where('dihitung', true);
        $totalSks = $dihitung->sum('sks');
        $totalMutu = $dihitung->sum(fn ($b) => $b['nilai_bobot'] * $b['sks']);

        return [
            'ipk' => $totalSks > 0 ? round($totalMutu / $totalSks, 2) : 0,
            'total_sks' => $totalSks,
            'total_mutu' => round($totalMutu, 2),
            'jumlah_mk' => $dihitung->count(),
            'jumlah_dobel' => count($grup),
            'dobel_belum_dipilih' => collect($grup)->where('sudah_dipilih', false)->count(),
            'tanpa_nilai' => $baris->where('diambil', true)->where('punya_nilai', false)->count(),
            'tidak_lulus' => $dihitung->where('tidak_lulus', true)->count(),
        ];
    }
}
