<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LaporanKKN extends Controller
{
    public function index()
    {
        $pendaftarKKN = DB::table('tbl_pendaftaran_kegiatan_mahasiswa as tpkm')
            ->join('master_mahasiswa as mm', 'tpkm.npm', '=', 'mm.npm')
            ->join('master_program_studi as mps', 'mm.kode_program_studi', '=', 'mps.kode_program_studi')
            ->join('tbl_kegiatan_mahasiswa as tkm', 'tpkm.kegiatan_mahasiswa_id', '=', 'tkm.id')
            ->select('tpkm.*', 'mm.nama_mahasiswa', 'mps.nama_program_studi_idn', 'tkm.nama_kegiatan')
            ->get();

        $pendaftarKKN = $pendaftarKKN->map(function ($item) {
            $status = $this->cekTagihanKKN($item->npm, 20252); // ganti dengan parameter yang sesuai
            $item->status_bayar = empty($status) ? 'Belum Bayar' : 'Sudah Bayar';

            return $item;
        });


        $d['kkn'] = $pendaftarKKN;
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
    private function cekTagihanKKN($npm, $tahun_akademik)
    {
        $url = config('services.simaku_url');
        $timestamp = time();
        $nonce = Str::uuid()->toString();
        $path = 'api/cek-tagihan-kkn';

        $body = json_encode([
            'npm' => $npm,
            'tahun_akademik' => $tahun_akademik,
        ]);

        $data = $timestamp . $nonce . 'POST' . $path . $body;
        $signature = hash_hmac('sha256', $data, config('services.hmac_secret'));
        $response = Http::withHeaders([
            'X-API-KEY'   => config('services.hmac_api_key'),
            'X-TIMESTAMP' => $timestamp,
            'X-NONCE'     => $nonce,
            'X-SIGNATURE' => $signature,
        ])->withBody($body, 'application/json')
            ->post($url . $path);

        $responseData = $response->json();

        $data = $responseData['data'] ?? [];

        if (empty($data)) {
            return [];
        }
        return $data;
    }
}
