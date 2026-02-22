@extends('layouts.app')
@section('content')
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
                                    <td>{{ $item['nilai_angka'] }}</td>
                                    <td>{{ $item['nilai_huruf'] }}</td>
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
