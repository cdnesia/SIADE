@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0"><i class="bx bx-sync me-1"></i>Master Sync</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4 col-sm-6">
                    <a href="{{ route('master.sync.prodi') }}" class="btn btn-outline-primary w-100 py-3">
                        <i class="bx bx-buildings d-block mb-1" style="font-size: 1.5rem;"></i>Sync Prodi
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a href="{{ route('master.sync.fakultas') }}" class="btn btn-outline-primary w-100 py-3">
                        <i class="bx bx-home d-block mb-1" style="font-size: 1.5rem;"></i>Sync Fakultas
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a href="{{ route('master.sync.jenis-matakuliah') }}" class="btn btn-outline-primary w-100 py-3">
                        <i class="bx bx-book d-block mb-1" style="font-size: 1.5rem;"></i>Sync Jenis Mata Kuliah
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a href="{{ route('master.sync.kurikulum') }}" class="btn btn-outline-primary w-100 py-3">
                        <i class="bx bx-list-ol d-block mb-1" style="font-size: 1.5rem;"></i>Sync Kurikulum
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a href="{{ route('master.sync.kurikulum-prodi') }}" class="btn btn-outline-primary w-100 py-3">
                        <i class="bx bx-list-check d-block mb-1" style="font-size: 1.5rem;"></i>Sync Kurikulum Prodi
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a href="{{ route('master.sync.kurikulum-mata-kuliah') }}" class="btn btn-outline-primary w-100 py-3">
                        <i class="bx bx-book-content d-block mb-1" style="font-size: 1.5rem;"></i>Sync Kurikulum Mata Kuliah
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a href="{{ route('master.sync.skala-nilai') }}" class="btn btn-outline-primary w-100 py-3">
                        <i class="bx bx-bar-chart d-block mb-1" style="font-size: 1.5rem;"></i>Sync Skala Nilai
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a href="{{ route('master.sync.krs') }}" class="btn btn-outline-primary w-100 py-3">
                        <i class="bx bx-book-open d-block mb-1" style="font-size: 1.5rem;"></i>Sync KRS
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a href="{{ route('master.sync.tahun-akademik') }}" class="btn btn-outline-primary w-100 py-3">
                        <i class="bx bx-calendar d-block mb-1" style="font-size: 1.5rem;"></i>Sync Tahun Akademik
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a href="{{ route('master.sync.jadwal-perkuliahan') }}" class="btn btn-outline-primary w-100 py-3">
                        <i class="bx bx-time d-block mb-1" style="font-size: 1.5rem;"></i>Sync Jadwal Perkuliahan
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a href="{{ route('master.sync.penerima-beasiswa') }}" class="btn btn-outline-primary w-100 py-3">
                        <i class="bx bx-money d-block mb-1" style="font-size: 1.5rem;"></i>Sync Penerima Beasiswa
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a href="{{ route('master.sync.jadwal-pertemuan') }}" class="btn btn-outline-primary w-100 py-3">
                        <i class="bx bx-calendar-event d-block mb-1" style="font-size: 1.5rem;"></i>Sync Jadwal Pertemuan
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a href="{{ route('master.sync.jadwal-pertemuan-absensi') }}" class="btn btn-outline-primary w-100 py-3">
                        <i class="bx bx-check-circle d-block mb-1" style="font-size: 1.5rem;"></i>Sync Absensi
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
