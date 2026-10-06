@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">Data Kurikulum</h6>
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
                            <th>Kode Kurikulum</th>
                            <th>Nama Kurikulum</th>
                            <th>Keterangan</th>
                            <th class="text-center">Prodi</th>
                            <th class="text-center">Mata Kuliah</th>
                            <th>Status</th>
                            @canany([$modul . '.edit', $modul . '.destroy', $modul . '.matakuliah.index'])
                                <th width="170px">Aksi</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kurikulum as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->kode_kurikulum }}</td>
                                <td>{{ $item->nama_kurikulum }}</td>
                                <td>{{ $item->keterangan }}</td>
                                <td class="text-center">{{ $item->kurikulum_prodi_count }}</td>
                                <td class="text-center">{{ $item->mata_kuliah_count }}</td>
                                <td>
                                    @if ($item->status == 'A')
                                        <span class="badge bg-success-subtle text-success-emphasis">Aktif</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger-emphasis">Tidak Aktif</span>
                                    @endif
                                </td>
                                @canany([$modul . '.edit', $modul . '.destroy', $modul . '.matakuliah.index'])
                                    <td class="text-nowrap">
                                        @can($modul . '.matakuliah.index')
                                            <a href="{{ route($modul . '.matakuliah.index', Crypt::encrypt($item->id)) }}"
                                                class="btn btn-primary btn-sm" title="Kelola mata kuliah"><i
                                                    class='bx bx-book-open'></i> Mata Kuliah</a>
                                        @endcan
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
