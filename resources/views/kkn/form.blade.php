@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center mt-2">
            <h6 class="mb-2">Pilih Kegiatan</h6>
        </div>
        <form action="{{ route('laporan-kkn.update', ['id' => Crypt::encrypt($kkn->id)]) }}" method="post" class="form gap-3">
            <div class="card-body">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Pilih Kegiatan</label>
                        <select class="form-control" name="kegiatan_id" id="kegiatan_id">
                            <option value="">Pilih Kegiatan</option>
                            @foreach ($kegiatan as $item)
                                <option value="{{ $item->id }}" {{ $kkn->kegiatan_mahasiswa_id == $item->id ? 'selected' : '' }}>
                                    {{ $item->nama_kegiatan }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">Proses</button>
            </div>
        </form>
    </div>
@endsection
