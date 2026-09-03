<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Akm extends Model
{
    protected $table = 'tbl_mahasiswa_akm';

    protected $fillable = [
        'kode_tahun_akademik',
        'npm',
        'nama_mahasiswa',
        'kode_program_studi',
        'program_kuliah_id',
        'tagihan_id',
        'semester',
        'ips',
        'ipk',
        'sks_semester',
        'sks_total',
        'status_mahasiswa',
    ];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'npm', 'npm');
    }
}
