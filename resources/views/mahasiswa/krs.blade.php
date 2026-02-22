@extends('layouts.app')
@section('content')
    @foreach ($krs as $key => $value)
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h6 class="mb-0">Tahun Akademik {{ $key }}</h6>
                <h6 class="mb-0"> -Semester {{ $value['semester'] }}</h6>
                <div class="ms-auto">
                    @can($modul . '.create')
                        <a href="{{ route($modul . '.create') }}" class="btn btn-sm btn-primary me-0"><i class="bx bx-printer mr-1"></i> Cetak</a>
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
@endsection
@push('css')
    <link href="{{ asset('') }}assets/plugins/datatable/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
@endpush
@push('js')
    <script src="{{ asset('') }}assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('') }}assets/plugins/datatable/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.krsTable').each(function() {
                $(this).DataTable({
                    lengthChange: false,
                    info: false,
                    paging: false,
                    scrollX: true,
                });
            });
        });
    </script>
@endpush
