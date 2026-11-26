<?php

namespace App\Services;

use App\Models\KRS;
use App\Models\Mahasiswa;
use App\Models\SkalaNilai;
use App\Models\WisudaPilihanMatakuliah;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mencari mahasiswa yang mengambil matakuliah yang sama lebih dari sekali di KRS
 * (kode MK sama, huruf besar/kecil & spasi diabaikan — aturan yang sama dengan
 * deteksi dobel di menu Wisuda), serta update nilai / hapus salah satu KRS dobel.
 *
 * Kode MK diambil dari matakuliah jadwal, atau langsung dari mata_kuliah_id
 * untuk KRS manual (jadwal_id = 0).
 */
class MatakuliahDobelService
{
    /**
     * Baris KRS beserta kode MK ternormalisasi (subquery dasar).
     */
    private function krsDenganKode(): Builder
    {
        return DB::table('tbl_mahasiswa_krs as k')
            ->leftJoin('tbl_jadwal_perkuliahan as j', function ($join) {
                $join->on('j.id', '=', 'k.jadwal_id')->where('k.jadwal_id', '<>', 0);
            })
            ->join('master_kurikulum_matakuliah as mk', 'mk.id', '=', DB::raw('IF(k.jadwal_id = 0, k.mata_kuliah_id, j.mata_kuliah_id)'))
            ->whereRaw("TRIM(mk.kode_mata_kuliah) <> ''");
    }

    /**
     * Pasangan (npm, kode MK) yang muncul lebih dari sekali.
     */
    private function grupDobel(): Builder
    {
        return $this->krsDenganKode()
            ->selectRaw('k.npm, UPPER(TRIM(mk.kode_mata_kuliah)) as kode, COUNT(*) as jumlah')
            ->groupBy('k.npm', DB::raw('UPPER(TRIM(mk.kode_mata_kuliah))'))
            ->havingRaw('COUNT(*) > 1');
    }

    /**
     * Rekap per mahasiswa: jumlah MK dobel & jumlah baris KRS dobel.
     */
    private function rekapMahasiswa(?string $prodi, ?string $angkatan): Builder
    {
        $rekap = DB::query()->fromSub($this->grupDobel(), 'g')
            ->selectRaw('g.npm, COUNT(*) as jumlah_mk, SUM(g.jumlah) as jumlah_krs')
            ->groupBy('g.npm');

        return DB::query()->fromSub($rekap, 'r')
            ->join('master_mahasiswa as m', 'm.npm', '=', 'r.npm')
            ->leftJoin('master_program_studi as p', 'p.kode_program_studi', '=', 'm.kode_program_studi')
            ->when($prodi, fn ($q) => $q->where('m.kode_program_studi', $prodi))
            ->when($angkatan, fn ($q) => $q->where('m.tahun_angkatan', $angkatan));
    }

    /**
     * Daftar mahasiswa ber-MK dobel (paginasi) + rincian KRS dobel untuk halaman tersebut.
     */
    public function daftar(?string $prodi, ?string $angkatan, string $cari, int $perHalaman): LengthAwarePaginator
    {
        $hasil = $this->rekapMahasiswa($prodi, $angkatan)
            ->when($cari !== '', function ($q) use ($cari) {
                $q->where(fn ($w) => $w->where('m.npm', 'like', "%{$cari}%")->orWhere('m.nama_mahasiswa', 'like', "%{$cari}%"));
            })
            ->select('m.npm', 'm.nama_mahasiswa', 'm.tahun_angkatan', 'm.kode_program_studi', 'p.nama_program_studi_idn', 'r.jumlah_mk', 'r.jumlah_krs')
            ->orderBy('p.nama_program_studi_idn')
            ->orderBy('m.npm')
            ->paginate($perHalaman)
            ->withQueryString();

        $rincian = $this->rincian($hasil->pluck('npm')->all());
        $hasil->getCollection()->transform(function ($m) use ($rincian) {
            $m->grup = $rincian->get($m->npm, collect());

            return $m;
        });

        return $hasil;
    }

    /**
     * Jumlah mahasiswa ber-MK dobel per angkatan (untuk dropdown filter).
     */
    public function jumlahPerAngkatan(?string $prodi): Collection
    {
        return $this->rekapMahasiswa($prodi, null)
            ->selectRaw('m.tahun_angkatan, COUNT(*) as jumlah')
            ->groupBy('m.tahun_angkatan')
            ->orderByDesc('m.tahun_angkatan')
            ->pluck('jumlah', 'tahun_angkatan');
    }

    /**
     * Jumlah mahasiswa ber-MK dobel per prodi (untuk dropdown filter).
     */
    public function jumlahPerProdi(): Collection
    {
        return $this->rekapMahasiswa(null, null)
            ->selectRaw('m.kode_program_studi, COUNT(*) as jumlah')
            ->groupBy('m.kode_program_studi')
            ->pluck('jumlah', 'kode_program_studi');
    }

    /**
     * Semua baris KRS dobel milik NPM tertentu, dikelompokkan: npm → kode MK → baris.
     */
    public function rincian(array $npm): Collection
    {
        if (empty($npm)) {
            return collect();
        }

        $grup = DB::query()->fromSub($this->grupDobel()->whereIn('k.npm', $npm), 'g');

        $baris = $this->krsDenganKode()
            ->joinSub($grup, 'g', function ($join) {
                $join->on('g.npm', '=', 'k.npm')->on('g.kode', '=', DB::raw('UPPER(TRIM(mk.kode_mata_kuliah))'));
            })
            ->leftJoin('master_tahun_akademik as ta', 'ta.kode_tahun_akademik', '=', 'k.kode_tahun_akademik')
            ->select(
                'k.id', 'k.npm', 'k.jadwal_id', 'k.kode_tahun_akademik', 'ta.nama_tahun_akademik',
                'k.nilai_sikap', 'k.nilai_kuis', 'k.nilai_uts', 'k.nilai_ku', 'k.nilai_kh', 'k.nilai_uas',
                'k.nilai_angka', 'k.nilai_huruf', 'k.nilai_bobot', 'k.lulus', 'k.created_at',
                'mk.kode_mata_kuliah', 'mk.nama_mata_kuliah_idn', 'mk.sks_mata_kuliah', 'mk.semester',
                DB::raw('g.kode as kode_grup')
            )
            ->orderBy('k.kode_tahun_akademik')
            ->orderBy('k.id')
            ->get();

        return $baris->groupBy('npm')->map(fn ($g) => $g->groupBy('kode_grup'));
    }

    /**
     * Ubah nilai satu KRS dobel. Huruf & bobot dihitung dari skala nilai prodi.
     * Nilai lama dicatat di tbl_wisuda_perbaikan_nilai_log (jenis = dobel).
     *
     * @return string|null alasan jika ditolak, null jika berhasil
     */
    public function update(int $krsId, float $nilaiAngka, ?int $userId): ?string
    {
        $krs = KRS::find($krsId);
        if (! $krs) {
            return 'Data KRS tidak ditemukan.';
        }
        if (! $this->termasukDobel($krs)) {
            return 'Matakuliah ini sudah tidak dobel, ubah nilainya dari menu Mahasiswa.';
        }

        $mahasiswa = Mahasiswa::where('npm', $krs->npm)->first(['kode_program_studi']);
        $skala = $mahasiswa ? SkalaNilai::where('kode_program_studi', $mahasiswa->kode_program_studi)
            ->where('nilai_mulai', '<=', $nilaiAngka)
            ->where('nilai_sampai', '>=', $nilaiAngka)
            ->first() : null;
        if (! $skala) {
            return 'Skala nilai prodi untuk nilai '.$nilaiAngka.' tidak ditemukan.';
        }

        DB::transaction(function () use ($krs, $nilaiAngka, $skala, $userId) {
            KRS::where('id', $krs->id)->update([
                'nilai_angka' => $nilaiAngka,
                'nilai_huruf' => $skala->nama,
                'nilai_bobot' => $skala->bobot,
                'lulus' => $skala->lulus,
                'updated_at' => now(),
            ]);

            DB::table('tbl_wisuda_perbaikan_nilai_log')->insert([
                'krs_id' => $krs->id,
                'npm' => $krs->npm,
                'nilai_angka_lama' => $krs->nilai_angka,
                'nilai_huruf_lama' => $krs->nilai_huruf ?: null,
                'nilai_bobot_lama' => $krs->nilai_bobot,
                'nilai_angka_baru' => $nilaiAngka,
                'nilai_huruf_baru' => $skala->nama,
                'nilai_bobot_baru' => $skala->bobot,
                'jenis' => 'dobel',
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return null;
    }

    /**
     * Hapus permanen satu KRS dobel. Ditolak jika MK tersebut tinggal satu baris,
     * sehingga minimal satu KRS untuk MK itu selalu tersisa. Isi baris disalin
     * ke tbl_wisuda_hapus_krs_log agar bisa dipulihkan.
     *
     * @return string|null alasan jika ditolak, null jika berhasil
     */
    public function hapus(int $krsId, ?int $userId): ?string
    {
        $alasan = null;

        DB::transaction(function () use ($krsId, $userId, &$alasan) {
            $krs = KRS::where('id', $krsId)->lockForUpdate()->first();
            if (! $krs) {
                $alasan = 'Data KRS tidak ditemukan.';

                return;
            }
            if (! $this->termasukDobel($krs)) {
                $alasan = 'Matakuliah ini tinggal satu di KRS, tidak bisa dihapus dari menu ini.';

                return;
            }

            DB::table('tbl_wisuda_hapus_krs_log')->insert([
                'krs_id' => $krs->id,
                'npm' => $krs->npm,
                'data_krs' => json_encode($krs->getAttributes()),
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $krs->delete();
            // Pilihan dobel di menu Wisuda untuk KRS ini ikut dibersihkan
            WisudaPilihanMatakuliah::where('npm', $krs->npm)->where('krs_id', $krs->id)->delete();
        });

        return $alasan;
    }

    /**
     * Apakah kode MK pada KRS ini muncul lebih dari sekali di KRS mahasiswa yang sama.
     */
    private function termasukDobel(KRS $krs): bool
    {
        $kode = $this->krsDenganKode()->where('k.id', $krs->id)
            ->value(DB::raw('UPPER(TRIM(mk.kode_mata_kuliah))'));
        if ($kode === null) {
            return false;
        }

        return $this->krsDenganKode()
            ->where('k.npm', $krs->npm)
            ->whereRaw('UPPER(TRIM(mk.kode_mata_kuliah)) = ?', [$kode])
            ->count() > 1;
    }
}
