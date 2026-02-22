@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">{{ $data ? 'Edit' : 'Tambah' }} Lembaga Beasisawa</h6>
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
                <div class="col-md-12">
                    <label class="form-label">Nama Beasiswa</label>
                    <input type="text" class="form-control @error('nama_beasiswa') is-invalid @enderror"
                        name="nama_beasiswa" value="{{ old('nama_beasiswa', $data->nama_beasiswa ?? '') }}"
                        placeholder="Nama Beasiswa">
                    @error('nama_beasiswa')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-12">
                    <label class="form-label">Nama Lembaga</label>
                    <input type="text" class="form-control @error('nama_lembaga') is-invalid @enderror"
                        name="nama_lembaga" value="{{ old('nama_lembaga', $data->nama_lembaga ?? '') }}"
                        placeholder="Nama Lembaga">
                    @error('nama_lembaga')
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
