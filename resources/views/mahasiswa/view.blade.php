@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">Data Mahasiswa</h6>
            <div class="ms-auto">
                @can($modul . '.create')
                    <a href="{{ route($modul . '.create') }}" class="btn btn-sm btn-primary">Tambah Mahasiswa</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3 g-2 align-items-end">
                <div class="col-sm-6 col-md-4">
                    <label for="filterProdi" class="form-label small mb-1">Program Studi</label>
                    <select id="filterProdi" class="form-select select2" data-placeholder="-- Semua Program Studi --">
                        <option value="">Semua Program Studi</option>
                        @foreach ($prodi as $p)
                            <option value="{{ $p->kode_program_studi }}">{{ $p->nama_program_studi_idn }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-4">
                    <label for="filterKelas" class="form-label small mb-1">Kelas Perkuliahan</label>
                    <select id="filterKelas" class="form-select select2" data-placeholder="-- Semua Kelas --">
                        <option value="">Semua Kelas Perkuliahan</option>
                        @foreach ($kelas as $k)
                            <option value="{{ $k->id }}">{{ $k->nama_program_perkuliahan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-3">
                    <label for="filterTahun" class="form-label small mb-1">Tahun Masuk</label>
                    <select id="filterTahun" class="form-select select2" data-placeholder="-- Semua Tahun --">
                        <option value="">Semua Tahun Masuk</option>
                        @foreach ($tahun as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-1">
                    <button id="btnResetFilter" class="btn btn-warning w-100" title="Reset Filter">
                        <i class="bx bx-reset"></i>
                    </button>
                </div>
            </div>
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
                            <th class="text-center" style="width: 100px">Aksi</th>
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
            var table = $('#mahasiswaTable').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                stateSave: true,
                ajax: {
                    url: "{{ url()->current() }}",
                    data: function(d) {
                        d.prodi = $('#filterProdi').val();
                        d.kelas = $('#filterKelas').val();
                        d.tahun = $('#filterTahun').val();
                    }
                },
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
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                ]
            });

            $('#filterProdi, #filterKelas, #filterTahun').on('change', function() {
                table.ajax.reload();
            });

            $('#btnResetFilter').on('click', function() {
                $('#filterProdi').val('').trigger('change');
                $('#filterKelas').val('').trigger('change');
                $('#filterTahun').val('').trigger('change');
            });
        });
    </script>
@endpush
