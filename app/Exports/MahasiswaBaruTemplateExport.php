<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MahasiswaBaruTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithStyles
{
    public function array(): array
    {
        return [
            ['BUDI SANTOSO', 'S12610001', '20261', 'S1 Informatika', 'Reguler A', 'Peserta didik baru'],
        ];
    }

    public function headings(): array
    {
        return ['nama_mahasiswa', 'npm', 'tahun_angkatan', 'program_studi', 'kelas', 'jenis_pendaftaran'];
    }

    public function columnFormats(): array
    {
        // NPM & angkatan sebagai teks agar tidak berubah jadi angka/notasi ilmiah
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
