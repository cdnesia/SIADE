@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <div class="ms-auto">
                @can($modul . '.index')
                    <a href="{{ route($modul . '.krs.create', request()->segment(4)) }}" class="btn btn-sm btn-info me-0"><i
                            class="bx bx-list-check mr-1"></i> Kontrak Mata Kuliah</a>
                @endcan
                <a href="{{ route($modul . '.index') }}" class="btn btn-sm btn-warning me-0"><i
                        class="bx bx-arrow-back mr-1"></i> Kembali</a>
            </div>
        </div>
    </div>
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
                                @canany([$modul . '.detail.krs.destroy', $modul . '.detail.krs.edit'])
                                    <th>Aksi</th>
                                @endcanany
                            </tr>
                        </thead>
                        <tbody>
                            {{-- @dd($krs) --}}
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
                                    @canany([$modul . '.detail.krs.destroy', $modul . '.detail.khs.edit'])
                                        <td>
                                            @can($modul . '.detail.krs.destroy')
                                                <form action="{{ route($modul . '.detail.krs.destroy', $item['encrypted_id']) }}"
                                                    method="POST" class="form-delete">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit">Hapus</button>
                                                </form>
                                            @endcan
                                            @can($modul . '.detail.krs.edit')
                                                edit
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

        $(document).on('submit', '.form-delete', function(e) {
            e.preventDefault();

            let form = $(this);
            let url = form.attr('action');

            if (!confirm('Yakin ingin menghapus data ini?')) {
                return;
            }

            $.ajax({
                url: url,
                type: "POST",
                data: form.serialize(),
                success: function(response) {
                    Lobibox.notify('success', {
                        pauseDelayOnHover: true,
                        size: 'mini',
                        rounded: true,
                        icon: 'bx bx-check-circle',
                        delayIndicator: false,
                        continueDelayOnInactiveTab: false,
                        position: 'top right',
                        msg: response.message,
                        sound: false,
                    });

                    form.closest('tr').remove();
                },
                error: function(xhr) {
                    let message = 'Terjadi kesalahan';
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }

                        if (xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors)
                                .map(err => err.join(', '))
                                .join('<br>');
                        }
                    }
                    Lobibox.notify('error', {
                        pauseDelayOnHover: true,
                        size: 'mini',
                        rounded: true,
                        icon: 'bx bx-x-circle',
                        delayIndicator: false,
                        continueDelayOnInactiveTab: false,
                        position: 'top right',
                        msg: message,
                        sound: false,
                    });
                }
            });
        });
    </script>
@endpush
