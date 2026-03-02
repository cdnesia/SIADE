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
                <div class="col-md-6">
                    <label class="form-label">Nama Mahasiswa</label>
                    <select name="npm" class="form-select select2 @error('npm') is-invalid @enderror"
                        data-placeholder="--Pilih Mahasiswa--">
                        <option value=""></option>
                        @foreach ($mahasiswa as $item => $val)
                            <option value="{{ $item }}"
                                {{ $item == old('npm', $data ? $data->npm : null) ? 'selected' : '' }}>
                                {{ '[' . $item . '] ' . $val }}</option>
                        @endforeach
                    </select>
                    @error('npm')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Lembaga Beasiswa</label>
                    <select name="lembaga" class="form-select select2 @error('lembaga') is-invalid @enderror"
                        data-placeholder="--Pilih Lembaga--">
                        <option value=""></option>
                        @foreach ($lembaga as $item => $val)
                            <option value="{{ $item }}"
                                {{ $item == old('lembaga', $data ? $data->id_lembaga : null) ? 'selected' : '' }}>
                                {{ $val }}</option>
                        @endforeach
                    </select>
                    @error('lembaga')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                {{-- <div class="col-md-6">
                    <label class="form-label">Tahun Akademik</label>
                    <select name="tahun_akademik[]"
                        class="form-select multiple-select2 @error('tahun_akademik') is-invalid @enderror"
                        data-placeholder="--Pilih Tahun Akademik--" multiple>
                        <option value=""></option>
                        @foreach ($tahun_akademik as $item)
                            <option value="{{ $item->kode_tahun_akademik }}"
                                {{ in_array($item->kode_tahun_akademik, old('tahun_akademik', $data ? json_decode($data->tahun_akademik, true) : []))
                                    ? 'selected'
                                    : '' }}>
                                {{ $item->kode_tahun_akademik }}</option>
                        @endforeach
                    </select>
                    @error('tahun_akademik')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div> --}}
                <div class="col-md-6">
                    <label class="form-label d-block">Tahun Akademik</label>

                    @php
                        $selected = old('tahun_akademik', $data ? json_decode($data->tahun_akademik, true) : []);
                    @endphp

                    <div class="row">
                        @foreach ($tahun_akademik as $item)
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input @error('tahun_akademik') is-invalid @enderror"
                                        type="checkbox" name="tahun_akademik[]" value="{{ $item->kode_tahun_akademik }}"
                                        id="ta_{{ $item->kode_tahun_akademik }}"
                                        {{ in_array($item->kode_tahun_akademik, $selected) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ta_{{ $item->kode_tahun_akademik }}">
                                        {{ $item->kode_tahun_akademik }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('tahun_akademik')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Jumlah Jaminan</label>
                    <input type="text" class="form-control @error('jumlah_jaminan') is-invalid @enderror"
                        name="jumlah_jaminan" value="{{ old('jumlah_jaminan', $data ? (int) $data->jumlah_jaminan : '') }}"
                        placeholder="Jumlah Jaminan">
                    @error('jumlah_jaminan')
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
