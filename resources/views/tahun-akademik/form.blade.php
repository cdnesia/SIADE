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
                    <label class="form-label">Kode Tahun Akademik</label>
                    <input type="text" class="form-control @error('kode_tahun_akademik') is-invalid @enderror"
                        name="kode_tahun_akademik"
                        value="{{ old('kode_tahun_akademik', $data->kode_tahun_akademik ?? '') }}"
                        placeholder="Kode Tahun Akademik">
                    @error('kode_tahun_akademik')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Tahun Akademik</label>
                    <input type="text" class="form-control @error('nama_tahun_akademik') is-invalid @enderror"
                        name="nama_tahun_akademik" disabled
                        value="{{ old('nama_tahun_akademik', $data->nama_tahun_akademik ?? '') }}"
                        placeholder="Kode Tahun Akademik">
                    @error('nama_tahun_akademik')
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
                    <label class="form-label">Tanggal Mulai</label>
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
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" class="form-control @error('tanggal_selesai') is-invalid @enderror"
                        name="tanggal_selesai" value="{{ old('tanggal_selesai', $data->tanggal_selesai ?? '') }}"
                        placeholder="Nama Kegiatan">
                    @error('tanggal_selesai')
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
@push('js')
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const kodeInput = document.querySelector('input[name="kode_tahun_akademik"]');
            const namaInput = document.querySelector('input[name="nama_tahun_akademik"]');

            kodeInput.addEventListener("keyup", function() {

                let kode = this.value.trim();

                // hanya boleh 5 digit angka
                let regex = /^\d{5}$/;

                if (!regex.test(kode)) {
                    namaInput.value = '';
                    return;
                }

                let tahun = kode.substring(0, 4);
                let semester = kode.substring(4, 5);

                let namaSemester = '';

                if (semester === '1') {
                    namaSemester = 'Ganjil';
                } else if (semester === '2') {
                    namaSemester = 'Genap';
                } else if (semester === '3') {
                    namaSemester = 'Pendek';
                } else {
                    namaInput.value = '';
                    return;
                }

                namaInput.value = tahun + ' ' + namaSemester;
            });

        });
    </script>
@endpush
