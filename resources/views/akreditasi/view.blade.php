@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">Riwayat Akreditasi</h6>
            <div class="ms-auto">
                <a href="{{ route('akreditasi.create') }}" class="btn btn-sm btn-primary">Tambah Akreditasi</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th width="30px">No</th>
                            <th>Nomor Surat Keputusan</th>
                            <th>Tanggal Berlaku</th>
                            <th>Tanggal Berakhir</th>
                            <th>Nilai</th>
                            <th>Berkas</th>
                            <th width="80px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($akreditasi as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->nomor_sk }}</td>
                                <td>{{ $item->mulai_berlaku }}</td>
                                <td>{{ $item->selesai_berlaku }}</td>
                                <td>{{ $item->nilai }}</td>
                                <td>{{ $item->file }}</td>
                                <td><a href="{{ route('akreditasi.edit', Crypt::encrypt($item->id)) }}">edit</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
