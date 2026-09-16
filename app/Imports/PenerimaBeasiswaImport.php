<?php

namespace App\Imports;

use App\Models\PenerimaBeasiswa;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PenerimaBeasiswaImport implements ToCollection, WithHeadingRow
{
    /** @var string[] */
    public array $errors = [];

    public int $berhasil = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $baris = $index + 2; // +1 heading row, +1 karena index mulai dari 0

            $npm = trim((string) ($row['npm'] ?? ''));
            $tahunAkademik = trim((string) ($row['tahun_akademik'] ?? ''));

            if ($npm === '' && $tahunAkademik === '') {
                continue; // baris kosong
            }

            if ($npm === '' || $tahunAkademik === '') {
                $this->errors[] = "Baris {$baris}: kolom npm dan tahun_akademik wajib diisi";
                continue;
            }

            $penerima = PenerimaBeasiswa::where('npm', $npm)->get();

            if ($penerima->isEmpty()) {
                $this->errors[] = "Baris {$baris}: NPM {$npm} belum terdaftar sebagai penerima beasiswa";
                continue;
            }

            foreach ($penerima as $item) {
                $tahun = json_decode($item->tahun_akademik, true);
                if (!is_array($tahun)) {
                    $tahun = [];
                }

                if (!in_array($tahunAkademik, $tahun, true)) {
                    $tahun[] = $tahunAkademik;
                    rsort($tahun);
                    $item->tahun_akademik = json_encode($tahun);
                    $item->save();
                }
            }

            $this->berhasil++;
        }
    }
}
