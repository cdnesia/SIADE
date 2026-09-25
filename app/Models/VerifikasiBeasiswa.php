<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerifikasiBeasiswa extends Model
{
    protected $table = 'tbl_verifikasi_beasiswa';

    protected $fillable = [
        'npm',
        'id_lembaga',
        'kode_tahun_akademik',
        'terverifikasi',
        'diverifikasi_oleh',
        'diverifikasi_pada',
    ];

    protected $casts = [
        'terverifikasi' => 'boolean',
        'diverifikasi_pada' => 'datetime',
    ];

    public function verifikator()
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }
}
