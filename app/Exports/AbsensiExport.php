<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AbsensiExport implements FromView, WithStyles
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

        $krs = DB::table('krs')
            ->select('nim', 'NamaMhsw')
            ->where('JadwalID', $this->id)
            ->orderBy('nim', 'ASC')
            ->get();

        $pertemuan = DB::table('jadwal_pertemuan')
            ->select('id', 'pertemuan', 'tanggal_pelaksanaan', 'bahan_kajian', 'id_jadwal')
            ->where('id_jadwal', $this->id)
            ->where('NA', 'A')
            ->orderBy('pertemuan', 'ASC')
            ->get()
            ->keyBy('id');

        $absensi = DB::table('jadwal_pertemuan_absensi as a')
            ->join('master_status_kehadiran as b', 'a.id_status_kehadiran', '=', 'b.id')
            ->select('a.nim', 'a.id_jadwal_pertemuan', 'b.nama as status_kehadiran')
            ->whereIn('a.id_jadwal_pertemuan', $pertemuan->pluck('id'))
            ->get();


        $result = [];

        foreach ($krs as $mhs) {
            $mhsPertemuan = [];
            foreach ($pertemuan as $p) {
                $pAbsensi = $absensi->filter(function ($a) use ($mhs, $p) {
                    return $a->nim == $mhs->nim && $a->id_jadwal_pertemuan == $p->id;
                })->values()->first()->status_kehadiran;

                $mhsPertemuan[] = [
                    'pertemuan' => $p->pertemuan,
                    'tanggal_pelaksanaan' => $p->tanggal_pelaksanaan,
                    'bahan_kajian' => $p->bahan_kajian,
                    'absensi' => $pAbsensi
                ];
            }

            $result[] = [
                'nim' => $mhs->nim,
                'NamaMhsw' => $mhs->NamaMhsw,
                'pertemuan' => $mhsPertemuan
            ];
        }

        return view('exports.absensi', compact(
            'jadwal',
            'kaprodi',
            'pertemuan',
            'result'
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

        $sheet->mergeCells("A1:{$highestColumn}1");

        $sheet->getStyle("A1")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("A12:{$highestColumn}12")
            ->getFont()
            ->setBold(true);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(35);

        foreach (range('D', $highestColumn) as $column) {
            $sheet->getColumnDimension($column)->setWidth(15);
        }

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
