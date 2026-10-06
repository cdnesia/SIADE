<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KurikulumProdi extends Model
{
    protected $table = 'master_kurikulum_prodi';

    protected $fillable = [
        'kurikulum_id',
        'kode_program_studi',
        'tahun_angkatan',
        'jumlah_sks_lulus',
        'jumlah_sks_wajib',
        'jumlah_sks_pilihan',
        'status',
    ];

    public function kurikulum()
    {
        return $this->belongsTo(Kurikulum::class, 'kurikulum_id', 'id');
    }
}
