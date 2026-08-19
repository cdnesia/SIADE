@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">Jadwal Dosen Mengajar</h6>
        </div>
        <div class="card-body">
            <div class="row mb-2 g-2 align-items-end">
                <div class="col-sm-6 col-md-4">
                    <label for="filterTahunAkademik" class="form-label small mb-1">Tahun Akademik</label>
                    <select id="filterTahunAkademik" class="form-select select2" data-placeholder="-- Semua --">
                        <option value="">Semua</option>
                        @foreach ($tahunAkademik as $ta)
                            <option value="{{ $ta }}">{{ $ta }}</option>
                        @endforeach
                    </select>
                </div>
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
                        <option value="">Semua Kelas</option>
                        @foreach ($kelas as $k)
                            <option value="{{ $k->id }}">{{ $k->nama_program_perkuliahan }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mb-2 g-2 align-items-end">
                <div class="col-sm-6 col-md-4">
                    <label for="filterDosen" class="form-label small mb-1">Dosen</label>
                    <select id="filterDosen" class="form-select select2" data-placeholder="-- Semua Dosen --">
                        <option value="">Semua Dosen</option>
                        @foreach ($dosen as $d)
                            <option value="{{ $d['id'] }}">{{ $d['nama_lengkap'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-4">
                    <label for="filterHari" class="form-label small mb-1">Hari</label>
                    <select id="filterHari" class="form-select select2" data-placeholder="-- Semua Hari --">
                        <option value="">Semua Hari</option>
                        @foreach ($hari as $h)
                            <option value="{{ $h->id }}">{{ $h->nama_hari }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-4">
                    <label for="filterRuang" class="form-label small mb-1">Ruangan</label>
                    <select id="filterRuang" class="form-select select2" data-placeholder="-- Semua Ruangan --">
                        <option value="">Semua Ruangan</option>
                        @foreach ($ruang as $r)
                            <option value="{{ $r }}">Ruang {{ $r }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-12">
                    <button id="btnResetFilter" class="btn btn-warning" title="Reset Filter">
                        <i class="bx bx-reset me-1"></i>Reset Filter
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="jadwalDosenTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Dosen Pengampu</th>
                            <th>Mata Kuliah</th>
                            <th>Program Studi</th>
                            <th>Kelas</th>
                            <th>Hari</th>
                            <th>Jam</th>
                            <th>Ruang</th>
                            <th>Kelompok</th>
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
            var table = $('#jadwalDosenTable').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                stateSave: true,
                ajax: {
                    url: "{{ url()->current() }}",
                    data: function(d) {
                        d.tahun_akademik = $('#filterTahunAkademik').val();
                        d.prodi = $('#filterProdi').val();
                        d.kelas = $('#filterKelas').val();
                        d.dosen = $('#filterDosen').val();
                        d.hari = $('#filterHari').val();
                        d.ruang = $('#filterRuang').val();
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
                        data: 'nama_dosen',
                        name: 'nama_dosen',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'mata_kuliah',
                        name: 'm.nama_mata_kuliah_idn'
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
                        data: 'nama_hari',
                        name: 'h.nama_hari'
                    },
                    {
                        data: 'jam',
                        name: 'j.jam_mulai'
                    },
                    {
                        data: 'ruang_id',
                        name: 'j.ruang_id'
                    },
                    {
                        data: 'kelompok',
                        name: 'j.kelompok'
                    },
                ]
            });

            $('#filterTahunAkademik, #filterProdi, #filterKelas, #filterDosen, #filterHari, #filterRuang').on('change', function() {
                table.ajax.reload();
            });

            $('#btnResetFilter').on('click', function() {
                $('#filterTahunAkademik').val('').trigger('change');
                $('#filterProdi').val('').trigger('change');
                $('#filterKelas').val('').trigger('change');
                $('#filterDosen').val('').trigger('change');
                $('#filterHari').val('').trigger('change');
                $('#filterRuang').val('').trigger('change');
            });
        });
    </script>
@endpush
