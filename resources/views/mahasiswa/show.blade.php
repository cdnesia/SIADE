@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column align-items-center text-center">
                        <img src="{{ asset('') }}assets/images/no-image.png" alt="Admin"
                            class="rounded-circle p-1 bg-primary" width="150">
                        <div class="mt-3">
                            <p class="text-secondary mb-1">{{ $mahasiswa['nama_mahasiswa'] }}</p>
                            <p class="text-muted font-size-sm mb-1">{{ $mahasiswa['npm'] }}</p>
                            <p class="text-muted font-size-sm">{{ $mahasiswa['nama_jenis_pendaftaran'] }}</p>
                            <a href="{{ route('mahasiswa.show', $mahasiswa['id']) }}?p=detail-mahasiswa"
                                class="btn btn-sm btn-info">Detail Mahasiswa</a>
                        </div>
                    </div>
                    <hr class="my-4" />
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                            <h6 class="mb-0">Fakultas</h6>
                            <span class="text-secondary">{{ $mahasiswa['nama_fakultas'] ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                            <h6 class="mb-0">Program Studi</h6>
                            <span class="text-secondary">{{ $mahasiswa['nama_program_studi'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                            <h6 class="mb-0">Kelas Perkuliahan</h6>
                            <span class="text-secondary">{{ $mahasiswa['nama_program_kuliah'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                            <h6 class="mb-0">Dosen PA</h6>
                            <span class="text-secondary">{{ $mahasiswa['nama_pa'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                            <h6 class="mb-0">NIDN PA</h6>
                            <span class="text-secondary">{{ $mahasiswa['nidn_pa'] }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        @if ($page == 'detail-mahasiswa')
            <div class="col-lg-8">
                @foreach ($krs as $key => $value)
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h6 class="mb-0">Tahun Akademik {{ $key }}</h6>
                            <h6 class="mb-0"> -Semester {{ $value['semester'] }}</h6>
                            <div class="ms-auto">
                                @can($modul . '.create')
                                    <a href="{{ route($modul . '.create') }}" class="btn btn-sm btn-primary me-0"><i
                                            class="bx bx-printer mr-1"></i> Cetak</a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered krsTable" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Mata Kuliah</th>
                                            <th>Nama Mata Kuliah</th>
                                            <th>Hari</th>
                                            <th>Ruang</th>
                                            <th>Jam Mulai</th>
                                            <th>Jam Selesai</th>
                                            <th>Dosen Pengampu</th>
                                            <th>Kelompok</th>
                                            @canany([$modul . '.destroy', $modul . '.edit'])
                                                <th>Aksi</th>
                                            @endcanany
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($value['krs'] as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item['kode_mata_kuliah'] }}</td>
                                                <td>{{ $item['nama_mata_kuliah'] }}</td>
                                                <td>{{ $item['hari'] }}</td>
                                                <td>{{ $item['ruang_id'] }}</td>
                                                <td>{{ $item['jam_mulai'] }}</td>
                                                <td>{{ $item['jam_selesai'] }}</td>
                                                <td>{{ $item['dosen_id'] }}</td>
                                                <td>{{ $item['kelompok'] }}</td>
                                                <td></td>
                                            </tr>
                                        @endforeach

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
        @endif
    </div>
@endsection
