@extends('layouts.app')
@section('content')
    @foreach ($krs as $key => $value)
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h6 class="mb-0">Tahun Akademik {{ $key }}-Semester {{ $value['semester'] }}</h6>
                <div class="ms-auto">
                    @can($modul . '.krs.create')
                        <a href="#" class="btn btn-sm btn-info kontrakMK" data-tahun-akademik="{{ $key }}"
                            data-npm="{{ Crypt::decrypt(request()->segment(4)) }}" data-bs-toggle="modal"
                            data-bs-target="#exampleModal">
                            <i class="bx bx-list-check mr-1"></i> Kontrak Mata Kuliah
                        </a>
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
                                @can($modul . '.khs.update-nilai')
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
                                    @can($modul . '.khs.update-nilai')
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control nilai-update"
                                                    placeholder="Nilai Update">
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
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('mahasiswa.krs.create', request()->segment(4)) }}" method="post">
                    @csrf
                    <div class="modal-body">
                        <input type="text" name="kode_tahun_akademik" id="fkode_tahun_akademik" value="">
                        <div class="row">
                            @foreach ($matakuliah as $item)
                                <div class="col-md-6">
                                    <div class="form-check form-check-success">
                                        <input class="form-check-input" name="matakuliah[]" type="checkbox"
                                            value="{{ $item['id'] }}" id="mk{{ $item['id'] }}">
                                        <label class="form-check-label" for="mk{{ $item['id'] }}">
                                            {{ $item['kode_mata_kuliah'] . ' ' . $item['nama_mata_kuliah_idn'] }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary btn-sm">Simpan Mata Kuliah</button>
                    </div>
                </form>
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
                url: "/mahasiswa/khs/" + id + "/update-nilai",
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

        $('.kontrakMK').click(function() {
            let tahunAkademik = $(this).data('tahun-akademik');
            let npm = $(this).data('npm');
            $('#fkode_tahun_akademik').val(tahunAkademik);
            $('#exampleModalLabel').text('Kontrak Mata Kuliah TA ' + tahunAkademik + ' NPM ' + npm);

        });
    </script>
@endpush
