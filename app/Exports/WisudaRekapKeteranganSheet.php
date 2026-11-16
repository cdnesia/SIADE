<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Sheet pendamping: arti setiap kode MK yang menjadi header di sheet "Transkrip".
 */
class WisudaRekapKeteranganSheet implements FromArray, WithEvents, WithHeadings, WithTitle
{
    public function __construct(private array $kolomMk, private ?array $kurikulum) {}

    public function title(): string
    {
        return 'Keterangan MK';
    }

    public function headings(): array
    {
        return ['No', 'Kode MK', 'Nama Matakuliah', 'SKS', 'Semester', 'Jenis', 'Sumber'];
    }

    public function array(): array
    {
        return collect($this->kolomMk)->values()->map(fn ($mk, $i) => [
            $i + 1,
            $mk['kode'],
            $mk['nama'],
            $mk['sks'],
            $mk['semester'],
            $mk['wajib'] === null ? '-' : ($mk['wajib'] ? 'Wajib' : 'Pilihan'),
            $mk['luar'] ? 'Di luar kurikulum' : ($this->kurikulum['nama_kurikulum'] ?? 'Transkrip'),
        ])->all();
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $ws = $e->sheet->getDelegate();
            $ws->getStyle('A1:G1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $ws->getStyle('A1:G1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A5F');
            foreach (['A' => 5, 'B' => 12, 'C' => 45, 'D' => 6, 'E' => 9, 'F' => 9, 'G' => 34] as $h => $w) {
                $ws->getColumnDimension($h)->setWidth($w);
            }
            $ws->freezePane('A2');
        }];
    }
}
