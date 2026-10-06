<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KurikulumMataKuliah extends Model
{
    protected $table='master_kurikulum_matakuliah';

    protected $fillable = [
        'kurikulum_id',
        'kode_program_studi',
        'kode_mata_kuliah',
        'nama_mata_kuliah_idn',
        'nama_mata_kuliah_eng',
        'mata_kuliah_tipe',
        'sks_tatap_muka',
        'sks_praktek',
        'sks_prak_lap',
        'sks_simulasi',
        'sks_mata_kuliah',
        'semester',
        'min_pertemuan',
        'max_pertemuan',
        'jenis_matakuliah_id',
        'is_mbkm',
        'is_mk_universitas',
        'status',
        'prasyarat_lulus',
    ];

    protected $casts = [
        'prasyarat_lulus' => 'array',
    ];

    public function kurikulum()
    {
        return $this->belongsTo(Kurikulum::class, 'kurikulum_id', 'id');
    }

    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'kode_program_studi', 'kode_program_studi');
    }
}
