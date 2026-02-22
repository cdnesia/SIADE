<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mahasiswa extends Model
{
    protected $table = 'master_mahasiswa';

    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'kode_program_studi', 'kode_program_studi');
    }

    public function kelas()
    {
        return $this->belongsTo(KelasPerkuliahan::class, 'program_kuliah_id', 'id');
    }
}
