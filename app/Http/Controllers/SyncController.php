<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function index()
    {
        return view('sync.index');
    }

    public function prodi()
    {
        return redirect()->back()->with('success', 'Sync Prodi berhasil');
    }

    public function fakultas()
    {
        return redirect()->back()->with('success', 'Sync Fakultas berhasil');
    }

    public function jenisMataKuliah()
    {
        return redirect()->back()->with('success', 'Sync Jenis Mata Kuliah berhasil');
    }

    public function kurikulum()
    {
        return redirect()->back()->with('success', 'Sync Kurikulum berhasil');
    }

    public function kurikulumProdi()
    {
        return redirect()->back()->with('success', 'Sync Kurikulum Prodi berhasil');
    }

    public function kurikulumMataKuliah()
    {
        return redirect()->back()->with('success', 'Sync Kurikulum Mata Kuliah berhasil');
    }

    public function skalaNilai()
    {
        return redirect()->back()->with('success', 'Sync Skala Nilai berhasil');
    }

    public function krs()
    {
        return redirect()->back()->with('success', 'Sync KRS berhasil');
    }

    public function tahunAkademik()
    {
        return redirect()->back()->with('success', 'Sync Tahun Akademik berhasil');
    }

    public function jadwalPerkuliahan()
    {
        return redirect()->back()->with('success', 'Sync Jadwal Perkuliahan berhasil');
    }

    public function penerimaBeasiswa()
    {
        return redirect()->back()->with('success', 'Sync Penerima Beasiswa berhasil');
    }

    public function jadwalPertemuan()
    {
        return redirect()->back()->with('success', 'Sync Jadwal Pertemuan berhasil');
    }

    public function jadwalPertemuanAbsensi()
    {
        return redirect()->back()->with('success', 'Sync Jadwal Pertemuan Absensi berhasil');
    }
}
