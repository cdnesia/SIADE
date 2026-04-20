<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MasterApiService;

class MasterApiController extends Controller
{
    public function khs(MasterApiService $service)
    {
        $periode = request()->input('periode');
        $prodi = request()->input('prodi');
        $angkatan = request()->input('angkatan');

        if (!$periode || !$prodi || !$angkatan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter periode, prodi, atau angkatan tidak ditemukan'
            ], 400);
        }
        $krs = $service->krs($periode, $prodi, $angkatan);

        return response()->json([
            'status' => 'success',
            'data' => $krs
        ]);
    }
}
