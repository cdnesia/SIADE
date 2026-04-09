<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanKKN extends Controller
{
    public function index()
    {
        $d['kkn'] = DB::table('tbl_pendaftaran_kegiatan_mahasiswa as tpkm')
            ->join('master_mahasiswa as mm', 'tpkm.npm', '=', 'mm.npm')
            ->join('master_program_studi as mps', 'mm.kode_program_studi', '=', 'mps.kode_program_studi')
            ->join('tbl_kegiatan_mahasiswa as tkm', 'tpkm.kegiatan_mahasiswa_id', '=', 'tkm.id')
            ->select('tpkm.*', 'mm.nama_mahasiswa', 'mps.nama_program_studi_idn', 'tkm.nama_kegiatan')
            ->get();
        return view('kkn.view', $d);
    }
}
