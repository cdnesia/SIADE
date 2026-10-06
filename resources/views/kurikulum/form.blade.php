@extends('layouts.app')
@section('content')
    @php
        $prodiDipilih = collect(old('prodi', $prodi_kurikulum->keys()->all()));
    @endphp
    <div class="card">
        <div class="card-header d-flex align-items-center py-3">
            <h6 class="mb-0">{{ $data ? 'Edit' : 'Tambah' }} Kurikulum</h6>
            <div class="ms-auto">
                <a href="{{ route($modul . '.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class='bx bx-arrow-back'></i> Kembali
                </a>
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

                <div class="col-12">
                    <h6 class="mb-0">Identitas Kurikulum</h6>
                    <hr class="mt-2 mb-0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kode Kurikulum</label>
                    <input type="text" class="form-control @error('kode_kurikulum') is-invalid @enderror"
                        name="kode_kurikulum" maxlength="20"
                        value="{{ old('kode_kurikulum', $data->kode_kurikulum ?? '') }}" placeholder="Contoh: OBE-2025">
                    @error('kode_kurikulum')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-5">
                    <label class="form-label">Nama Kurikulum</label>
                    <input type="text" class="form-control @error('nama_kurikulum') is-invalid @enderror"
                        name="nama_kurikulum" maxlength="150"
                        value="{{ old('nama_kurikulum', $data->nama_kurikulum ?? '') }}" placeholder="Contoh: OBE Baru">
                    @error('nama_kurikulum')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select select2 @error('status') is-invalid @enderror"
                        data-placeholder="--Pilih Status--">
                        <option value=""></option>
                        <option value="A" {{ old('status', $data->status ?? 'A') == 'A' ? 'selected' : '' }}>Aktif</option>
                        <option value="N" {{ old('status', $data->status ?? 'A') == 'N' ? 'selected' : '' }}>Tidak Aktif
                        </option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-12">
                    <label class="form-label">Keterangan</label>
                    <textarea class="form-control @error('keterangan') is-invalid @enderror" name="keterangan" rows="2"
                        placeholder="Contoh: Kurikulum 2025">{{ old('keterangan', $data->keterangan ?? '') }}</textarea>
                    @error('keterangan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 mt-4">
                    <div class="d-flex flex-wrap align-items-end gap-2">
                        <div>
                            <h6 class="mb-0">Program Studi</h6>
                            <small class="text-muted">Centang prodi yang memakai kurikulum ini. Prodi yang tidak dicentang
                                tidak bisa dipilih saat mengelola mata kuliah.</small>
                        </div>
                        <span class="ms-auto small text-muted"><strong id="jumlah-dipilih">0</strong> prodi dipilih</span>
                    </div>
                    <hr class="mt-2 mb-0">
                </div>
                <div class="col-12">
                    @if ($prodi->isEmpty())
                        <div class="text-center text-muted border rounded-3 py-4">Belum ada data program studi.</div>
                    @else
                        <div class="table-responsive border rounded-3">
                            <table class="table align-middle mb-0">
                                <thead class="small text-muted bg-light">
                                    <tr>
                                        <th style="width:50px" class="text-center">
                                            <input type="checkbox" class="form-check-input" id="pilih-semua"
                                                aria-label="Pilih semua prodi">
                                        </th>
                                        <th>Program Studi</th>
                                        <th class="text-center" style="width:110px">Mata Kuliah</th>
                                        <th style="min-width:280px">Angkatan yang memakai kurikulum ini</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($prodi as $item)
                                        @php
                                            $kode = $item->kode_program_studi;
                                            $masuk = $prodiDipilih->contains($kode);
                                            $angkatanProdi = array_map('intval', old("angkatan.$kode", $prodi_kurikulum[$kode] ?? []));
                                            $jumlah = $jumlah_mk[$kode] ?? 0;
                                        @endphp
                                        <tr class="baris-prodi {{ $masuk ? '' : 'text-muted' }}">
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input cek-prodi" name="prodi[]"
                                                    value="{{ $kode }}" id="prodi-{{ $kode }}"
                                                    data-jumlah-mk="{{ $jumlah }}" {{ $masuk ? 'checked' : '' }}>
                                            </td>
                                            <td>
                                                <label for="prodi-{{ $kode }}" class="mb-0 w-100" style="cursor:pointer">
                                                    <span class="fw-semibold">{{ $item->nama_program_studi_idn }}</span>
                                                    <div class="small text-muted">{{ $kode }}</div>
                                                </label>
                                            </td>
                                            <td class="text-center">
                                                @if ($jumlah)
                                                    <span class="badge bg-primary-subtle text-primary-emphasis">{{ $jumlah }} MK</span>
                                                @else
                                                    <span class="small text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <select name="angkatan[{{ $kode }}][]"
                                                    class="form-select pilih-angkatan @error("angkatan.$kode") is-invalid @enderror"
                                                    data-placeholder="Pilih angkatan" multiple
                                                    aria-label="Angkatan {{ $item->nama_program_studi_idn }}"
                                                    {{ $masuk ? '' : 'disabled' }}>
                                                    @foreach ($angkatan as $th)
                                                        <option value="{{ $th }}"
                                                            {{ in_array($th, $angkatanProdi) ? 'selected' : '' }}>
                                                            {{ substr($th, 0, 4) }}</option>
                                                    @endforeach
                                                </select>
                                                @error("angkatan.$kode")
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted d-block mt-2">
                            Angkatan dipakai untuk menentukan kurikulum mahasiswa saat KRS dan transfer nilai. Satu
                            angkatan pada satu prodi hanya boleh memakai satu kurikulum.
                        </small>
                    @endif
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 border-top pt-3">
                    <a href="{{ route($modul . '.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class='bx bx-save'></i> {{ $data ? 'Simpan Perubahan' : 'Simpan Kurikulum' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('js')
    <script>
        $(function() {
            $('.pilih-angkatan').select2({
                theme: 'bootstrap-5',
                width: '100%',
                closeOnSelect: false,
                placeholder: 'Pilih angkatan',
            });

            function perbaruiBaris(cek) {
                const $baris = $(cek).closest('tr');
                $baris.toggleClass('text-muted', !cek.checked);
                $baris.find('.pilih-angkatan').prop('disabled', !cek.checked);
            }

            function perbaruiRingkasan() {
                const total = $('.cek-prodi').length;
                const dipilih = $('.cek-prodi:checked').length;
                $('#jumlah-dipilih').text(dipilih);
                $('#pilih-semua').prop('checked', total > 0 && dipilih === total)
                    .prop('indeterminate', dipilih > 0 && dipilih < total);
            }

            $('.cek-prodi').on('change', function() {
                // Ingatkan jika prodi yang sudah punya mata kuliah dikeluarkan
                const jumlahMk = $(this).data('jumlah-mk');
                if (!this.checked && jumlahMk > 0 &&
                    !confirm(`Prodi ini sudah punya ${jumlahMk} mata kuliah di kurikulum ini. ` +
                        'Mata kuliahnya tetap tersimpan, tetapi prodi tidak bisa dipilih lagi sampai dicentang kembali. Lanjutkan?')) {
                    this.checked = true;
                }
                perbaruiBaris(this);
                perbaruiRingkasan();
            });

            $('#pilih-semua').on('change', function() {
                $('.cek-prodi').prop('checked', this.checked).each(function() {
                    perbaruiBaris(this);
                });
                perbaruiRingkasan();
            });

            perbaruiRingkasan();
        });
    </script>
@endpush
