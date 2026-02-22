<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BAPExport implements FromView, WithStyles, ShouldAutoSize
{
    protected $id;
    protected $service;

    public function __construct($id, $service)
    {
        $this->id = Crypt::decrypt($id);
        $this->service = $service;
    }

    public function view(): View
    {
        $jadwal = DB::table('jadwal_kuliah')
            ->where('id', $this->id)
            ->first();

        $kaprodi = $this->service->kaprodi($jadwal->ProdiID);

        $pertemuan = DB::table('jadwal_pertemuan')
            ->where('id_jadwal', $this->id)
            ->where('NA', 'A')
            ->orderBy('pertemuan', 'ASC')
            ->get();

        return view('exports.bap', compact(
            'jadwal',
            'kaprodi',
            'pertemuan'
        ));
    }
    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
            ->getFont()
            ->setName('Calibri')
            ->setSize(12);

        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A9:G9')->getFont()->setBold(true);


        $sheet->getRowDimension($highestRow)->setRowHeight(100);
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 18,
                ],
            ],
        ];
    }
}
