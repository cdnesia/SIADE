<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KalenderAkademik extends Model
{
    protected $table = 'master_kalender_akademik';

    public function prodis()
    {
        $kodes = $this->kode_program_studi ? json_decode($this->kode_program_studi, true) : [];
        if (empty($kodes)) {
            return collect();
        }
        $prodis = Prodi::whereIn('kode_program_studi', $kodes)->get();
        return $prodis->sortBy('nama_program_studi_idn')->values();
    }
}
