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
            ['BUDI SANTOSO', 'S12610001', '20261', 'S1 Informatika', 'Reguler A', 'Peserta didik baru', 'L', 'Jambi', '15/08/2007', '1571011508070001', '0071234567', '10501234', '', 'budi@example.com', '081234567890', 'SUTRISNO', 'SITI AMINAH', ''],
        ];
    }

    public function headings(): array
    {
        return [
            'nama_mahasiswa', 'npm', 'tahun_angkatan', 'program_studi', 'kelas', 'jenis_pendaftaran',
            'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'nik', 'nisn', 'npsn', 'no_kipk',
            'email', 'handphone', 'nama_ayah', 'nama_ibu_kandung', 'nama_wali',
        ];
    }

    public function columnFormats(): array
    {
        // Kolom berisi angka panjang dijadikan teks agar tidak berubah jadi notasi ilmiah / kehilangan angka 0 di depan
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'I' => NumberFormat::FORMAT_TEXT,
            'J' => NumberFormat::FORMAT_TEXT,
            'K' => NumberFormat::FORMAT_TEXT,
            'L' => NumberFormat::FORMAT_TEXT,
            'M' => NumberFormat::FORMAT_TEXT,
            'O' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
