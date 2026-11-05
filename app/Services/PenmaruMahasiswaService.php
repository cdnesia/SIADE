<?php

namespace App\Services;

use App\Http\Controllers\MahasiswaBaruController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Akses data mahasiswa baru (sudah ber-NIM) di database penmaru_old.
 * Aturan tahun, pengecualian, dan format NIM mengikuti Generate NPM (MahasiswaBaruController).
 */
class PenmaruMahasiswaService
{
    const ROLE_MAHASISWA = 6;

    private function db()
    {
        return DB::connection('penmaru_old');
    }

    /**
     * Daftar tahun gelombang yang tersedia, contoh: ['UMJA2026', 'UMJA2025'].
     */
    public function daftarTahun(): Collection
    {
        return $this->db()->table('pmb_prodi')
            ->where('na', 'N')
            ->selectRaw('DISTINCT LEFT(pmb_gelombang, 8) as tahun')
            ->orderByDesc('tahun')
            ->pluck('tahun');
    }

    /**
     * Pendaftar yang sudah memiliki NIM pada tahun tertentu.
     */
    public function pendaftarBerNim(string $tahun): Collection
    {
        return $this->db()->table('pmb_prodi')
            ->where('na', 'N')
            ->whereRaw('LEFT(pmb_gelombang, 8) = ?', [$tahun])
            ->whereNotNull('nim')
            ->where('nim', '!=', '')
            ->whereNotIn('pmb', MahasiswaBaruController::EXCLUDE)
            ->orderBy('prodi')
            ->orderBy('nim')
            ->get([
                // nomor = id pmb_prodi (dipakai import), nomor_pmb = pmb.nomor (dipakai edit & generate NIM)
                'nomor', 'nomor_pmb', 'pmb', 'nim', 'nim_num', 'nama', 'nama_daftar', 'nik', 'prodi', 'kelas',
                'nama_kelas', 'jurusan', 'sekolah_asal', 'pmb_gelombang', 'nama_jalur', 'hp_daftar', 'email',
            ])
            ->unique('pmb')
            ->values();
    }

    /**
     * Cocokkan pendaftar dengan master_mahasiswa SIADE dan isi properti berikut pada tiap item:
     * - master        : data master yang NPM-nya sama dengan NIM penmaru (null jika tidak ada)
     * - master_lain   : data master milik orang yang sama (NIK/nama) tetapi NPM-nya berbeda
     * - status_master : 'sudah'    = NIM ada di master dan orangnya sama
     *                   'bentrok'  = NIM ada di master tetapi dipakai mahasiswa lain
     *                   'beda_nim' = orangnya sudah ada di master dengan NPM lain
     *                   'belum'    = belum ada di master
     */
    public function cocokkanDenganMaster(Collection $pendaftar, string $tahun): Collection
    {
        $kolom = ['id', 'npm', 'nama_mahasiswa', 'nik', 'kode_program_studi', 'tahun_angkatan'];

        $byNpm = DB::table('master_mahasiswa')->whereIn('npm', $pendaftar->pluck('nim'))->get($kolom)->keyBy('npm');

        $nik = $pendaftar->pluck('nik')->map(fn($n) => trim((string) $n))->filter()->unique();
        $byNik = DB::table('master_mahasiswa')->whereIn('nik', $nik)->get($kolom)->groupBy('nik');

        // Kandidat pencocokan nama: master seangkatan (contoh: UMJA2026 -> 2026x)
        $angkatan = DB::table('master_mahasiswa')
            ->where('tahun_angkatan', 'like', '20' . substr($tahun, 6, 2) . '%')
            ->get($kolom);

        foreach ($pendaftar as $p) {
            $p->master = $byNpm[$p->nim] ?? null;
            $p->master_lain = null;

            if ($p->master && $this->orangSama($p, $p->master)) {
                $p->status_master = 'sudah';
                continue;
            }

            // Cari data diri yang sama di master dengan NPM berbeda: NIK dulu, lalu nama + prodi
            $nikP = trim((string) $p->nik);
            $p->master_lain = ($nikP !== '' ? ($byNik[$nikP] ?? collect())->first(fn($m) => $m->npm !== $p->nim) : null)
                ?? $angkatan->first(fn($m) => $m->npm !== $p->nim
                    && (string) $m->kode_program_studi === (string) $p->prodi
                    && $this->namaSama($p->nama ?: $p->nama_daftar, $m->nama_mahasiswa));

            $p->status_master = $p->master ? 'bentrok' : ($p->master_lain ? 'beda_nim' : 'belum');
        }

        // Nama di SIADE untuk dibandingkan dengan nama di PMB
        foreach ($pendaftar as $p) {
            $m = $p->master ?? $p->master_lain;
            $p->nama_siade = $m->nama_mahasiswa ?? null;
            $p->nama_beda = $m && !$this->namaSama($p->nama ?: $p->nama_daftar, $m->nama_mahasiswa);
        }

        return $pendaftar;
    }

    /**
     * Pendaftar dan data master dianggap orang yang sama jika NIK sama,
     * atau (bila salah satu NIK kosong) namanya sama setelah dinormalisasi.
     */
    public function orangSama($pendaftar, $master): bool
    {
        $nikP = trim((string) ($pendaftar->nik ?? ''));
        $nikM = trim((string) ($master->nik ?? ''));
        if ($nikP !== '' && $nikM !== '') {
            return $nikP === $nikM;
        }

        return $this->namaSama($pendaftar->nama ?: ($pendaftar->nama_daftar ?? ''), $master->nama_mahasiswa);
    }

    private function namaSama(?string $a, ?string $b): bool
    {
        $norm = fn($s) => trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Z ]/', ' ', mb_strtoupper((string) $s))));
        $a = $norm($a);
        $b = $norm($b);

        return $a !== '' && $a === $b;
    }

    /**
     * Ubah NIM pendaftar secara manual. nim_num ikut disesuaikan bila NIM mengikuti
     * pola jenjang + tahun + kode prodi + nomor urut, agar generate berikutnya tetap berurutan.
     */
    public function ubahNim(int $nomor, string $nim): void
    {
        $this->db()->transaction(function () use ($nomor, $nim) {
            $pmb = $this->db()->table('pmb')->where('nomor', $nomor)->lockForUpdate()->first();
            $prodi = $this->daftarProdi()->get($pmb->prodi);

            $data = ['nim' => $nim];
            if ($prodi) {
                $awalan = $prodi->jenjang . substr($pmb->pmb, 6, 2) . $prodi->nim_prodi_kode;
                $sisa = substr($nim, strlen($awalan));
                if (str_starts_with($nim, $awalan) && $sisa !== '' && ctype_digit($sisa)) {
                    $data['nim_num'] = (int) $sisa;
                }
            }

            $this->db()->table('pmb')->where('nomor', $nomor)->update($data);
        });
    }

    /**
     * Apakah NIM sudah dipakai pendaftar lain di penmaru.
     */
    public function nimDipakaiPendaftarLain(string $nim, int $nomor): bool
    {
        return $this->db()->table('pmb')->where('nim', $nim)->where('nomor', '!=', $nomor)->exists();
    }

    public function pendaftar(int $nomor)
    {
        return $this->db()->table('pmb')->where('nomor', $nomor)->first();
    }

    /**
     * Prodi beserta jenjang dan kode NIM, di-key berdasarkan kode prodi (contoh: 60201).
     */
    public function daftarProdi(): Collection
    {
        return $this->db()->table('master_sub_unit_kerja as msuk')
            ->join('master_pendidikan as mp', 'mp.id', 'msuk.id_pendidikan')
            ->where('msuk.kode', '!=', '')
            ->where('msuk.NA', 'A')
            ->orderBy('msuk.nama')
            ->get(['msuk.kode', 'msuk.nim_prodi_kode', 'mp.jenjang', 'msuk.nama'])
            ->keyBy('kode');
    }

    /**
     * Kelas aktif. list_prodi berbentuk ".60201.61201." atau kosong (berlaku untuk semua prodi).
     */
    public function daftarKelas(): Collection
    {
        return $this->db()->table('kelas')
            ->where('NA', 'A')
            ->orderBy('id')
            ->get(['id', 'nama', 'list_prodi'])
            ->keyBy('id');
    }

    public function daftarJurusan(): Collection
    {
        return $this->db()->table('pmb_jurusan')->orderBy('id')->pluck('nama');
    }

    public function kelasTersediaUntukProdi($kelas, string $kodeProdi): bool
    {
        return empty($kelas->list_prodi) || str_contains($kelas->list_prodi, '.' . $kodeProdi . '.');
    }

    /**
     * Perbarui kelas, prodi, dan jurusan pendaftar di tabel pmb.
     * NIM dibuat ulang jika diminta atau jika prodi berubah (kode prodi bagian dari NIM).
     *
     * @return array{nim_lama: ?string, nim_baru: ?string}
     */
    public function perbarui(int $nomor, array $data, bool $generateNim): array
    {
        return $this->db()->transaction(function () use ($nomor, $data, $generateNim) {
            $pmb = $this->db()->table('pmb')->where('nomor', $nomor)->lockForUpdate()->first();
            $prodiBerubah = (string) $pmb->prodi !== (string) $data['prodi'];

            $this->db()->table('pmb')->where('nomor', $nomor)->update([
                'kelas' => $data['kelas'],
                'prodi' => $data['prodi'],
                'jurusan' => $data['jurusan'],
            ]);

            $nimBaru = null;
            if ($generateNim || $prodiBerubah) {
                $nimBaru = $this->terbitkanNim($nomor);
            }

            return ['nim_lama' => $pmb->nim, 'nim_baru' => $nimBaru];
        });
    }

    /**
     * Terbitkan NIM baru dengan format: jenjang + 2 digit tahun + kode prodi NIM + nomor urut 3 digit.
     * Nomor urut melanjutkan nim_num terbesar pada prodi dan tahun yang sama.
     */
    public function terbitkanNim(int $nomor): string
    {
        return $this->db()->transaction(function () use ($nomor) {
            $pmb = $this->db()->table('pmb')->where('nomor', $nomor)->lockForUpdate()->first();
            $prodi = $this->daftarProdi()->get($pmb->prodi);

            if (!$prodi) {
                throw new \RuntimeException("Prodi {$pmb->prodi} tidak ditemukan di penmaru.");
            }

            $tahun = substr($pmb->pmb, 0, 8); // contoh: UMJA2026
            $kodeTahun = substr($tahun, 6, 2);

            $nimTerakhir = $this->db()->table('pmb')
                ->where('prodi', $pmb->prodi)
                ->where('pmb', 'like', $tahun . '%')
                ->whereNotIn('pmb', MahasiswaBaruController::EXCLUDE)
                ->whereNotNull('nim')
                ->where('nim_num', '!=', 0)
                ->lockForUpdate()
                ->max('nim_num');

            // Lewati nomor yang NIM-nya sudah dipakai, baik di penmaru maupun di master lokal
            $nimNum = (int) $nimTerakhir;
            do {
                $nimNum++;
                $nim = $prodi->jenjang . $kodeTahun . $prodi->nim_prodi_kode . str_pad($nimNum, 3, '0', STR_PAD_LEFT);
                $dipakai = $this->db()->table('pmb')->where('nim', $nim)->where('nomor', '!=', $nomor)->exists()
                    || DB::table('master_mahasiswa')->where('npm', $nim)->exists();
            } while ($dipakai);

            $this->db()->table('pmb')->where('nomor', $nomor)->update([
                'nim' => $nim,
                'nim_num' => $nimNum,
            ]);

            return $nim;
        });
    }

    /**
     * Masukkan pendaftar terpilih (berdasarkan nomor pmb) ke master_mahasiswa.
     * Hanya data inti dan biodata teks yang diambil; NIM yang sudah ada di master dilewati.
     * Setiap mahasiswa sekaligus dibuatkan akun login (username & password = NPM) dan user_id-nya diisi.
     *
     * @return array{ditambah: int, dilewati: array<string>}
     */
    public function masukkanKeMaster(array $nomor): array
    {
        $pendaftar = $this->db()->table('pmb_prodi')
            ->whereIn('nomor', $nomor)
            ->where('na', 'N')
            ->whereNotNull('nim')
            ->where('nim', '!=', '')
            ->whereNotIn('pmb', MahasiswaBaruController::EXCLUDE)
            ->get()
            ->unique('pmb');

        // Kelas penmaru dipetakan ke master_kelas_perkuliahan berdasarkan nama
        $kelasLokal = DB::table('master_kelas_perkuliahan')
            ->pluck('id', 'nama_program_perkuliahan')
            ->mapWithKeys(fn($id, $nama) => [strtolower(trim($nama)) => $id]);

        $sudahAda = DB::table('master_mahasiswa')->whereIn('npm', $pendaftar->pluck('nim'))->pluck('npm')->flip();

        $teks = fn($nilai, $maks) => ($nilai = trim((string) $nilai)) !== '' && mb_strlen($nilai) <= $maks ? $nilai : null;
        $tanggal = fn($nilai) => $nilai && $nilai !== '0000-00-00' ? $nilai : null;

        $baris = [];
        $dilewati = [];
        foreach ($pendaftar as $p) {
            if (isset($sudahAda[$p->nim])) {
                $dilewati[] = $p->nim;
                continue;
            }

            $baris[] = [
                'nama_mahasiswa' => mb_strtoupper(trim($p->nama ?: $p->nama_daftar)),
                'npm' => $p->nim,
                'va_code' => '',
                'tahun_angkatan' => '20' . substr($p->pmb, 6, 2) . '1', // UMJA2026xxxx -> 20261
                'kode_program_studi' => $p->prodi,
                'program_kuliah_id' => $kelasLokal[strtolower(trim((string) $p->nama_kelas))] ?? null,
                // 2 = Pindahan untuk jalur transfer/pindahan, selain itu 1 = Peserta didik baru
                'jenis_pendaftaran_id' => preg_match('/pindahan|transfer/i', (string) $p->nama_jalur) ? 2 : 1,
                'pa_id' => 0,
                'jenis_kelamin' => [1 => 'L', 2 => 'P'][$p->kelamin] ?? null,
                'tempat_lahir' => $teks($p->tempat_lahir, 100),
                'tanggal_lahir' => $tanggal($p->tanggal_lahir),
                'nik' => $teks($p->nik, 20),
                'nisn' => $teks($p->nisn, 20),
                'npsn' => $teks($p->npsn, 20),
                'no_kipk' => $teks($p->kipk, 25),
                'email' => $teks($p->email, 150),
                'handphone' => $teks($p->no_hp ?: ($p->no_wa ?: $p->hp_daftar), 30),
                'nama_ayah' => $teks($p->ayah, 150),
                'nama_ibu_kandung' => $teks($p->ibu, 150),
                'nama_wali' => $teks($p->wali, 150),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->simpanKeMaster($baris);

        return ['ditambah' => count($baris), 'dilewati' => $dilewati];
    }

    /**
     * Insert baris ke master_mahasiswa beserta akun login mahasiswanya dalam satu transaksi.
     * Dipakai import dari PMB maupun form Tambah Mahasiswa.
     */
    public function simpanKeMaster(array $baris): void
    {
        DB::transaction(function () use ($baris) {
            $userId = $this->buatAkunMahasiswa($baris);
            foreach ($baris as &$b) {
                $b['user_id'] = $userId[$b['npm']];
            }
            unset($b);

            collect($baris)->chunk(100)->each(fn($c) => DB::table('master_mahasiswa')->insert($c->all()));
        });
    }

    /**
     * Buat akun login untuk mahasiswa baru: username (kolom email) dan password = NPM.
     * Akun yang username-nya sudah ada dipakai ulang tanpa mengubah password-nya.
     *
     * @return array<string, int> user_id di-key berdasarkan NPM
     */
    private function buatAkunMahasiswa(array $baris): array
    {
        $npm = array_column($baris, 'npm');
        $sudahAda = DB::table('users')->whereIn('email', $npm)->pluck('id', 'email');

        $akunBaru = [];
        foreach ($baris as $b) {
            if (!isset($sudahAda[$b['npm']])) {
                $akunBaru[] = [
                    'name' => $b['nama_mahasiswa'],
                    'email' => $b['npm'],
                    'password' => Hash::make($b['npm']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        collect($akunBaru)->chunk(100)->each(fn($c) => DB::table('users')->insert($c->all()));

        $userId = DB::table('users')->whereIn('email', $npm)->pluck('id', 'email');

        // Semua akun mahasiswa masuk ke role mahasiswa (id 6), dilewati jika role belum ada
        $role = Role::find(self::ROLE_MAHASISWA);
        if ($role) {
            DB::table('model_has_roles')->insertOrIgnore($userId->map(fn($id) => [
                'role_id' => $role->id,
                'model_type' => \App\Models\User::class,
                'model_id' => $id,
            ])->values()->all());
        }

        return $userId->all();
    }
}
