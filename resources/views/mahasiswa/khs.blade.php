@extends('layouts.app')
@section('content')
    @foreach ($krs as $key => $value)
        <div class="card">
            <div class="card-header d-flex align-items-center mt-2">
                <h6>Tahun Akademik {{ $key }}-Semester {{ $value['semester'] }}</h6>
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
                                @can($modul . '.detail.khs.update-nilai')
                                    <th style="width: 200px">Perbaikan Nilai</th>
                                @endcan
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
                                    @can($modul . '.detail.khs.update-nilai')
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control nilai-update"
                                                    data-id="{{ $item['encrypted_id'] }}" placeholder="Nilai Update">
                                                <button class="btn btn-outline-success btn-update"
                                                    data-id="{{ $item['encrypted_id'] }}">
                                                    <i class="bx bx-check-circle me-0"></i>
                                                </button>
                                            </div>
                                        </td>
                                    @endcan
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

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('.btn-update').click(function() {
            let pathArray = window.location.pathname.split('/');
            let encryptedId = pathArray[4];
            let id = $(this).data('id');
            let row = $(this).closest('tr');
            let nilai = row.find('.nilai-update').val();

            $.ajax({
                url: "/mahasiswa/detail/khs/" + id + "/update-nilai",
                type: "POST",
                data: {
                    nilai: nilai,
                    mahasiswa: encryptedId
                },
                success: function(response) {
                    if (response.success === true) {
                        row.find('.nilai-angka').text(response.data.nilai_angka);
                        row.find('.nilai-huruf').text(response.data.nilai_huruf);
                        alert(response.message);
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    alert('Gagal update nilai');
                    console.log(xhr.responseText);
                }
            });

        });
    </script>
@endpush
