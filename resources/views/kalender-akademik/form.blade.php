@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">{{ $data ? 'Edit' : 'Tambah' }} Kalender Akademik</h6>
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
                    <label class="form-label">Tahun Akademik</label>
                    <select name="kode_tahun_akademik"
                        class="form-select select2 @error('kode_tahun_akademik') is-invalid @enderror"
                        data-placeholder="--Pilih Tahun Akademik--">
                        <option value=""></option>
                        @foreach ($tahun_akademik as $item)
                            <option value="{{ $item->kode_tahun_akademik }}"
                                {{ old('kode_tahun_akademik', $data->kode_tahun_akademik ?? '') == $item->kode_tahun_akademik ? 'selected' : '' }}>
                                {{ $item->nama_tahun_akademik }}</option>
                        @endforeach
                    </select>
                    @error('kode_tahun_akademik')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-6">
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
                <div class="col-md-12">
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
                <div class="col-md-6">
                    <label class="form-label">Tanggal Buka</label>
                    <input type="date" class="form-control @error('tanggal_mulai') is-invalid @enderror"
                        name="tanggal_mulai" value="{{ old('tanggal_mulai', $data->tanggal_mulai ?? '') }}"
                        placeholder="Nama Kegiatan">
                    @error('tanggal_mulai')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal Tutup</label>
                    <input type="date" class="form-control @error('tanggal_selesai') is-invalid @enderror"
                        name="tanggal_selesai" value="{{ old('tanggal_selesai', $data->tanggal_selesai ?? '') }}"
                        placeholder="Nama Kegiatan">
                    @error('tanggal_selesai')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                {{-- Input Jadwal --}}
                <div class="col-md-3">
                    <label class="form-label">Input Jadwal</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" id="1" type="radio" value="1"
                                name="keg_input_jadwal"
                                {{ old('keg_input_jadwal', $data->keg_input_jadwal ?? '') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="1">Ya</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" id="2" type="radio" value="0"
                                name="keg_input_jadwal"
                                {{ old('keg_input_jadwal', $data->keg_input_jadwal ?? '') == '0' ? 'checked' : '' }}>
                            <label class="form-check-label" for="2">Tidak</label>
                        </div>
                    </div>
                </div>

                {{-- Input Jadwal SP --}}
                <div class="col-md-3">
                    <label class="form-label">Input Jadwal SP</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" id="3" type="radio" value="1"
                                name="keg_input_jadwal_sp"
                                {{ old('keg_input_jadwal_sp', $data->keg_input_jadwal_sp ?? '') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="3">Ya</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" id="4" type="radio" value="0"
                                name="keg_input_jadwal_sp"
                                {{ old('keg_input_jadwal_sp', $data->keg_input_jadwal_sp ?? '') == '0' ? 'checked' : '' }}>
                            <label class="form-check-label" for="4">Tidak</label>
                        </div>
                    </div>
                </div>

                {{-- Kontrak KRS --}}
                <div class="col-md-3">
                    <label class="form-label">Kontrak Mata Kuliah</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" id="5" type="radio" value="1"
                                name="keg_kontrak_krs"
                                {{ old('keg_kontrak_krs', $data->keg_kontrak_krs ?? '') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="5">Ya</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" id="6" type="radio" value="0"
                                name="keg_kontrak_krs"
                                {{ old('keg_kontrak_krs', $data->keg_kontrak_krs ?? '') == '0' ? 'checked' : '' }}>
                            <label class="form-check-label" for="6">Tidak</label>
                        </div>
                    </div>
                </div>

                {{-- Input Nilai --}}
                <div class="col-md-3">
                    <label class="form-label">Input Nilai</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" id="7" type="radio" value="1"
                                name="keg_input_nilai"
                                {{ old('keg_input_nilai', $data->keg_input_nilai ?? '') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="7">Ya</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" id="8" type="radio" value="0"
                                name="keg_input_nilai"
                                {{ old('keg_input_nilai', $data->keg_input_nilai ?? '') == '0' ? 'checked' : '' }}>
                            <label class="form-check-label" for="8">Tidak</label>
                        </div>
                    </div>
                </div>

                {{-- Pendaftaran KKN --}}
                <div class="col-md-3">
                    <label class="form-label">Pendaftaran KKN</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" id="9" type="radio" value="1"
                                name="keg_pendaftaran_kkn"
                                {{ old('keg_pendaftaran_kkn', $data->keg_pendaftaran_kkn ?? '') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="9">Ya</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" id="10" type="radio" value="0"
                                name="keg_pendaftaran_kkn"
                                {{ old('keg_pendaftaran_kkn', $data->keg_pendaftaran_kkn ?? '') == '0' ? 'checked' : '' }}>
                            <label class="form-check-label" for="10">Tidak</label>
                        </div>
                    </div>
                </div>

                {{-- Pendaftaran PKL --}}
                <div class="col-md-3">
                    <label class="form-label">Pendaftaran PKL</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" id="11" type="radio" value="1"
                                name="keg_pendaftaran_pkl"
                                {{ old('keg_pendaftaran_pkl', $data->keg_pendaftaran_pkl ?? '') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="11">Ya</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" id="12" type="radio" value="0"
                                name="keg_pendaftaran_pkl"
                                {{ old('keg_pendaftaran_pkl', $data->keg_pendaftaran_pkl ?? '') == '0' ? 'checked' : '' }}>
                            <label class="form-check-label" for="12">Tidak</label>
                        </div>
                    </div>
                </div>

                {{-- Seminar Proposal --}}
                <div class="col-md-3">
                    <label class="form-label">Pendaftaran Seminar Proposal</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" id="13" type="radio" value="1"
                                name="keg_pendaftaran_seminar_proposal"
                                {{ old('keg_pendaftaran_seminar_proposal', $data->keg_pendaftaran_seminar_proposal ?? '') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="13">Ya</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" id="14" type="radio" value="0"
                                name="keg_pendaftaran_seminar_proposal"
                                {{ old('keg_pendaftaran_seminar_proposal', $data->keg_pendaftaran_seminar_proposal ?? '') == '0' ? 'checked' : '' }}>
                            <label class="form-check-label" for="14">Tidak</label>
                        </div>
                    </div>
                </div>

                {{-- Sidang Akhir --}}
                <div class="col-md-3">
                    <label class="form-label">Pendaftaran Sidang Tugas Akhir</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" id="15" type="radio" value="1"
                                name="keg_pendaftaran_sidang_akhir"
                                {{ old('keg_pendaftaran_sidang_akhir', $data->keg_pendaftaran_sidang_akhir ?? '') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="15">Ya</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" id="16" type="radio" value="0"
                                name="keg_pendaftaran_sidang_akhir"
                                {{ old('keg_pendaftaran_sidang_akhir', $data->keg_pendaftaran_sidang_akhir ?? '') == '0' ? 'checked' : '' }}>
                            <label class="form-check-label" for="16">Tidak</label>
                        </div>
                    </div>
                </div>

                {{-- Pendaftaran Wisuda --}}
                <div class="col-md-3">
                    <label class="form-label">Pendaftaran Wisuda</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" id="17" type="radio" value="1"
                                name="keg_pendaftaran_wisuda"
                                {{ old('keg_pendaftaran_wisuda', $data->keg_pendaftaran_wisuda ?? '') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="17">Ya</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" id="18" type="radio" value="0"
                                name="keg_pendaftaran_wisuda"
                                {{ old('keg_pendaftaran_wisuda', $data->keg_pendaftaran_wisuda ?? '') == '0' ? 'checked' : '' }}>
                            <label class="form-check-label" for="18">Tidak</label>
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
