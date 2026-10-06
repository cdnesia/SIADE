<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kurikulum extends Model
{
    protected $table = 'master_kurikulum';

    protected $fillable = [
        'kode_kurikulum',
        'nama_kurikulum',
        'status',
        'keterangan',
    ];

    public function mataKuliah()
    {
        return $this->hasMany(KurikulumMataKuliah::class, 'kurikulum_id', 'id');
    }

    public function kurikulumProdi()
    {
        return $this->hasMany(KurikulumProdi::class, 'kurikulum_id', 'id');
    }
}
