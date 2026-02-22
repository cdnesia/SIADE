<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterApiController extends Controller
{
    public function dataDosen()
    {
        $data = DB::connection('siade_old')->table('pegawai')->where('NA', 'A')->get();
        return response()->json([
            'success' => true,
            'data' => $data

        ]);
    }
    public function dataRuang()
    {
        $data = DB::connection('siade_old')->table('ruang')->where('NA', 'A')->get();
        return response()->json([
            'success' => true,
            'data' => $data

        ]);
    }
}
