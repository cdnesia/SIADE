@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">{{ $data ? 'Edit' : 'Tambah' }} Data Permission</h6>
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
                    <label class="form-label">Pilih Route Untuk Di Masukan Ke Permission</label>

                    @foreach ($route as $item)
                        <div class="form-check">
                            <input class="form-check-input @error('permissions') is-invalid @enderror" name="permissions[]"
                                type="checkbox" value="{{ $item }}" id="{{ $item }}"
                                {{ is_array(old('permissions')) && in_array($item, old('permissions')) ? 'checked' : '' }}>
                            <label class="form-check-label" for="{{ $item }}">
                                {{ $item }}
                            </label>
                        </div>
                    @endforeach

                    @error('permissions')
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
