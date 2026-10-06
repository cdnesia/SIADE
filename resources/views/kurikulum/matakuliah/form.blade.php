@extends('layouts.app')
@section('content')
    @php
        $kurikulumId = Crypt::encrypt($kurikulum->id);
        $prodiAktif = old('kode_program_studi', $data->kode_program_studi ?? $kode_program_studi);
    @endphp
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <div>
                <h6 class="mb-0">{{ $data ? 'Edit' : 'Tambah' }} Mata Kuliah</h6>
                <small class="text-muted">Kurikulum {{ $kurikulum->kode_kurikulum }} &mdash;
                    {{ $kurikulum->nama_kurikulum }}</small>
            </div>
            <div class="ms-auto">
                <a href="{{ route($modul . '.index', [$kurikulumId, 'prodi' => $prodiAktif]) }}"
                    class="btn btn-sm btn-outline-secondary"><i class='bx bx-arrow-back'></i> Kembali</a>
            </div>
        </div>

        <div class="card-body">
            <form method="POST"
                action="{{ $data ? route($modul . '.update', [$kurikulumId, Crypt::encrypt($data->id)]) : route($modul . '.store', $kurikulumId) }}"
                class="row g-3">
                @csrf
                @if ($data)
                    @method('PUT')
                @endif

                <div class="col-12">
                    <h6 class="mb-0">Identitas Mata Kuliah</h6>
                    <small class="text-muted">Program studi, kode, dan nama mata kuliah.</small>
                    <hr class="mt-2 mb-0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Program Studi</label>
                    <select name="kode_program_studi" id="kode_program_studi"
                        class="form-select select2 @error('kode_program_studi') is-invalid @enderror"
                        data-placeholder="--Pilih Program Studi--">
                        <option value=""></option>
                        @foreach ($prodi as $item)
                            <option value="{{ $item->kode_program_studi }}"
                                {{ $prodiAktif == $item->kode_program_studi ? 'selected' : '' }}>
                                {{ $item->kode_program_studi }} - {{ $item->nama_program_studi_idn }}
                            </option>
                        @endforeach
                    </select>
                    @error('kode_program_studi')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kode Mata Kuliah</label>
                    <input type="text" class="form-control @error('kode_mata_kuliah') is-invalid @enderror"
                        name="kode_mata_kuliah" maxlength="20"
                        value="{{ old('kode_mata_kuliah', $data->kode_mata_kuliah ?? '') }}" placeholder="Contoh: AGA-101">
                    @error('kode_mata_kuliah')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Semester</label>
                    <input type="number" min="1" max="14"
                        class="form-control @error('semester') is-invalid @enderror" name="semester"
                        value="{{ old('semester', $data->semester ?? 1) }}">
                    @error('semester')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Nama Mata Kuliah (Indonesia)</label>
                    <input type="text" class="form-control @error('nama_mata_kuliah_idn') is-invalid @enderror"
                        name="nama_mata_kuliah_idn" maxlength="200"
                        value="{{ old('nama_mata_kuliah_idn', $data->nama_mata_kuliah_idn ?? '') }}"
                        placeholder="Contoh: Pendidikan Agama">
                    @error('nama_mata_kuliah_idn')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Mata Kuliah (Inggris)</label>
                    <input type="text" class="form-control @error('nama_mata_kuliah_eng') is-invalid @enderror"
                        name="nama_mata_kuliah_eng" maxlength="200"
                        value="{{ old('nama_mata_kuliah_eng', $data->nama_mata_kuliah_eng ?? '') }}"
                        placeholder="Contoh: Religious Study">
                    @error('nama_mata_kuliah_eng')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tipe Mata Kuliah</label>
                    <select name="mata_kuliah_tipe"
                        class="form-select select2 @error('mata_kuliah_tipe') is-invalid @enderror"
                        data-placeholder="--Pilih Tipe--">
                        <option value=""></option>
                        @foreach ($tipe as $item)
                            <option value="{{ $item->id }}"
                                {{ (string) old('mata_kuliah_tipe', $data->mata_kuliah_tipe ?? 0) === (string) $item->id ? 'selected' : '' }}>
                                {{ $item->nama }}</option>
                        @endforeach
                    </select>
                    @error('mata_kuliah_tipe')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Jenis Mata Kuliah</label>
                    <select name="jenis_matakuliah_id"
                        class="form-select select2 @error('jenis_matakuliah_id') is-invalid @enderror"
                        data-placeholder="--Pilih Jenis--">
                        <option value=""></option>
                        @foreach ($jenis as $item)
                            <option value="{{ $item->id }}"
                                {{ old('jenis_matakuliah_id', $data->jenis_matakuliah_id ?? '') == $item->id ? 'selected' : '' }}>
                                {{ $item->nama }}</option>
                        @endforeach
                    </select>
                    @error('jenis_matakuliah_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <h6 class="mb-0">Bobot SKS &amp; Pertemuan</h6>
                    <small class="text-muted">Total SKS dihitung otomatis dari jumlah komponen.</small>
                    <hr class="mt-2 mb-0">
                </div>
                @foreach ([
                    'sks_tatap_muka' => 'SKS Tatap Muka',
                    'sks_praktek' => 'SKS Praktikum',
                    'sks_prak_lap' => 'SKS Praktik Lapangan',
                    'sks_simulasi' => 'SKS Simulasi',
                ] as $field => $label)
                    <div class="col-6 col-md-2">
                        <label class="form-label">{{ $label }}</label>
                        <input type="number" min="0" max="24"
                            class="form-control input-sks @error($field) is-invalid @enderror" name="{{ $field }}"
                            value="{{ old($field, $data->$field ?? 0) }}">
                        @error($field)
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
                <div class="col-md-4">
                    <label class="form-label">Total SKS</label>
                    <input type="text" class="form-control bg-light fw-semibold" id="total_sks" readonly
                        value="{{ $data->sks_mata_kuliah ?? 0 }}">
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Minimal Pertemuan</label>
                    <input type="number" min="1" class="form-control @error('min_pertemuan') is-invalid @enderror"
                        name="min_pertemuan" value="{{ old('min_pertemuan', $data->min_pertemuan ?? 14) }}">
                    @error('min_pertemuan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Maksimal Pertemuan</label>
                    <input type="number" min="1" class="form-control @error('max_pertemuan') is-invalid @enderror"
                        name="max_pertemuan" value="{{ old('max_pertemuan', $data->max_pertemuan ?? 16) }}">
                    @error('max_pertemuan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <h6 class="mb-0">Pengaturan</h6>
                    <small class="text-muted">Status, prasyarat, dan kategori khusus.</small>
                    <hr class="mt-2 mb-0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select select2 @error('status') is-invalid @enderror"
                        data-placeholder="--Pilih Status--">
                        <option value=""></option>
                        <option value="A" {{ old('status', $data->status ?? 'A') == 'A' ? 'selected' : '' }}>Aktif
                        </option>
                        <option value="N" {{ old('status', $data->status ?? 'A') == 'N' ? 'selected' : '' }}>Tidak
                            Aktif</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-8">
                    <label class="form-label">Prasyarat Lulus</label>
                    <select name="prasyarat_lulus[]" id="prasyarat_lulus"
                        class="form-select @error('prasyarat_lulus') is-invalid @enderror @error('prasyarat_lulus.*') is-invalid @enderror"
                        data-placeholder="--Pilih mata kuliah prasyarat (opsional)--" multiple>
                    </select>
                    <small class="text-muted">Mata kuliah yang harus lulus sebelum mengambil mata kuliah ini.</small>
                    @error('prasyarat_lulus.*')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12 d-flex flex-wrap gap-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_mbkm" value="1" id="is_mbkm"
                            {{ old('is_mbkm', $data->is_mbkm ?? 0) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_mbkm">Mata kuliah MBKM</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_mk_universitas" value="1"
                            id="is_mk_universitas"
                            {{ old('is_mk_universitas', $data->is_mk_universitas ?? 0) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_mk_universitas">Mata kuliah universitas</label>
                    </div>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 border-top pt-3">
                    <a href="{{ route($modul . '.index', [$kurikulumId, 'prodi' => $prodiAktif]) }}"
                        class="btn btn-outline-secondary btn-sm">Batal</a>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class='bx bx-save'></i> {{ $data ? 'Simpan Perubahan' : 'Simpan Mata Kuliah' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('js')
    @php
        $opsiPrasyarat = $kandidat_prasyarat->where('id', '!=', $data->id ?? null)->values();
        $prasyaratTerpilih = array_map('intval', old('prasyarat_lulus', $data->prasyarat_lulus ?? []));
    @endphp
    <script>
        $(function() {
            // Hitung total SKS otomatis
            function hitungSks() {
                let total = 0;
                $('.input-sks').each(function() {
                    total += parseInt($(this).val()) || 0;
                });
                $('#total_sks').val(total);
            }
            $('.input-sks').on('input', hitungSks);
            hitungSks();

            // Opsi prasyarat hanya dari prodi yang dipilih
            const kandidat = @json($opsiPrasyarat);
            let terpilih = @json($prasyaratTerpilih);
            const $prasyarat = $('#prasyarat_lulus');

            function isiPrasyarat() {
                const prodi = $('#kode_program_studi').val();
                if ($prasyarat.hasClass('select2-hidden-accessible')) {
                    $prasyarat.select2('destroy');
                }
                $prasyarat.empty();
                kandidat.filter(mk => mk.kode_program_studi === prodi).forEach(mk => {
                    const opsi = new Option(
                        `Smt ${mk.semester} · ${mk.kode_mata_kuliah} - ${mk.nama_mata_kuliah_idn}`,
                        mk.id, false, terpilih.includes(mk.id)
                    );
                    $prasyarat.append(opsi);
                });
                $prasyarat.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: $prasyarat.data('placeholder'),
                    closeOnSelect: false,
                });
            }

            $prasyarat.on('change', function() {
                terpilih = ($(this).val() || []).map(Number);
            });
            $('#kode_program_studi').on('change', isiPrasyarat);
            isiPrasyarat();
        });
    </script>
@endpush
