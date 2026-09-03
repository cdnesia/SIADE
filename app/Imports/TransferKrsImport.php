<?php

namespace App\Imports;

use App\Models\KurikulumMataKuliah;
use App\Models\KurikulumProdi;
use App\Models\Mahasiswa;
use App\Models\SkalaNilai;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TransferKrsImport implements ToCollection, WithHeadingRow
{
    /** @var string[] */
    public array $errors = [];

    public int $berhasil = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $baris = $index + 2; // +1 heading row, +1 karena index mulai dari 0

            $npm = trim((string) ($row['npm'] ?? ''));
            $kodeTahunAkademik = trim((string) ($row['kode_tahun_akademik'] ?? ''));
            $kodeMataKuliah = trim((string) ($row['kode_mata_kuliah'] ?? ''));
            $nilaiAngka = $row['nilai_angka'] ?? null;

            if ($npm === '' && $kodeTahunAkademik === '' && $kodeMataKuliah === '' && $nilaiAngka === null) {
                continue; // baris kosong
            }

            if ($npm === '' || $kodeTahunAkademik === '' || $kodeMataKuliah === '' || $nilaiAngka === null || $nilaiAngka === '') {
                $this->errors[] = "Baris {$baris}: kolom npm, kode_tahun_akademik, kode_mata_kuliah, dan nilai_angka wajib diisi";
                continue;
            }

            if (!is_numeric($nilaiAngka) || $nilaiAngka < 0 || $nilaiAngka > 100) {
                $this->errors[] = "Baris {$baris}: nilai_angka harus berupa angka 0-100";
                continue;
            }

            $mahasiswa = Mahasiswa::where('npm', $npm)->first();
            if (!$mahasiswa) {
                $this->errors[] = "Baris {$baris}: NPM {$npm} tidak ditemukan";
                continue;
            }

            $kurikulumId = KurikulumProdi::whereJsonContains('tahun_angkatan', (int) $mahasiswa->tahun_angkatan)
                ->where('kode_program_studi', $mahasiswa->kode_program_studi)
                ->pluck('kurikulum_id')
                ->first();

            $mataKuliah = KurikulumMataKuliah::where('kode_program_studi', $mahasiswa->kode_program_studi)
                ->where('kurikulum_id', $kurikulumId)
                ->where('kode_mata_kuliah', $kodeMataKuliah)
                ->first();

            if (!$mataKuliah) {
                $this->errors[] = "Baris {$baris}: Kode mata kuliah {$kodeMataKuliah} tidak ditemukan di kurikulum prodi mahasiswa NPM {$npm}";
                continue;
            }

            $skalaNilai = SkalaNilai::where('nilai_mulai', '<=', $nilaiAngka)
                ->where('nilai_sampai', '>=', $nilaiAngka)
                ->where('kode_program_studi', $mahasiswa->kode_program_studi)
                ->first();

            if (!$skalaNilai) {
                $this->errors[] = "Baris {$baris}: Skala nilai untuk angka {$nilaiAngka} tidak ditemukan pada prodi mahasiswa NPM {$npm}";
                continue;
            }

            $kunci = [
                'npm' => $npm,
                'jadwal_id' => 0,
                'mata_kuliah_id' => $mataKuliah->id,
                'kode_tahun_akademik' => $kodeTahunAkademik,
            ];

            $nilai = [
                'nilai_angka' => $nilaiAngka,
                'nilai_huruf' => $skalaNilai->nama,
                'nilai_bobot' => $skalaNilai->bobot,
                'lulus' => $skalaNilai->lulus,
                'persetujuan_pa' => 'Y',
                'datetime_persetujuan_pa' => now(),
                'updated_at' => now(),
            ];

            $existing = DB::table('tbl_mahasiswa_krs')->where($kunci)->first();

            if ($existing) {
                DB::table('tbl_mahasiswa_krs')->where('id', $existing->id)->update($nilai);
            } else {
                DB::table('tbl_mahasiswa_krs')->insert(array_merge($kunci, $nilai, [
                    'created_at' => now(),
                ]));
            }

            $this->berhasil++;
        }
    }
}
