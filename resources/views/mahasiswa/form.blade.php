@extends('layouts.app')
@section('content')
    @php
        $wajib = '<span class="text-danger">*</span>';
    @endphp
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <div>
                <h6 class="mb-0">Tambah Mahasiswa</h6>
                <small class="text-muted">Data masuk ke master mahasiswa dan akun login dibuat otomatis (username &
                    password = NPM). Kolom bertanda {!! $wajib !!} wajib diisi.</small>
            </div>
            <div class="ms-auto">
                <a href="{{ route($modul . '.index') }}" class="btn btn-sm btn-warning">Kembali</a>
            </div>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route($modul . '.store') }}" id="form-mahasiswa">
                @csrf

                <h6 class="text-primary border-bottom pb-2 mb-3"><i class='bx bx-id-card'></i> Data Akademik</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label" for="npm">NPM {!! $wajib !!}</label>
                        <input type="text" name="npm" id="npm" maxlength="20" required
                            class="form-control text-uppercase @error('npm') is-invalid @enderror"
                            value="{{ old('npm') }}" placeholder="Contoh: S12654001">
                        @error('npm')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="nama_mahasiswa">Nama Mahasiswa {!! $wajib !!}</label>
                        <input type="text" name="nama_mahasiswa" id="nama_mahasiswa" maxlength="200" required
                            class="form-control text-uppercase @error('nama_mahasiswa') is-invalid @enderror"
                            value="{{ old('nama_mahasiswa') }}">
                        @error('nama_mahasiswa')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="kode_program_studi">Program Studi {!! $wajib !!}</label>
                        <select name="kode_program_studi" id="kode_program_studi" required
                            class="form-select select2 @error('kode_program_studi') is-invalid @enderror"
                            data-placeholder="-- Pilih Program Studi --">
                            <option value=""></option>
                            @foreach ($prodi as $p)
                                <option value="{{ $p->kode_program_studi }}"
                                    {{ old('kode_program_studi') == $p->kode_program_studi ? 'selected' : '' }}>
                                    {{ $p->nama_program_studi_idn }}</option>
                            @endforeach
                        </select>
                        @error('kode_program_studi')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label" for="tahun_angkatan">Angkatan {!! $wajib !!}</label>
                        <input type="text" name="tahun_angkatan" id="tahun_angkatan" maxlength="5" required
                            inputmode="numeric" class="form-control @error('tahun_angkatan') is-invalid @enderror"
                            value="{{ old('tahun_angkatan', $angkatan_default) }}">
                        <div class="form-text">Tahun + semester, contoh 20261</div>
                        @error('tahun_angkatan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label" for="program_kuliah_id">Kelas {!! $wajib !!}</label>
                        <select name="program_kuliah_id" id="program_kuliah_id" required
                            class="form-select @error('program_kuliah_id') is-invalid @enderror">
                            <option value="">-- Pilih --</option>
                            @foreach ($kelas as $k)
                                <option value="{{ $k->id }}" {{ old('program_kuliah_id') == $k->id ? 'selected' : '' }}>
                                    {{ $k->nama_program_perkuliahan }}</option>
                            @endforeach
                        </select>
                        @error('program_kuliah_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label" for="jenis_pendaftaran_id">Jenis Pendaftaran {!! $wajib !!}</label>
                        <select name="jenis_pendaftaran_id" id="jenis_pendaftaran_id" required
                            class="form-select @error('jenis_pendaftaran_id') is-invalid @enderror">
                            @foreach ($jenis_pendaftaran as $j)
                                <option value="{{ $j->id }}"
                                    {{ old('jenis_pendaftaran_id', 1) == $j->id ? 'selected' : '' }}>
                                    {{ $j->nama_jenis_pendaftaran }}</option>
                            @endforeach
                        </select>
                        @error('jenis_pendaftaran_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <h6 class="text-primary border-bottom pb-2 mb-3"><i class='bx bx-user'></i> Data Pribadi</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label" for="jenis_kelamin">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="jenis_kelamin"
                            class="form-select @error('jenis_kelamin') is-invalid @enderror">
                            <option value="">-- Pilih --</option>
                            <option value="L" {{ old('jenis_kelamin') === 'L' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="P" {{ old('jenis_kelamin') === 'P' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                        @error('jenis_kelamin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @foreach ([
                        ['tempat_lahir', 'Tempat Lahir', 'col-md-4', 'text', 100],
                        ['tanggal_lahir', 'Tanggal Lahir', 'col-md-4', 'date', null],
                        ['nik', 'NIK', 'col-md-4', 'text', 20],
                        ['nisn', 'NISN', 'col-md-4', 'text', 20],
                        ['npsn', 'NPSN Sekolah Asal', 'col-md-4', 'text', 20],
                        ['no_kipk', 'No. KIP Kuliah', 'col-md-4', 'text', 25],
                        ['email', 'Email', 'col-md-4', 'email', 150],
                        ['handphone', 'No. Handphone', 'col-md-4', 'tel', 30],
                    ] as [$nama, $label, $kolom, $tipe, $maks])
                        <div class="{{ $kolom }}">
                            <label class="form-label" for="{{ $nama }}">{{ $label }}</label>
                            <input type="{{ $tipe }}" name="{{ $nama }}" id="{{ $nama }}"
                                @if ($maks) maxlength="{{ $maks }}" @endif
                                @if ($tipe === 'date') max="{{ now()->subDay()->toDateString() }}" @endif
                                @if ($nama === 'nik') inputmode="numeric" @endif
                                class="form-control @error($nama) is-invalid @enderror" value="{{ old($nama) }}">
                            @error($nama)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <h6 class="text-primary border-bottom pb-2 mb-3"><i class='bx bx-group'></i> Orang Tua / Wali</h6>
                <div class="row g-3 mb-4">
                    @foreach ([
                        ['nama_ayah', 'Nama Ayah'],
                        ['nama_ibu_kandung', 'Nama Ibu Kandung'],
                        ['nama_wali', 'Nama Wali'],
                    ] as [$nama, $label])
                        <div class="col-md-4">
                            <label class="form-label" for="{{ $nama }}">{{ $label }}</label>
                            <input type="text" name="{{ $nama }}" id="{{ $nama }}" maxlength="150"
                                class="form-control @error($nama) is-invalid @enderror" value="{{ old($nama) }}">
                            @error($nama)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <a href="{{ route($modul . '.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary" id="btn-simpan">
                        <i class='bx bx-save'></i> Simpan Mahasiswa
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('js')
    <script>
        $(function() {
            $('#form-mahasiswa').on('submit', function() {
                $('#btn-simpan').prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...');
            });
        });
    </script>
@endpush
