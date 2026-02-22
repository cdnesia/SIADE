@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">{{ $data ? 'Edit' : 'Tambah' }} Data Akreditasi</h6>
            <div class="ms-auto">
                <a href="{{ route('akreditasi.index') }}" class="btn btn-sm btn-warning">Kembali</a>
            </div>
        </div>

        <div class="card-body">
            <form method="POST"
                action="{{ $data ? route('akreditasi.update', Crypt::encrypt($data->id)) : route('akreditasi.store') }}"
                class="row g-3">
                @csrf
                @if ($data)
                    @method('PUT')
                @endif
                <div class="col-md-12">
                    <label class="form-label">Nomor Surat Keputusan</label>
                    <input type="text" class="form-control @error('nomor_sk') is-invalid @enderror" name="nomor_sk"
                        value="{{ old('nomor_sk', $data->nomor_sk ?? '') }}">
                    @error('nomor_sk')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal Berlaku</label>
                    <input type="date" class="form-control @error('tanggal_berlaku') is-invalid @enderror"
                        name="tanggal_berlaku" value="{{ old('mulai_berlaku', $data->mulai_berlaku ?? '') }}">
                    @error('tanggal_berlaku')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal Berkahir</label>
                    <input type="date" class="form-control @error('tanggal_berakhir') is-invalid @enderror"
                        name="tanggal_berakhir" value="{{ old('selesai_berlaku', $data->selesai_berlaku ?? '') }}">
                    @error('tanggal_berakhir')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nilai</label>
                    <input type="text" class="form-control @error('nilai') is-invalid @enderror" name="nilai"
                        value="{{ old('nilai', $data->nilai ?? '') }}">
                    @error('nilai')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Berkas</label>
                    <input type="file" class="form-control @error('berkas') is-invalid @enderror" name="berkas">
                    @error('berkas')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                    @if ($data && $data->file)
                        <small><a href="{{ $data->file }}" class="badge bg-primary">Lihat Berkas</a></small>
                    @endif
                </div>
                <div>
                    <button type="submit" class="btn btn-success btn-primary btn-sm">
                        {{ $data ? 'Update' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
