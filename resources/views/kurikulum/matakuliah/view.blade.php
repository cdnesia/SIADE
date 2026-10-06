@extends('layouts.app')
@section('content')
    @php
        $kurikulumId = Crypt::encrypt($kurikulum->id);
        $prodiTerpilih = $prodi->firstWhere('kode_program_studi', $kode_program_studi);
        $perSemester = $matakuliah->groupBy('semester');
    @endphp
    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2 py-3">
            <div>
                <h6 class="mb-0">Mata Kuliah Kurikulum {{ $kurikulum->nama_kurikulum }}</h6>
                <small class="text-muted">{{ $kurikulum->keterangan ?: $kurikulum->kode_kurikulum }}</small>
            </div>
            <div class="ms-auto d-flex gap-1">
                <a href="{{ route('kurikulum.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class='bx bx-arrow-back'></i> Kembali
                </a>
                @can('kurikulum.edit')
                    <a href="{{ route('kurikulum.edit', $kurikulumId) }}" class="btn btn-sm btn-outline-primary">
                        <i class='bx bx-cog'></i> Atur Prodi
                    </a>
                @endcan
                @if ($kode_program_studi)
                    @can($modul . '.create')
                        <a href="{{ route($modul . '.create', [$kurikulumId, 'prodi' => $kode_program_studi]) }}"
                            class="btn btn-sm btn-primary"><i class='bx bx-plus'></i> Tambah Mata Kuliah</a>
                    @endcan
                @endif
            </div>
        </div>
        <div class="card-body">
            <nav class="mb-3" aria-label="Filter program studi">
                <div class="small text-muted mb-2">Program Studi</div>
                <div class="d-flex flex-nowrap flex-md-wrap gap-2 overflow-auto pb-1">
                    @forelse ($prodi as $item)
                        <a href="{{ route($modul . '.index', [$kurikulumId, 'prodi' => $item->kode_program_studi]) }}"
                            class="btn btn-sm d-inline-flex align-items-center gap-2 text-nowrap flex-shrink-0 {{ ($kode_program_studi == $item->kode_program_studi) ? 'btn-primary' : 'btn-outline-secondary' }}"
                            @if (($kode_program_studi == $item->kode_program_studi)) aria-current="page" @endif
                            title="{{ $item->kode_program_studi }}">
                            {{ $item->nama_program_studi_idn }}
                            <span class="badge rounded-pill {{ ($kode_program_studi == $item->kode_program_studi) ? 'bg-white text-primary' : 'bg-secondary-subtle text-secondary-emphasis' }}">{{ $item->jumlah_mk }}</span>
                        </a>
                    @empty
                        <span class="text-muted">
                            Belum ada program studi yang masuk kurikulum ini.
                            @can('kurikulum.edit')
                                <a href="{{ route('kurikulum.edit', $kurikulumId) }}">Atur program studi</a>
                            @endcan
                        </span>
                    @endforelse
                </div>
            </nav>

            @if ($matakuliah->isNotEmpty())
                <div class="mb-3">
                    <label class="form-label" for="cari-mk">Cari Mata Kuliah</label>
                    <input type="search" id="cari-mk" class="form-control" placeholder="Ketik kode atau nama mata kuliah">
                </div>
            @endif

            @if ($matakuliah->isNotEmpty())
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="text-muted small">Jumlah Mata Kuliah</div>
                            <div class="fs-4 fw-bold">{{ $matakuliah->count() }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="text-muted small">Total SKS (aktif)</div>
                            <div class="fs-4 fw-bold">{{ $matakuliah->where('status', 'A')->sum('sks_mata_kuliah') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="text-muted small">Jumlah Semester</div>
                            <div class="fs-4 fw-bold">{{ $perSemester->count() }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="text-muted small">Tidak Aktif</div>
                            <div class="fs-4 fw-bold">{{ $matakuliah->where('status', 'N')->count() }}</div>
                        </div>
                    </div>
                </div>
            @endif

            @if (!$kode_program_studi)
                <div class="text-center text-muted border rounded-3 py-5">
                    <i class='bx bx-filter-alt fs-1 d-block mb-2'></i>
                    Pilih program studi terlebih dahulu untuk melihat mata kuliah.
                </div>
            @elseif ($matakuliah->isEmpty())
                <div class="text-center text-muted border rounded-3 py-5">
                    <i class='bx bx-book-open fs-1 d-block mb-2'></i>
                    <p class="mb-3">Belum ada mata kuliah untuk
                        <strong>{{ $prodiTerpilih->nama_program_studi_idn ?? $kode_program_studi }}</strong>
                        pada kurikulum ini.
                    </p>
                    @can($modul . '.create')
                        <a href="{{ route($modul . '.create', [$kurikulumId, 'prodi' => $kode_program_studi]) }}"
                            class="btn btn-sm btn-primary"><i class='bx bx-plus'></i> Tambah Mata Kuliah</a>
                    @endcan
                </div>
            @else
                <div id="hasil-kosong" class="text-center text-muted border rounded-3 py-4 d-none">
                    Tidak ada mata kuliah yang cocok dengan pencarian.
                </div>

                @foreach ($perSemester as $semester => $daftar)
                    <section class="grup-semester mb-4">
                        <div class="d-flex align-items-center justify-content-between bg-light border rounded-top-3 px-3 py-2">
                            <h6 class="mb-0">Semester {{ $semester }}</h6>
                            <small class="text-muted">
                                {{ $daftar->count() }} MK &middot;
                                <strong>{{ $daftar->where('status', 'A')->sum('sks_mata_kuliah') }} SKS</strong>
                            </small>
                        </div>
                        <div class="table-responsive border border-top-0 rounded-bottom-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="small text-muted">
                                    <tr>
                                        <th style="width:110px">Kode</th>
                                        <th>Nama Mata Kuliah</th>
                                        <th class="text-center" style="width:70px">SKS</th>
                                        <th style="width:150px">Tipe / Jenis</th>
                                        <th style="width:100px">Status</th>
                                        @canany([$modul . '.edit', $modul . '.destroy'])
                                            <th class="text-end" style="width:100px">Aksi</th>
                                        @endcanany
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($daftar as $item)
                                        <tr class="baris-mk"
                                            data-cari="{{ strtolower($item->kode_mata_kuliah . ' ' . $item->nama_mata_kuliah_idn . ' ' . $item->nama_mata_kuliah_eng) }}">
                                            <td class="fw-semibold">{{ $item->kode_mata_kuliah }}</td>
                                            <td>
                                                {{ $item->nama_mata_kuliah_idn }}
                                                @if ($item->is_mbkm)
                                                    <span class="badge bg-info-subtle text-info-emphasis ms-1">MBKM</span>
                                                @endif
                                                @if ($item->is_mk_universitas)
                                                    <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">MKU</span>
                                                @endif
                                                @if ($item->nama_mata_kuliah_eng)
                                                    <div class="small text-muted">{{ $item->nama_mata_kuliah_eng }}</div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-semibold">{{ $item->sks_mata_kuliah }}</span>
                                                @if ($item->sks_praktek || $item->sks_prak_lap || $item->sks_simulasi)
                                                    <div class="small text-muted"
                                                        title="Tatap muka / Praktikum / Praktik lapangan / Simulasi">
                                                        {{ $item->sks_tatap_muka }}/{{ $item->sks_praktek }}/{{ $item->sks_prak_lap }}/{{ $item->sks_simulasi }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $tipe[$item->mata_kuliah_tipe] ?? '-' }}
                                                @if (isset($jenis[$item->jenis_matakuliah_id]))
                                                    <div class="small text-muted">{{ $jenis[$item->jenis_matakuliah_id] }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($item->status == 'A')
                                                    <span class="badge bg-success-subtle text-success-emphasis">Aktif</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger-emphasis">Tidak Aktif</span>
                                                @endif
                                            </td>
                                            @canany([$modul . '.edit', $modul . '.destroy'])
                                                <td class="text-end text-nowrap">
                                                    @can($modul . '.edit')
                                                        <a href="{{ route($modul . '.edit', [$kurikulumId, Crypt::encrypt($item->id)]) }}"
                                                            class="btn btn-warning btn-sm" title="Edit"><i
                                                                class='bx bx-message-square-edit me-0'></i></a>
                                                    @endcan
                                                    @can($modul . '.destroy')
                                                        <form
                                                            action="{{ route($modul . '.destroy', [$kurikulumId, Crypt::encrypt($item->id)]) }}"
                                                            method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus"
                                                                onclick="return confirm('Yakin ingin menghapus {{ $item->kode_mata_kuliah }} - {{ addslashes($item->nama_mata_kuliah_idn) }}?')">
                                                                <i class='bx bx-message-square-x me-0'></i>
                                                            </button>
                                                        </form>
                                                    @endcan
                                                </td>
                                            @endcanany
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endforeach
            @endif
        </div>
    </div>
@endsection
@push('js')
    <script>
        // Pastikan prodi terpilih terlihat saat daftar prodi bisa digeser (mobile)
        const prodiAktif = document.querySelector('nav [aria-current="page"]');
        if (prodiAktif) {
            prodiAktif.parentElement.scrollLeft = prodiAktif.offsetLeft - prodiAktif.parentElement.offsetLeft - 16;
        }

        // Pencarian cepat di sisi klien, menyembunyikan semester yang tidak punya hasil
        $('#cari-mk').on('input', function() {
            const kata = $(this).val().toLowerCase().trim();
            $('.baris-mk').each(function() {
                $(this).toggle($(this).data('cari').includes(kata));
            });
            $('.grup-semester').each(function() {
                $(this).toggle($(this).find('.baris-mk:visible').length > 0);
            });
            $('#hasil-kosong').toggleClass('d-none', $('.baris-mk:visible').length > 0);
        });
    </script>
@endpush
