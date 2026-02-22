<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KRS extends Model
{
    protected $table = 'tbl_mahasiswa_krs';

    public function jadwal()
    {
        return $this->belongsTo(JadwalPerkuliahan::class, 'jadwal_id', 'id');
    }

    public function mataKuliah()
    {
        return $this->hasOneThrough(
            KurikulumMataKuliah::class,
            JadwalPerkuliahan::class,
            'id',           // FK di JadwalPerkuliahan ke KRS → jadwal_id
            'id',           // PK di MataKuliah
            'jadwal_id',    // FK di KRS ke JadwalPerkuliahan
            'mata_kuliah_id' // FK di JadwalPerkuliahan ke MataKuliah
        );
    }
    public function hari()
    {
        return $this->hasOneThrough(
            Hari::class,
            JadwalPerkuliahan::class,
            'id',           // FK di JadwalPerkuliahan ke KRS → jadwal_id
            'id',           // PK di MataKuliah
            'jadwal_id',    // FK di KRS ke JadwalPerkuliahan
            'hari_id' // FK di JadwalPerkuliahan ke MataKuliah
        );
    }
}
