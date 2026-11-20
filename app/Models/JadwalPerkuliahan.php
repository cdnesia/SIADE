<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalPerkuliahan extends Model
{
    protected $table='tbl_jadwal_perkuliahan';

    public function mataKuliah()
    {
        return $this->belongsTo(KurikulumMataKuliah::class, 'mata_kuliah_id', 'id');
    }

    public function pertemuan()
    {
        return $this->hasMany(JadwalPertemuan::class, 'jadwal_id', 'id')->orderBy('pertemuan');
    }

    /**
     * Setting bobot per pertemuan milik mata kuliah jadwal ini.
     */
    public function pertemuanNilai()
    {
        return $this->hasMany(KurikulumMataKuliahPertemuan::class, 'mata_kuliah_id', 'mata_kuliah_id')->orderBy('pertemuan');
    }
}
