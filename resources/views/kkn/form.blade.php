@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center mt-2">
            <h6 class="mb-2">Cek IP</h6>
        </div>
        <form action="{{ route('kipk.store') }}" method="post" class="form gap-3">
            <div class="card-body">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Nomor Pokok Mahasiswa</label>
                        <textarea class="form-control" name="nim_list" id="nim_list" cols="30" rows="5"
                            placeholder="Contoh:
2023001
2023002
2023003"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tahun Akademik</label>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            @foreach ($tahun_akademik as $item)
                                <div class="form-check form-check-success">
                                    <input class="form-check-input" name="tahun_akademik[]" type="checkbox" value="{{ $item->kode_tahun_akademik }}" id="{{ $item->kode_tahun_akademik }}">
                                    <label class="form-check-label" for="{{ $item->kode_tahun_akademik }}">
                                        {{ $item->kode_tahun_akademik }}
                                    </label>
                                </div>
                            @endforeach

                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">Proses</button>
            </div>
        </form>
    </div>
@endsection
