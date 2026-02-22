@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">{{ $data ? 'Edit' : 'Tambah' }} Data Role</h6>
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
                    <label class="form-label">Nama Role</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                        value="{{ old('name', $data->name ?? '') }}" placeholder="Nama Role">
                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-12">
                    <label class="form-label">Pilih Permission</label>
                    @foreach ($permissions as $item)
                        <div class="form-check">
                            <input class="form-check-input @error('permissions') is-invalid @enderror" name="permissions[]"
                                type="checkbox" value="{{ $item }}" id="permission_{{ $loop->index }}"
                                {{ is_array(old('permissions'))
                                    ? in_array($item, old('permissions'))
                                    : (isset($data) && $data->permissions->pluck('name')->contains($item)
                                        ? 'checked'
                                        : '') }}>
                            <label class="form-check-label" for="permission_{{ $loop->index }}">
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
