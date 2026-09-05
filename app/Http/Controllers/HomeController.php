<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('home');
    }


    // $cek = $api->cetakKhs();

    //     return response($cek->body(), $cek->status())
    //         ->header('Content-Type', $cek->header('Content-Type'));
}
