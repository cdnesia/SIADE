<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PenerimaBeasiswaTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function array(): array
    {
        return [
            ['S12340000', '20261'],
        ];
    }

    public function headings(): array
    {
        return ['npm', 'tahun_akademik'];
    }
}
