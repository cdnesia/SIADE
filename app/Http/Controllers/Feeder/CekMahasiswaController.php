<?php

namespace App\Http\Controllers\Feeder;

use App\Http\Controllers\Controller;
use App\Services\NeofeederService;
use Illuminate\Http\Request;

class CekMahasiswaController extends Controller
{
    private $neoservice;

    public function __construct(NeofeederService $neoservice)
    {
        $this->neoservice = $neoservice;
    }
    public function cekKrs()
    {
        $data = [
            'act' => "GetDetailNilaiPerkuliahanKelas",
            'filter' => "nim='S12461412'",
            'order' => 0,
            'limit' => 0,
            'offset' => 0
        ];

        $result = $this->neoservice->getData($data);

        return response()->json($result);
    }
}
