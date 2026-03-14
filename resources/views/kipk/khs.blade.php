@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header align-items-center">
            <h6 class="mb-0">Nama Mahasiswa : {{ $mahasiswa['nama_mahasiswa'] }}</h6>
            <h6 class="mb-0">NPM : {{ $mahasiswa['npm'] }}</h6>
            <h6 class="mb-0">Program Studi : {{ $mahasiswa['nama_program_studi'] }}</h6>
            <h6 class="mb-0">Kelas Perkuliahan : {{ $mahasiswa['nama_program_kuliah'] }}</h6>
        </div>
    </div>
    @foreach ($krs as $key => $value)
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h6 class="mb-0">Tahun Akademik {{ $key }}-Semester {{ $value['semester'] }}</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered krsTable" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width: 30px">No</th>
                                <th style="width: 100px">Mata Kuliah</th>
                                <th>Nama Mata Kuliah</th>
                                <th style="width: 100px">Nilai Angka</th>
                                <th style="width: 100px">Nilai Huruf</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($value['krs'] as $item)
                                <tr class="bg-success">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item['kode_mata_kuliah'] }}</td>
                                    <td>{{ $item['nama_mata_kuliah'] }}</td>
                                    <td class="nilai-angka">{{ $item['nilai_angka'] }}</td>
                                    <td class="nilai-huruf">{{ $item['nilai_huruf'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <strong>
                    <span>IPS : {{ $value['metadata']['ips'] }}</span>
                    <span>IPK : {{ $value['metadata']['ipk'] }}</span>
                </strong>
            </div>
        </div>
    @endforeach
@endsection
