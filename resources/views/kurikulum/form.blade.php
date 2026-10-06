@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">{{ $data ? 'Edit' : 'Tambah' }} Kurikulum</h6>
            <div class="ms-auto">
                <a href="{{ route($modul . '.index') }}" class="btn btn-sm btn-warning">Kembali</a>
            </div>
        </div>

        <div class="card-body">
            <form method="POST"
                action="{{ $data ? route($modul . '.update', Crypt::encrypt($data->id)) : route($modul . '.store') }}"
                class="row g-3">
                @csrf
                @if ($data)
                    @method('PUT')
                @endif
                <div class="col-md-6">
                    <label class="form-label">Kode Kurikulum</label>
                    <input type="text" class="form-control @error('kode_kurikulum') is-invalid @enderror"
                        name="kode_kurikulum" value="{{ old('kode_kurikulum', $data->kode_kurikulum ?? '') }}"
                        placeholder="Kode Kurikulum">
                    @error('kode_kurikulum')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Kurikulum</label>
                    <input type="text" class="form-control @error('nama_kurikulum') is-invalid @enderror"
                        name="nama_kurikulum" value="{{ old('nama_kurikulum', $data->nama_kurikulum ?? '') }}"
                        placeholder="Nama Kurikulum">
                    @error('nama_kurikulum')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-12">
                    <label class="form-label">Keterangan</label>
                    <textarea class="form-control @error('keterangan') is-invalid @enderror" name="keterangan"
                        rows="3" placeholder="Keterangan">{{ old('keterangan', $data->keterangan ?? '') }}</textarea>
                    @error('keterangan')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select select2 @error('status') is-invalid @enderror"
                        data-placeholder="--Pilih Status--">
                        <option value=""></option>
                        <option value="A" {{ old('status', $data->status ?? '') == 'A' ? 'selected' : '' }}>Aktif
                        </option>
                        <option value="N" {{ old('status', $data->status ?? '') == 'N' ? 'selected' : '' }}>Tidak
                            Aktif
                        </option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
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
