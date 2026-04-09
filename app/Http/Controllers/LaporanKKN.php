<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
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
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $d['kkn'] = DB::table('tbl_pendaftaran_kegiatan_mahasiswa as tpkm')
            ->join('tbl_kegiatan_mahasiswa as tkm', 'tpkm.kegiatan_mahasiswa_id', '=', 'tkm.id')
            ->join('master_mahasiswa as mm', 'tpkm.npm', '=', 'mm.npm')
            ->select('tpkm.*', 'tkm.kelas_perkuliahan_id', 'mm.kode_program_studi')
            ->where('tpkm.id', $id)
            ->first();
        $d['kegiatan'] = DB::table('tbl_kegiatan_mahasiswa')
            ->where('kelas_perkuliahan_id', $d['kkn']->kelas_perkuliahan_id)
            ->whereJsonContains('kode_program_studi', $d['kkn']->kode_program_studi)
            ->get();

        return view('kkn.form', $d);
    }
    public function update(Request $request, $id)
    {
        $id = Crypt::decrypt($id);
        DB::table('tbl_pendaftaran_kegiatan_mahasiswa')
            ->where('id', $id)
            ->update([
                'kegiatan_mahasiswa_id' => $request->kegiatan_id,
            ]);
        return redirect()->route('laporan-kkn.index')->with('success', 'Laporan KKN berhasil diperbarui');
    }
}
