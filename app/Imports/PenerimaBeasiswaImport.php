<?php

namespace App\Imports;

use App\Models\PenerimaBeasiswa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PenerimaBeasiswaImport implements ToCollection, WithHeadingRow
{
    /** @var string[] */
    public array $errors = [];

    public int $berhasil = 0;

    public int $digabung = 0;

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            $this->mergeDuplicates();

            // Preload semua data sekali (bukan query per baris) supaya import cepat untuk data banyak.
            $existing = PenerimaBeasiswa::all()->groupBy('npm');

            foreach ($rows as $index => $row) {
                $baris = $index + 2; // +1 heading row, +1 karena index mulai dari 0

                $npm = trim((string) ($row['npm'] ?? ''));
                $tahunAkademik = trim((string) ($row['tahun_akademik'] ?? ''));

                if ($npm === '' && $tahunAkademik === '') {
                    continue; // baris kosong
                }

                if ($npm === '' || $tahunAkademik === '') {
                    $this->errors[] = "Baris {$baris}: kolom npm dan tahun_akademik wajib diisi";
                    continue;
                }

                $penerima = $existing->get($npm);

                if (!$penerima || $penerima->isEmpty()) {
                    $this->errors[] = "Baris {$baris}: NPM {$npm} belum terdaftar sebagai penerima beasiswa";
                    continue;
                }

                foreach ($penerima as $item) {
                    $tahun = json_decode($item->tahun_akademik, true);
                    if (!is_array($tahun)) {
                        $tahun = [];
                    }

                    if (!in_array($tahunAkademik, $tahun, true)) {
                        $tahun[] = $tahunAkademik;
                        rsort($tahun);
                        $item->tahun_akademik = json_encode($tahun);
                        $item->save();
                    }
                }

                $this->berhasil++;
            }
        });
    }

    /**
     * Gabungkan baris yang npm + nama mahasiswa + id_lembaga-nya sama jadi satu
     * baris saja. tahun_akademik digabung (union), jumlah_jaminan diambil dari
     * baris terbaru (id terbesar), baris duplikat lainnya dihapus permanen.
     */
    private function mergeDuplicates(): void
    {
        $kelompokDuplikat = PenerimaBeasiswa::query()
            ->select('tbl_penerima_beasiswa.npm', 'tbl_penerima_beasiswa.id_lembaga')
            ->leftJoin('master_mahasiswa', 'master_mahasiswa.npm', '=', 'tbl_penerima_beasiswa.npm')
            ->groupBy('tbl_penerima_beasiswa.npm', 'tbl_penerima_beasiswa.id_lembaga', 'master_mahasiswa.nama_mahasiswa')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($kelompokDuplikat as $key) {
            $rows = PenerimaBeasiswa::where('npm', $key->npm)
                ->where('id_lembaga', $key->id_lembaga)
                ->orderByDesc('id')
                ->get();

            $terbaru = $rows->first();

            $tahunGabungan = [];
            foreach ($rows as $row) {
                $tahun = json_decode($row->tahun_akademik, true);
                if (is_array($tahun)) {
                    $tahunGabungan = array_merge($tahunGabungan, $tahun);
                }
            }
            $tahunGabungan = array_values(array_unique($tahunGabungan));
            rsort($tahunGabungan);

            $terbaru->tahun_akademik = json_encode($tahunGabungan);
            $terbaru->save();

            $idHapus = $rows->where('id', '!=', $terbaru->id)->pluck('id');
            PenerimaBeasiswa::whereIn('id', $idHapus)->delete();

            $this->digabung += $idHapus->count();
        }
    }
}
