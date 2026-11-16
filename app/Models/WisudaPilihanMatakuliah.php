<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WisudaPilihanMatakuliah extends Model
{
    protected $table = 'tbl_wisuda_pilihan_matakuliah';

    protected $fillable = [
        'npm',
        'krs_id',
        'diambil',
        'user_id',
    ];
}
