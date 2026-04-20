<?php

namespace App\Http\Controllers\Feeder;

use App\Http\Controllers\Controller;
use App\Services\NeofeederService;
use Illuminate\Http\Request;

class BiodataMahasiswaController extends Controller
{
    private $neoservice;

    public function __construct(NeofeederService $neoservice)
    {
        $this->neoservice = $neoservice;
    }
    public function index()
    {
        $data = [
            "act"    => "GetListMahasiswa",
            "filter" => "id_periode like '2024%'",
            "limit"  => 0,
            "offset" => 0
        ];

        $result = $this->neoservice->getData($data);

        if (!isset($result['data']) || empty($result['data'])) {
            return response()->json([]);
        }

        $ids = collect($result['data'])->pluck('id_mahasiswa')->toArray();

        $biodataResponse = $this->biodata($ids);
        $biodataMap = collect($biodataResponse['data'] ?? [])->keyBy('id_mahasiswa');
        $arrayWilayah = $this->wilayah();
        $wilayahMap = collect($arrayWilayah['data'] ?? [])
            ->groupBy(function ($item) {
                return trim($item['id_level_wilayah']);
            })
            ->mapWithKeys(function ($group, $key) {
                return [$key => $group->keyBy(function ($item) {
                    return trim($item['id_wilayah']);
                })];
            });


        $mergedData = collect($result['data'])->map(function ($item) use ($biodataMap) {
            $id = $item['id_mahasiswa'];
            return array_merge($item, $biodataMap->get($id, []));
        });

        $mergedData = $mergedData->map(function ($item) use ($wilayahMap) {
            $id_kec = trim($item['id_wilayah'] ?? '');
            $data_kecamatan = $wilayahMap[3][$id_kec] ?? null;

            $id_induk_kotkab = isset($data_kecamatan['id_induk_wilayah']) ? trim($data_kecamatan['id_induk_wilayah']) : null;
            $data_kotkab = $wilayahMap[2][$id_induk_kotkab] ?? null;

            $id_induk_prov = isset($data_kotkab['id_induk_wilayah']) ? trim($data_kotkab['id_induk_wilayah']) : null;
            $data_provinsi = $wilayahMap[1][$id_induk_prov] ?? null;

            return [
                'nim'                   => $item['nim'] ?? null,
                'nama_mahasiswa'        => $item['nama_mahasiswa'] ?? null,
                'jenis_kelamin'         => $item['jenis_kelamin'] ?? null,
                'tanggal_lahir'         => $item['tanggal_lahir'] ?? null,
                'tempat_lahir'          => $item['tempat_lahir'] ?? null,
                'nama_agama'            => $item['nama_agama'] ?? null,
                'nik'                   => $item['nik'] ?? null,
                'nisn'                  => $item['nisn'] ?? null,
                'jalan'                 => $item['jalan'] ?? null,
                'dusun'                 => $item['dusun'] ?? null,
                'rt'                    => $item['rt'] ?? null,
                'rw'                    => $item['rw'] ?? null,
                'kelurahan'             => $item['kelurahan'] ?? null,
                'nama_kecamatan'        => $data_kecamatan['nama_wilayah'] ?? null,
                'kota_kabupaten'        => $data_kotkab['nama_wilayah'] ?? null,
                'nama_provinsi'         => $data_provinsi['nama_wilayah'] ?? null,
                'kewarganegaraan'       => $item['kewarganegaraan'] ?? null,
                'id_negara'             => $item['id_negara'] ?? null,
                'handphone'             => $item['handphone'] ?? null,
                'email'                 => $item['email'] ?? null,
                'nama_periode_masuk'    => $item['nama_periode_masuk'] ?? null,
                'id_periode_keluar'     => $item['id_periode_keluar'] ?? null,
                'tanggal_keluar'        => $item['tanggal_keluar'] ?? null,
                'nama_program_studi'    => $item['nama_program_studi'] ?? null,
                'nama_status_mahasiswa' => $item['nama_status_mahasiswa'] ?? null,
            ];
        });


        return response()->json([
            'jumlah' => count($mergedData),
            'data' => $mergedData->values(),
        ]);
    }

    private function biodata(array $ids = [])
    {
        if (empty($ids)) {
            return ['data' => []];
        }

        $data = [
            "act"    => "GetBiodataMahasiswa",
            "filter" => "id_mahasiswa IN ('" . implode("','", $ids) . "')",
            "limit"  => 0,
            "offset" => 0
        ];

        return $this->neoservice->getData($data);
    }
    private function wilayah()
    {
        $data = [
            "act"    => "GetWilayah",
            "filter" => "",
            "limit"  => 0,
            "offset" => 0
        ];

        return $this->neoservice->getData($data);
    }
}
