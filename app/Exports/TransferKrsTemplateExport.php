<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TransferKrsTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function array(): array
    {
        return [
            ['S12345678', '20251', 'IF101', '85'],
        ];
    }

    public function headings(): array
    {
        return ['npm', 'kode_tahun_akademik', 'kode_mata_kuliah', 'nilai_angka'];
    }
}
