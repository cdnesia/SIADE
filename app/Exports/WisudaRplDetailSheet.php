<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Detail transkrip RPL untuk diisi manual: satu baris per matakuliah kurikulum per mahasiswa.
 * Nilai terisi = hasil konversi RPL di Neo Feeder; baris kuning = belum ada nilai, isi manual.
 * Kolom Mutu berisi rumus Indeks × SKS sehingga ikut terhitung setelah indeks diisi.
 */
class WisudaRplDetailSheet implements WithEvents, WithTitle
{
    private const JUDUL = ['No', 'NIM', 'Nama Mahasiswa', 'Periode Masuk', 'Semester', 'Kode MK', 'Matakuliah', 'SKS',
        'Sumber Nilai', 'Nilai Huruf', 'Indeks', 'Mutu', 'Keterangan'];

    private const WARNA_ISI_MANUAL = 'FFFEF9C3';

    public function __construct(private Collection $baris) {}

    public function title(): string
    {
        return 'Detail Transkrip';
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => fn (AfterSheet $e) => $this->tulis($e->sheet->getDelegate())];
    }

    private function tulis(Worksheet $ws): void
    {
        $ws->fromArray(self::JUDUL, null, 'A1');
        $r = 2;
        $no = 0;
        foreach ($this->baris as $m) {
            foreach ($this->barisMahasiswa($m) as $b) {
                $ws->fromArray([++$no], null, 'A'.$r);
                $ws->setCellValueExplicit('B'.$r, $m['npm'], DataType::TYPE_STRING);
                $ws->fromArray([$m['nama'], $m['angkatan'], $b['semester'], $b['kode'], $b['nama'], $b['sks'], $b['sumber']], null, 'C'.$r);
                if ($b['konversi']) {
                    $ws->fromArray([$b['huruf'], $b['indeks']], null, 'J'.$r);
                } else {
                    // Baris untuk diisi manual: Nilai Huruf & Indeks dikosongkan
                    $ws->getStyle('J'.$r.':K'.$r)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::WARNA_ISI_MANUAL);
                }
                $ws->setCellValue('L'.$r, '=IF(K'.$r.'="","",K'.$r.'*H'.$r.')');
                $ws->setCellValue('M'.$r, $b['keterangan']);
                $r++;
            }
        }

        $ws->getStyle('A1:M1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $ws->getStyle('A1:M1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A5F');
        foreach (['A' => 6, 'B' => 14, 'C' => 28, 'D' => 12, 'E' => 9, 'F' => 11, 'G' => 40, 'H' => 6, 'I' => 14, 'J' => 11, 'K' => 8, 'L' => 8, 'M' => 50] as $h => $w) {
            $ws->getColumnDimension($h)->setWidth($w);
        }
        $ws->freezePane('E2');
        $ws->setAutoFilter('A1:M'.max(1, $r - 1));
    }

    /**
     * Semua MK kurikulum (urut semester) diisi dari konversi bila ada, lalu MK konversi
     * yang tidak ada di kurikulum. Tanpa kurikulum: hanya baris konversi.
     */
    private function barisMahasiswa(array $m): array
    {
        $konversi = $m['transkrip']->where('diambil', true)->keyBy('kode_mk');
        $hasil = [];

        foreach ($m['mk_kurikulum']->sortBy([['semester', 'asc'], ['kode_mk', 'asc']]) as $mk) {
            $k = $konversi->pull($mk['kode_mk']);
            $hasil[] = $k
                ? $this->dariKonversi($k, $mk['semester'], $mk['wajib'] ? 'Wajib' : 'Pilihan')
                : [
                    'semester' => $mk['semester'], 'kode' => $mk['kode_mk'], 'nama' => $mk['nama_mk'], 'sks' => $mk['sks'],
                    'sumber' => 'Isi manual', 'konversi' => false, 'huruf' => null, 'indeks' => null,
                    'keterangan' => ($mk['wajib'] ? 'Wajib' : 'Pilihan').' · belum ada nilai konversi',
                ];
        }

        foreach ($konversi as $k) {
            $hasil[] = $this->dariKonversi($k, null, $m['mk_kurikulum']->isEmpty() ? null : 'Di luar kurikulum');
        }

        return $hasil;
    }

    private function dariKonversi(array $k, ?int $semester, ?string $jenis): array
    {
        return [
            'semester' => $semester, 'kode' => $k['kode_mk'], 'nama' => $k['nama_mk'], 'sks' => $k['sks'],
            'sumber' => 'Konversi RPL', 'konversi' => true, 'huruf' => $k['nilai_huruf'], 'indeks' => $k['nilai_bobot'],
            'keterangan' => implode(' · ', array_filter([$jenis, $k['asal'] ? 'Asal: '.$k['asal'] : null, $k['tidak_lulus'] ? 'Tidak lulus' : null])),
        ];
    }
}
