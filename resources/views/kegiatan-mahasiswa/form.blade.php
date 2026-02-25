@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">{{ $data ? 'Edit' : 'Tambah' }} Kegiatan Mahasiswa</h6>
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
                    <label class="form-label">Nama Kegiatan</label>
                    <input type="text" class="form-control @error('nama_kegiatan') is-invalid @enderror"
                        name="nama_kegiatan" value="{{ old('nama_kegiatan', $data->nama_kegiatan ?? '') }}"
                        placeholder="Nama Kegiatan">
                    @error('nama_kegiatan')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tahun Angkatan</label>
                    <select name="tahun_angkatan[]"
                        class="form-select multiple-select2 @error('tahun_angkatan') is-invalid @enderror"
                        data-placeholder="--Pilih Tahun Angkatan--" multiple>
                        <option value=""></option>
                        @foreach ($tahun_angkatan as $item)
                            <option value="{{ $item }}"
                                {{ in_array($item, old('tahun_angkatan', $data ? json_decode($data->tahun_angkatan, true) : []))
                                    ? 'selected'
                                    : '' }}>
                                {{ $item }}</option>
                        @endforeach
                    </select>
                    @error('tahun_angkatan')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Program Studi</label>
                    <select name="kode_program_studi[]"
                        class="form-select multiple-select2 @error('kode_program_studi') is-invalid @enderror"
                        data-placeholder="--Pilih Program Studi--" multiple>
                        <option value=""></option>
                        @foreach ($prodi as $item)
                            <option value="{{ $item->kode_program_studi }}"
                                {{ in_array(
                                    $item->kode_program_studi,
                                    old('kode_program_studi', $data ? json_decode($data->kode_program_studi, true) : []),
                                )
                                    ? 'selected'
                                    : '' }}>
                                {{ $item->nama_program_studi_idn }}</option>
                        @endforeach
                    </select>
                    @error('kode_program_studi')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kelas Perkuliahan</label>
                    <select name="kelas_perkuliahan_id" id="kelas_perkuliahan_id" class="form-select select2 @error('kelas_perkuliahan_id') is-invalid @enderror" data-placeholder="--Pilih Kelas Perkuliahan--">
                        <option value=""></option>
                        @foreach ($kelas_perkuliahan as $item)
                            <option value="{{ $item->id }}"
                                {{ old('kelas_perkuliahan_id', $data->kelas_perkuliahan_id ?? '') == $item->id ? 'selected' : '' }}>
                                {{ $item->nama_program_perkuliahan }}
                            </option>
                        @endforeach
                        @error('kelas_perkuliahan_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Minimal SKS</label>
                    <input type="text" class="form-control @error('minimal_sks') is-invalid @enderror" name="minimal_sks"
                        value="{{ old('minimal_sks', $data->minimal_sks ?? '') }}" placeholder="Minimal SKS">
                    @error('minimal_sks')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Minimal Semester</label>
                    <input type="text" class="form-control @error('minimal_semester') is-invalid @enderror"
                        name="minimal_semester" value="{{ old('minimal_semester', $data->minimal_semester ?? '') }}"
                        placeholder="Minimal Semester">
                    @error('minimal_semester')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Maksimal Nilai D</label>
                    <input type="text" class="form-control @error('maksimal_nilai_d') is-invalid @enderror"
                        name="maksimal_nilai_d" value="{{ old('maksimal_nilai_d', $data->maksimal_nilai_d ?? '') }}"
                        placeholder="Maksimal Nilai D">
                    @error('maksimal_nilai_d')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nama Biaya</label>
                    <select name="nama_biaya" id="nama_biaya" class="form-select select2 @error('nama_biaya') is-invalid @enderror" data-placeholder="--Biaya dan Potongan--">
                        <option value=""></option>
                        @foreach ($bipot as $item)
                            <option value="{{ $item['id'] }}"
                                {{ old('nama_biaya', $data->id_bipot ?? '') == $item['id'] ? 'selected' : '' }}>
                                {{ $item['nama_bipot'] }}
                            </option>
                        @endforeach
                        @error('nama_biaya')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Biaya Pendaftaran</label>
                    <input type="text" class="form-control @error('biaya_pendaftaran') is-invalid @enderror"
                        name="biaya_pendaftaran" value="{{ old('biaya_pendaftaran', $data->biaya_pendaftaran ?? '') }}"
                        placeholder="Biaya Pendaftaran">
                    @error('biaya_pendaftaran')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jenis Kegiatan</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input @error('tipe') is-invalid @enderror" id="17"
                                type="radio" value="KKN" name="tipe"
                                {{ old('tipe', $data->tipe ?? '') == 'KKN' ? 'checked' : '' }}>
                            <label class="form-check-label" for="17">KKN</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input @error('tipe') is-invalid @enderror" id="18"
                                type="radio" value="PKL" name="tipe"
                                {{ old('tipe', $data->tipe ?? '') == 'PKL' ? 'checked' : '' }}>
                            <label class="form-check-label" for="18">PKL</label>
                        </div>
                    </div>
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
