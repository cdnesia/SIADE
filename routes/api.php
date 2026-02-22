<?php

use App\Http\Controllers\Api\MasterApiController;
use App\Http\Controllers\Api\TagihanController;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return response()->json([
        'message' => 'API SIADE Laravel 12 aktif'
    ]);
});

Route::post('/data-dosen', [MasterApiController::class, 'dataDosen'])->middleware('verifyHmac');
Route::post('/data-ruang', [MasterApiController::class, 'dataRuang'])->middleware('verifyHmac');
