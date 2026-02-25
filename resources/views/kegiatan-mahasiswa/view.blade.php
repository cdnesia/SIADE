@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">Kegiatan Mahasiswa</h6>
            <div class="ms-auto">
                @can($modul . '.create')
                    <a href="{{ route($modul . '.create') }}" class="btn btn-sm btn-primary">Tambah Data</a>
                @endcan

            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered example" style="width:100%">
                    <thead>
                        <tr>
                            <th width="30px">No</th>
                            <th>Nama Kegiatan</th>
                            <th>Program Studi</th>
                            <th>Tahun Angkatan</th>
                            <th>Minimal SKS</th>
                            <th>Minimal Semester</th>
                            <th>Maksimal Nilai D</th>
                            <th>Nama Biaya</th>
                            <th>Biaya Pendaftaran</th>
                            <th>Tipe Kegiatan</th>
                            @canany([$modul . '.edit', $modul . '.destroy'])
                                <th width="50px">Aksi</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kegiatan as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->nama_kegiatan }}</td>
                                <td>
                                    @foreach ($item->prodis as $value)
                                        <small>{{ $value->nama_program_studi_idn }} <br></small>
                                    @endforeach
                                </td>
                                <td>{{ $item->tahun_angkatan }}</td>
                                <td>{{ $item->minimal_sks }}</td>
                                <td>{{ $item->minimal_semester }}</td>
                                <td>{{ $item->maksimal_nilai_d }}</td>
                                <td>{{ $item->id_bipot }}</td>
                                <td>Rp. {{ number_format($item->biaya_pendaftaran, 0, ',', '.') }}</td>
                                <td>{{ $item->tipe }}</td>
                                @canany([$modul . '.edit', $modul . '.destroy'])
                                    <td>
                                        @can($modul . '.edit')
                                            <a href="{{ route($modul . '.edit', Crypt::encrypt($item->id)) }}"
                                                class="btn btn-warning btn-sm"><i class='bx bx-message-square-edit me-0'></i></a>
                                        @endcan
                                        @can($modul . '.destroy')
                                            <form action="{{ route($modul . '.destroy', Crypt::encrypt($item->id)) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Yakin ingin menghapus data ini?')">
                                                    <i class='bx bx-message-square-x me-0'></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                @endcanany
                            </tr>
                        @endforeach
                    </tbody>
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
            $('.example').each(function() {
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
