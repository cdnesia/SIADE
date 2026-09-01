<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MahasiswaBaruController extends Controller
{
    public function __construct(protected ApiService $api) {}
    public function index()
    {

        $tahun_filter = 'UMJA2026';

        $pmb_prodi = DB::connection('penmaru_old')
            ->table('pmb_prodi')
            ->where('na', 'N')
            ->selectRaw("*, LEFT(pmb_gelombang, 8) as gelombang_short")
            ->get();


        $tahun_akademik = $pmb_prodi->groupBy('gelombang_short')->unique();

        $gelombang_pendaftaran = $tahun_akademik[$tahun_filter]->pluck('pmb_gelombang')->unique()->values()->toArray();


        $sudah_ada_nim = $tahun_akademik[$tahun_filter]->whereNotNull('nim')->where('nim', '!=', '');

        $sudah_ada_nim_array = $sudah_ada_nim->pluck('pmb')->unique()->values()->toArray();

        $belum_ada_nim = $tahun_akademik[$tahun_filter]->filter(fn($item) => (is_null($item->nim) || $item->nim === '') && $item->pmb_jalur != 20);


        $belum_ada_nim_array = $belum_ada_nim->pluck('pmb')->unique()->values()->toArray();

        $result = $this->api->post(
            '/api/tagihan-pmb/massal',
            [
                "npms"             => $belum_ada_nim_array,
                "tahun_akademik"   => $gelombang_pendaftaran,
            ]
        );

        $dataLolos = [];

        if ($result['success'] ?? false) {
            foreach (($result['data'] ?? []) as $tagihan) {
                $total = (float) ($tagihan['total_tagihan'] ?? 0);
                $terbayar = (float) ($tagihan['nominal_terbayar'] ?? 0);
                if ($terbayar >= ($total * 0.5)) {
                    foreach (($tagihan['detail_tagihan'] ?? []) as $detail) {
                        if (($detail['id_bipot'] ?? null) == 1) {
                            $dataLolos[] = $tagihan['npm'] ?? null;
                        }
                    }
                }
            }
        }

        $prodis = DB::connection('penmaru_old')
            ->table('master_sub_unit_kerja as msuk')
            ->select('kode', 'nim_prodi_kode', 'jenjang', 'msuk.nama')
            // ->select('msuk.*')
            ->join('master_pendidikan as mp', 'mp.id', 'msuk.id_pendidikan')
            ->where('kode', '!=', '')
            ->get()->keyBy('kode')->toArray();

        // dd($prodis);

        $data_mahasiswa = $belum_ada_nim->keyBy('pmb');

        $kode_tahun = substr($tahun_filter, 6, 2);

        $exclude = ['UMJA202610014', 'UMJA202610013', 'UMJA202610006'];

        $hasil = [];

        foreach ($dataLolos as $npm) {
            if (!isset($data_mahasiswa[$npm])) {
                continue;
            }

            $pendaftar = $data_mahasiswa[$npm];
            $prodi     = (int) $pendaftar->prodi;
            $nomor = (int) $pendaftar->nomor_pmb;
            $nama = $pendaftar->nama_daftar;


            if (!isset($prodis[$prodi])) {
                continue;
            }

            $jenjang    = $prodis[$prodi]->jenjang;
            $kode_prodi = $prodis[$prodi]->nim_prodi_kode;

            $nim_terakhir = DB::connection('penmaru_old')
                ->table('pmb')
                ->where('prodi', $prodi)
                ->where('pmb', 'like', '%' . $tahun_filter . '%')
                ->whereNotIn('pmb', $exclude)
                ->whereNotNull('nim')
                ->where('nim_num', '!=', 0)
                ->orderBy('nim_num', 'DESC')
                ->value('nim_num');


            $nim_num_baru = ($nim_terakhir ? (int) $nim_terakhir + 1 : 1);

            $nim_baru = $jenjang . $kode_tahun . $kode_prodi . str_pad($nim_num_baru, 3, '0', STR_PAD_LEFT);

            DB::connection('penmaru_old')
                ->table('pmb')
                ->where('nomor', $nomor)
                ->update([
                    'nim'     => $nim_baru,
                    'nim_num' => $nim_num_baru,
                ]);

            $hasil[] = [
                'nomor'   => $nomor,
                'prodi'   => $prodi,
                'nama'    => $nama,
                'nim'     => $nim_baru,
                'nim_num' => $nim_num_baru,
            ];
        }

        $by_prodi = $sudah_ada_nim
            ->whereNotIn('pmb', $exclude)
            ->groupBy('prodi')
            ->map(function ($items, $prodi) use ($prodis) {
                $nama_prodi = isset($prodis[$prodi]) ? $prodis[$prodi]->nama : 'Prodi ' . $prodi;
                return [
                    'nama_prodi' => $nama_prodi,
                    'kode_prodi' => $prodi,
                    'jumlah'     => $items->count(),
                    'data'       => $items->sortBy('nim')->values(),
                ];
            })
            ->values();

        $d['npm_baru'] = $hasil;
        $d['jumlah_npm_baru'] = count($hasil);
        $d['jumlah_mahasiswa_baru'] = count($sudah_ada_nim);
        $d['by_prodi'] = $by_prodi;
        $d['tahun_filter'] = $tahun_filter;

        return view('mahasiswa-baru.index', $d);
    }
}
