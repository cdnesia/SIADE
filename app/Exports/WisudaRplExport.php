<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Export rekap transkrip mahasiswa RPL (data Neo Feeder):
 *  - Sheet "Transkrip" & "Keterangan MK" : format sama dengan rekap wisuda per angkatan.
 *  - Sheet "Detail Transkrip"            : satu baris per matakuliah tiap mahasiswa (konversi & kuliah).
 */
class WisudaRplExport implements WithMultipleSheets
{
    public function __construct(
        private Collection $baris,
        private ?array $kurikulum,
        private Collection $mkKurikulum,
        private array $info,
    ) {}

    public function sheets(): array
    {
        $rekap = new WisudaRekapExport($this->baris, $this->kurikulum, $this->mkKurikulum, $this->info);

        return [...$rekap->sheets(), new WisudaRplDetailSheet($this->baris)];
    }
}
