@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">Data Kuliah Kerja Nyata (KKN)</h6>
            <div class="ms-auto">
                <a href="{{ route('laporan-kkn.index') }}" class="btn btn-sm btn-warning">Kembali</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center">
                    <thead class="thead-dark">
                        <tr>
                            <th>No</th>
                            <th>NPM</th>
                            <th>Nama Mahasiswa</th>
                            <th>Program Studi</th>
                            <th>Jenis KKN</th>
                            <th>Status Pembayaran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    @foreach ($kkn as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->npm }}</td>
                            <td>{{ $item->nama_mahasiswa }}</td>
                            <td>{{ $item->nama_program_studi_idn }}</td>
                            <td>{{ $item->nama_kegiatan }}</td>
                            <td></td>
                            <td>
                                <a href="{{ route('laporan-kkn.edit', Crypt::encrypt($item->id)) }}" class="btn btn-sm btn-info">Edit</a>
                            </td>
                        </tr>
                    @endforeach

                    <tbody>
                    </tbody>

                </table>

            </div>
        </div>
    </div>
@endsection
