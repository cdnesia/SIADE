<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet utama: kolom kiri data mahasiswa, kolom kanan nilai huruf per kode MK.
 * Baris 4 = kode MK, baris 5 = nama MK, baris 6 = SKS & semester, data mulai baris 7.
 */
class WisudaRekapTranskripSheet implements WithEvents, WithTitle
{
    private const KOLOM_MHS = ['No', 'NIM', 'Nama Mahasiswa', 'Angkatan', 'IPK', 'Total SKS', 'Syarat SKS', 'Nilai Kosong', 'MK Kurang', 'Status'];

    private const BARIS_HEADER = 4;

    private const BARIS_DATA = 7;

    // Warna (ARGB) — selaras dengan tampilan aplikasi
    private const WARNA_HEADER = 'FF1E3A5F';

    private const WARNA_LUAR = 'FFD97706';

    private const WARNA_KOSONG = 'FFFDE2E2';

    private const WARNA_BELUM_KRS = 'FFE5E7EB';

    private const WARNA_MERAH = 'FFDC2626';

    private const WARNA_HIJAU = 'FF16A34A';

    public function __construct(
        private Collection $baris,
        private array $kolomMk,
        private ?array $kurikulum,
        private array $info,
    ) {}

    public function title(): string
    {
        return 'Transkrip';
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => fn (AfterSheet $e) => $this->tulis($e->sheet->getDelegate())];
    }

    private function tulis(Worksheet $ws): void
    {
        $jumlahMhs = count(self::KOLOM_MHS);
        $kolomAkhir = $jumlahMhs + count($this->kolomMk);
        $hurufAkhir = Coordinate::stringFromColumnIndex($kolomAkhir);
        $barisAkhir = self::BARIS_DATA + $this->baris->count() - 1;

        // ---- Judul & keterangan ----
        $ws->setCellValue('A1', 'REKAP TRANSKRIP NILAI WISUDA');
        $ws->setCellValue('A2', sprintf(
            'Program Studi: %s  ·  Angkatan: %s  ·  Kurikulum: %s  ·  Filter: %s  ·  Dicetak: %s',
            $this->info['prodi'],
            $this->info['angkatan'],
            $this->kurikulum['nama_kurikulum'] ?? 'tidak tersedia (Neo Feeder gagal dimuat)',
            $this->info['filter'],
            now()->translatedFormat('d F Y H:i')
        ));
        $ws->setCellValue('A3', 'Keterangan: "Kosong" = sudah ada KRS tapi nilai belum diisi  ·  sel abu-abu = MK kurikulum belum ada KRS (kurang)  ·  huruf merah = tidak lulus  ·  kode oranye = di luar kurikulum. Detail kode lihat sheet "Keterangan MK".');
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $ws->getStyle('A2:A3')->getFont()->setSize(9)->getColor()->setARGB('FF6B7280');

        // ---- Header data mahasiswa (digabung 3 baris) ----
        foreach (self::KOLOM_MHS as $i => $label) {
            $h = Coordinate::stringFromColumnIndex($i + 1);
            $ws->setCellValue($h.self::BARIS_HEADER, $label);
            $ws->mergeCells($h.self::BARIS_HEADER.':'.$h.(self::BARIS_DATA - 1));
        }

        // ---- Header MK: kode, nama, SKS · semester ----
        foreach ($this->kolomMk as $i => $mk) {
            $h = Coordinate::stringFromColumnIndex($jumlahMhs + $i + 1);
            $ws->setCellValueExplicit($h.self::BARIS_HEADER, $mk['kode'], DataType::TYPE_STRING);
            $ws->setCellValue($h.(self::BARIS_HEADER + 1), $mk['nama']);
            $ws->setCellValue($h.(self::BARIS_HEADER + 2), $this->angka($mk['sks']).' SKS'.($mk['semester'] ? ' · Smt '.$mk['semester'] : ''));
            $ws->getColumnDimension($h)->setWidth(11);
            if ($mk['luar']) {
                $ws->getStyle($h.self::BARIS_HEADER.':'.$h.(self::BARIS_DATA - 1))
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::WARNA_LUAR);
            }
        }

        $rangeHeader = 'A'.self::BARIS_HEADER.':'.$hurufAkhir.(self::BARIS_DATA - 1);
        $ws->getStyle($rangeHeader)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        // Warna dasar header (kolom "luar kurikulum" tetap oranye)
        foreach (range(1, $kolomAkhir) as $c) {
            $h = Coordinate::stringFromColumnIndex($c);
            $isi = $ws->getStyle($h.self::BARIS_HEADER)->getFill();
            if ($isi->getFillType() !== Fill::FILL_SOLID) {
                $ws->getStyle($h.self::BARIS_HEADER.':'.$h.(self::BARIS_DATA - 1))
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::WARNA_HEADER);
            }
        }
        $ws->getStyle('A'.(self::BARIS_HEADER + 1).':'.$hurufAkhir.(self::BARIS_HEADER + 2))->getFont()->setBold(false)->setSize(8);
        $ws->getRowDimension(self::BARIS_HEADER + 1)->setRowHeight(48);

        // ---- Data per mahasiswa ----
        $r = self::BARIS_DATA;
        foreach ($this->baris->values() as $no => $b) {
            $ring = $b['ringkasan'];
            $ws->setCellValue('A'.$r, $no + 1);
            $ws->setCellValueExplicit('B'.$r, $b['npm'], DataType::TYPE_STRING);
            $ws->setCellValue('C'.$r, $b['nama']);
            $ws->setCellValue('D'.$r, substr((string) $b['angkatan'], 0, 4));
            $ws->setCellValue('E'.$r, $ring['total_sks'] > 0 ? (float) $ring['ipk'] : null);
            $ws->setCellValue('F'.$r, $ring['total_sks']);
            $ws->setCellValue('G'.$r, $ring['syarat_sks_lulus'] ?? null);
            $ws->setCellValue('H'.$r, $ring['tanpa_nilai']);
            $ws->setCellValue('I'.$r, $ring['mk_kurang'] ?? null);
            if (($ring['mk_kurang'] ?? 0) > 0) {
                $ws->getStyle('I'.$r)->getFont()->setBold(true)->getColor()->setARGB(self::WARNA_MERAH);
            }
            $ws->setCellValue('J'.$r, $b['siap'] ? 'Siap wisuda' : 'Perlu dicek');
            $ws->getStyle('J'.$r)->getFont()->setBold(true)->getColor()->setARGB($b['siap'] ? self::WARNA_HIJAU : self::WARNA_MERAH);
            if ($ring['tanpa_nilai'] > 0) {
                $ws->getStyle('H'.$r)->getFont()->setBold(true)->getColor()->setARGB(self::WARNA_MERAH);
            }
            if (isset($ring['syarat_sks_lulus']) && $ring['total_sks'] < $ring['syarat_sks_lulus']) {
                $ws->getStyle('F'.$r)->getFont()->setBold(true)->getColor()->setARGB(self::WARNA_MERAH);
            }

            // Nilai per kode MK (hanya baris transkrip yang diambil/dihitung)
            $perKode = $b['transkrip']->where('diambil', true)->groupBy(fn ($t) => strtoupper(trim($t['kode_mk'])));
            foreach ($this->kolomMk as $i => $mk) {
                $sel = Coordinate::stringFromColumnIndex($jumlahMhs + $i + 1).$r;
                $isian = $perKode->get($mk['kode']);
                if (! $isian) {
                    if (! $mk['luar']) {
                        $ws->getStyle($sel)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::WARNA_BELUM_KRS);
                    }

                    continue;
                }
                $bernilai = $isian->firstWhere('punya_nilai', true);
                if ($bernilai) {
                    $ws->setCellValueExplicit($sel, $bernilai['nilai_huruf'], DataType::TYPE_STRING);
                    if ($bernilai['tidak_lulus']) {
                        $ws->getStyle($sel)->getFont()->setBold(true)->getColor()->setARGB(self::WARNA_MERAH);
                    }
                } else {
                    $ws->setCellValue($sel, 'Kosong');
                    $ws->getStyle($sel)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::WARNA_KOSONG);
                    $ws->getStyle($sel)->getFont()->setBold(true)->setSize(8)->getColor()->setARGB(self::WARNA_MERAH);
                }
            }
            $r++;
        }

        // ---- Format umum ----
        if ($barisAkhir >= self::BARIS_DATA) {
            $ws->getStyle('E'.self::BARIS_DATA.':E'.$barisAkhir)->getNumberFormat()->setFormatCode('0.00');
            $ws->getStyle('A'.self::BARIS_DATA.':A'.$barisAkhir)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $ws->getStyle('D'.self::BARIS_DATA.':'.$hurufAkhir.$barisAkhir)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $ws->getStyle('A'.self::BARIS_DATA.':'.$hurufAkhir.$barisAkhir)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }
        $ws->getStyle('A'.self::BARIS_HEADER.':'.$hurufAkhir.max($barisAkhir, self::BARIS_DATA - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFD1D5DB');

        $lebar = ['A' => 5, 'B' => 17, 'C' => 30, 'D' => 9, 'E' => 7, 'F' => 8, 'G' => 8, 'H' => 8, 'I' => 9, 'J' => 13];
        foreach ($lebar as $h => $w) {
            $ws->getColumnDimension($h)->setWidth($w);
        }

        // Kunci kolom data mahasiswa & header saat digeser ke kanan/bawah
        $ws->freezePane(Coordinate::stringFromColumnIndex($jumlahMhs + 1).self::BARIS_DATA);
        $ws->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
    }

    private function angka(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }
}
