<?php

namespace App\Imports;

use App\Support\AturanMahasiswa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Membaca & memvalidasi file import mahasiswa baru tanpa menyimpan apa pun.
 * Hasilnya ($valid dan $errors) ditampilkan sebagai pratinjau, penyimpanan
 * baru dilakukan setelah dikonfirmasi (lihat ImportMahasiswaBaruController::simpan).
 */
class MahasiswaBaruImport implements ToCollection, WithHeadingRow
{
    const MAKS_BARIS = 2000;

    /** Kolom teks yang langsung diambil apa adanya dari file */
    const KOLOM_TEKS = [
        'tempat_lahir', 'nik', 'nisn', 'npsn', 'no_kipk', 'email', 'handphone',
        'nama_ayah', 'nama_ibu_kandung', 'nama_wali',
    ];

    /** @var array<int, array> baris siap simpan ke master_mahasiswa, beserta info tampilan */
    public array $valid = [];

    /** @var array<int, array{baris: int, npm: string, nama: string, pesan: string[]}> */
    public array $errors = [];

    public int $total = 0;

    public bool $melebihiBatas = false;

    public function collection(Collection $rows)
    {
        $rows = $rows->filter(fn($row) => collect($row)->filter(fn($v) => $this->teks($v) !== '')->isNotEmpty());
        $this->total = $rows->count();

        if ($this->total > self::MAKS_BARIS) {
            $this->melebihiBatas = true;
            return;
        }

        // Prodi, kelas & jenis pendaftaran boleh diisi kode/ID atau nama (tidak peka huruf besar/kecil)
        $prodi = DB::table('master_program_studi')->get(['kode_program_studi as id', 'nama_program_studi_idn as nama']);
        $kelas = DB::table('master_kelas_perkuliahan')->get(['id', 'nama_program_perkuliahan as nama']);
        $jenis = DB::table('master_jenis_pendaftaran')->get(['id', 'nama_jenis_pendaftaran as nama']);
        [$cariProdi, $namaProdi] = $this->pemetaan($prodi);
        [$cariKelas, $namaKelas] = $this->pemetaan($kelas);
        [$cariJenis, $namaJenis] = $this->pemetaan($jenis);

        $npmDiFile = [];
        $nikDiFile = [];

        foreach ($rows as $index => $row) {
            $baris = $index + 2; // +1 heading row, +1 karena index mulai dari 0

            $data = [
                'nama_mahasiswa' => mb_strtoupper($this->teks($row['nama_mahasiswa'] ?? '')),
                'npm' => strtoupper($this->teks($row['npm'] ?? '')),
                'tahun_angkatan' => $this->teks($row['tahun_angkatan'] ?? ''),
                'kode_program_studi' => $this->cocokkan($cariProdi, $row['program_studi'] ?? ''),
                'program_kuliah_id' => $this->cocokkan($cariKelas, $row['kelas'] ?? ''),
                // Default Peserta didik baru jika kolom dikosongkan
                'jenis_pendaftaran_id' => $this->teks($row['jenis_pendaftaran'] ?? '') === ''
                    ? 1 : $this->cocokkan($cariJenis, $row['jenis_pendaftaran']),
                'jenis_kelamin' => $this->jenisKelamin($row['jenis_kelamin'] ?? ''),
                'tanggal_lahir' => $this->tanggal($row['tanggal_lahir'] ?? ''),
            ];
            foreach (self::KOLOM_TEKS as $kolom) {
                $data[$kolom] = $this->teks($row[$kolom] ?? '');
            }
            $data = array_map(fn($v) => $v === '' ? null : $v, $data);

            $validator = validator($data, AturanMahasiswa::rules(), AturanMahasiswa::messages(), AturanMahasiswa::attributes());
            $pesan = $validator->errors()->all();

            // Nilai prodi/kelas/jenis yang tidak dikenali diberi pesan yang menyebut isian aslinya
            foreach (['program_studi' => 'kode_program_studi', 'kelas' => 'program_kuliah_id', 'jenis_pendaftaran' => 'jenis_pendaftaran_id'] as $kolom => $field) {
                $isian = $this->teks($row[$kolom] ?? '');
                if ($isian !== '' && $data[$field] === null) {
                    $pesan = array_values(array_diff($pesan, $validator->errors()->get($field)));
                    $pesan[] = AturanMahasiswa::attributes()[$field] . " \"{$isian}\" tidak ditemukan.";
                }
            }

            if ($data['npm'] && isset($npmDiFile[$data['npm']])) {
                $pesan[] = "NPM {$data['npm']} ganda di file (sama dengan baris {$npmDiFile[$data['npm']]}).";
            }
            if ($data['nik'] && isset($nikDiFile[$data['nik']])) {
                $pesan[] = "NIK {$data['nik']} ganda di file (sama dengan baris {$nikDiFile[$data['nik']]}).";
            }
            if ($data['npm']) {
                $npmDiFile[$data['npm']] ??= $baris;
            }
            if ($data['nik']) {
                $nikDiFile[$data['nik']] ??= $baris;
            }

            if ($pesan) {
                $this->errors[] = [
                    'baris' => $baris,
                    'npm' => $data['npm'] ?? '-',
                    'nama' => $data['nama_mahasiswa'] ?? '-',
                    'pesan' => $pesan,
                ];
                continue;
            }

            $this->valid[] = [
                'baris' => $baris,
                'data' => $data,
                'nama_prodi' => $namaProdi[$data['kode_program_studi']] ?? '-',
                'nama_kelas' => $namaKelas[$data['program_kuliah_id']] ?? '-',
                'nama_jenis' => $namaJenis[$data['jenis_pendaftaran_id']] ?? '-',
            ];
        }
    }

    /**
     * @return array{0: array<string, string>, 1: array<string, string>} [isian lowercase => id, id => nama]
     */
    private function pemetaan(Collection $master): array
    {
        $cari = [];
        foreach ($master as $m) {
            $cari[strtolower(trim($m->nama))] = (string) $m->id;
            $cari[strtolower((string) $m->id)] = (string) $m->id;
        }

        return [$cari, $master->pluck('nama', 'id')->all()];
    }

    private function cocokkan(array $cari, $nilai): ?string
    {
        return $cari[strtolower($this->teks($nilai))] ?? null;
    }

    /**
     * Angka dari Excel (mis. NPM/NIK di sel bertipe angka) dikembalikan tanpa desimal/notasi ilmiah.
     */
    private function teks($nilai): string
    {
        if (is_float($nilai) && floor($nilai) == $nilai) {
            $nilai = sprintf('%.0f', $nilai);
        }

        return trim((string) $nilai);
    }

    private function jenisKelamin($nilai): string
    {
        $nilai = strtoupper($this->teks($nilai));

        return match (true) {
            in_array($nilai, ['L', 'LAKI-LAKI', 'LAKI LAKI', 'PRIA'], true) => 'L',
            in_array($nilai, ['P', 'PEREMPUAN', 'WANITA'], true) => 'P',
            default => $nilai, // biarkan validator menolak isian lain
        };
    }

    /**
     * Terima tanggal Excel (angka serial) maupun teks dd/mm/yyyy, dd-mm-yyyy, yyyy-mm-dd.
     */
    private function tanggal($nilai): string
    {
        if (is_numeric($nilai)) {
            return ExcelDate::excelToDateTimeObject($nilai)->format('Y-m-d');
        }

        $nilai = $this->teks($nilai);
        foreach (['j/n/Y', 'j-n-Y', 'Y-n-j'] as $format) {
            $tanggal = \DateTime::createFromFormat('!' . $format, $nilai);
            // getLastErrors() menangkap tanggal meluap seperti 31/02/2005
            if ($tanggal && !\DateTime::getLastErrors()) {
                return $tanggal->format('Y-m-d');
            }
        }

        return $nilai;
    }
}
