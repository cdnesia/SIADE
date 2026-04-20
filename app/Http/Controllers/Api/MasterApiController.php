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

        if (!$periode || !$prodi) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter periode atau prodi tidak ditemukan'
            ], 400);
        }
        $krs = $service->krs($periode, $prodi);

        return response()->json([
            'status' => 'success',
            'data' => $krs
        ]);
    }
}
