<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KurikulumMataKuliahPertemuan extends Model
{
    protected $table = 'master_kurikulum_matakuliah_pertemuan';

    public const TIDAK_DINILAI = 'TIDAK_DINILAI';

    // Daftar jenis penilaian: kode => label
    public const JENIS = [
        'TIDAK_DINILAI' => 'Tidak Dinilai',
        'TUGAS'         => 'Tugas',
        'KUIS'          => 'Kuis',
        'PRAKTIKUM'     => 'Praktikum',
        'PRESENTASI'    => 'Presentasi',
        'PROYEK'        => 'Proyek',
        'KEHADIRAN'     => 'Kehadiran / Sikap',
        'UTS'           => 'UTS',
        'UAS'           => 'UAS',
    ];

    protected $fillable = [
        'mata_kuliah_id',
        'pertemuan',
        'jenis_penilaian',
        'bobot',
        'keterangan',
    ];

    protected $casts = [
        'pertemuan' => 'integer',
        'bobot'     => 'float',
    ];

    public function mataKuliah()
    {
        return $this->belongsTo(KurikulumMataKuliah::class, 'mata_kuliah_id', 'id');
    }

    public function getJenisLabelAttribute(): string
    {
        return self::JENIS[$this->jenis_penilaian] ?? $this->jenis_penilaian;
    }
}
