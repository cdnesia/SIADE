<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MahasiswaBaruExport implements FromArray, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithStyles
{
    public function __construct(protected array $rows) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['No', 'NIM', 'No. Pendaftaran', 'Nama', 'Jenjang', 'Program Studi', 'Kelas', 'Gelombang'];
    }

    public function columnFormats(): array
    {
        // NIM & nomor pendaftaran sebagai teks agar tidak berubah jadi angka/notasi ilmiah
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
