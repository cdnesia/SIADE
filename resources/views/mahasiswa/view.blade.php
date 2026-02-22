@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">Data Mahasiswa</h6>
            <div class="ms-auto">
                @can($modul . '.create')
                    <a href="{{ route($modul . '.create') }}" class="btn btn-sm btn-primary">Tambah Mahasiswa</a>
                @endcan
                @can($modul . '.sync')
                    <form action="{{ route($modul . '.sync') }}" method="POST" style="display:inline-block;">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning"><i class="bx bx-sync mr-1"></i>Sync
                            Mahasiswa</button>
                    </form>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="mahasiswaTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor Pokok Mahasiswa</th>
                            <th>Nama Mahasiswa</th>
                            <th>Program Studi</th>
                            <th>Kelas Perkuliahan</th>
                            <th>Tahun Masuk</th>
                            @canany([$modul . '.destroy', $modul . '.edit'])
                                <th>Aksi</th>
                            @endcanany
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection
@push('css')
    <link href="{{ asset('') }}assets/plugins/datatable/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
@endpush
@push('js')
    <script src="{{ asset('') }}assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('') }}assets/plugins/datatable/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#mahasiswaTable').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                ajax: "{{ url()->current() }}",
                language: {
                    processing: '<i class="bx bx-loader bx-spin"></i> Mohon Tunggu...'
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'npm',
                        name: 'm.npm'
                    },
                    {
                        data: 'nama_mahasiswa',
                        name: 'm.nama_mahasiswa'
                    },
                    {
                        data: 'nama_prodi',
                        name: 'p.nama_program_studi_idn'
                    },
                    {
                        data: 'nama_kelas',
                        name: 'k.nama_program_perkuliahan'
                    },
                    {
                        data: 'tahun_angkatan',
                        name: 'm.tahun_angkatan'
                    },
                    @canany([$modul . '.destroy', $modul . '.edit'])
                        {
                            data: 'aksi',
                            name: 'aksi',
                            orderable: false,
                            searchable: false
                        },
                    @endcanany
                ]
            });
        });
    </script>
@endpush
