@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">{{ $data ? 'Edit' : 'Tambah' }} Penerima Beasiswa</h6>
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
                    <label class="form-label">Beasiswa</label>
                    <select name="lembaga" id="pilih-lembaga" class="form-select select2 @error('lembaga') is-invalid @enderror"
                        data-placeholder="--Pilih Beasiswa--">
                        <option value=""></option>
                        @foreach ($lembaga as $item)
                            <option value="{{ $item->id }}" data-tanggungan="{{ $item->jenis_tanggungan }}"
                                {{ $item->id == old('lembaga', $data ? $data->id_lembaga : null) ? 'selected' : '' }}>
                                {{ $item->nama_beasiswa }} ({{ $item->nama_lembaga }}) - {{ ucfirst($item->jenis_tanggungan) }}
                            </option>
                        @endforeach
                    </select>
                    @error('lembaga')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
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
                    <div id="wrap-jaminan">
                        <label class="form-label" for="jumlah_jaminan">Jumlah Jaminan (Rp)</label>
                        <input type="text" inputmode="numeric" id="jumlah_jaminan"
                            class="form-control @error('jumlah_jaminan') is-invalid @enderror" name="jumlah_jaminan"
                            value="{{ old('jumlah_jaminan', $data ? (int) $data->jumlah_jaminan : '') }}"
                            placeholder="Contoh: 2500000">
                        <div class="form-text">Nominal potongan tagihan per semester.</div>
                        @error('jumlah_jaminan')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div id="info-penuh" class="alert alert-info py-2 mb-0" style="display:none">
                        <i class="bx bx-info-circle me-1"></i>Beasiswa <b>penuh</b>: seluruh tagihan semester ditanggung,
                        jumlah jaminan tidak perlu diisi.
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
@push('js')
    <script>
        // Jumlah jaminan hanya tampil untuk beasiswa sebagian
        $(function() {
            function aturJaminan() {
                const penuh = $('#pilih-lembaga option:selected').data('tanggungan') === 'penuh';
                $('#wrap-jaminan').toggle(!penuh);
                $('#info-penuh').toggle(penuh);
            }
            $('#pilih-lembaga').on('change', aturJaminan);
            aturJaminan();
        });
    </script>
@endpush
