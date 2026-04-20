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

    public function mataKuliahJadwal()
    {
        return $this->hasOneThrough(
            KurikulumMataKuliah::class,
            JadwalPerkuliahan::class,
            'id',
            'id',
            'jadwal_id',
            'mata_kuliah_id'
        );
    }

    public function mataKuliahLangsung()
    {
        return $this->belongsTo(KurikulumMataKuliah::class, 'mata_kuliah_id', 'id');
    }

    public function hari()
    {
        return $this->hasOneThrough(
            Hari::class,
            JadwalPerkuliahan::class,
            'id',
            'id',
            'jadwal_id',
            'hari_id'
        );
    }

    public function mahasiswa()
    {
        return $this->hasOne(Mahasiswa::class, 'npm', 'npm');
    }

    public function getMataKuliahAttribute()
    {
        if ($this->jadwal_id == 0) {
            return $this->mataKuliahLangsung;
        }

        return $this->mataKuliahJadwal;
    }
}
