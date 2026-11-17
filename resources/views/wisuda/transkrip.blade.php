@extends('layouts.app')

@push('css')
    <style>
        /* Baris matakuliah dobel ditandai merah */
        #tabel-transkrip tr.baris-dobel > td { background-color: var(--bs-danger-bg-subtle); }
        #tabel-transkrip tr.baris-dobel > td:first-child { box-shadow: inset 4px 0 0 var(--bs-danger); }
        #tabel-transkrip tr.baris-dobel.awal-grup > td { border-top: 2px solid var(--bs-danger-border-subtle); }
        /* Anggota dobel yang tidak diambil: dipudarkan, tidak dihitung ke IPK */
        #tabel-transkrip tr.tidak-diambil > td { opacity: .55; }
        #tabel-transkrip tr.tidak-diambil .nama-mk { text-decoration: line-through; }
        /* Saat area data dimuat ulang lewat AJAX */
        #area-data { transition: opacity .15s; }
        #area-data.memuat { opacity: .5; pointer-events: none; }
        .badge-titik::before { content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: currentColor; margin-right: 5px; vertical-align: middle; }
        .label-pilih { cursor: pointer; user-select: none; }
        .kartu-angka { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }

        @media print {
            .sidebar-wrapper, .topbar, .page-footer, .no-print, .back-to-top { display: none !important; }
            .page-wrapper { margin: 0 !important; padding: 0 !important; }
            #tabel-transkrip tr.tidak-diambil { display: none; }
            #tabel-transkrip tr > td { background: none !important; box-shadow: none !important; opacity: 1 !important; }
        }
    </style>
@endpush

@section('content')
    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-0"><i class="bx bx-award me-2"></i>Cek IPK & Transkrip Wisuda</h5>
            <small class="text-muted">Masukkan NPM untuk memeriksa transkrip nilai dan IPK. Matakuliah yang diambil lebih dari
                sekali ditandai merah, pilih salah satu yang dipakai di transkrip.</small>
        </div>
        @if ($url_rekap)
            <a href="{{ $url_rekap }}" class="btn btn-sm btn-outline-secondary ms-auto no-print">
                <i class="bx bx-arrow-back me-1"></i>Kembali ke Rekap Angkatan
            </a>
        @else
            <a href="{{ route('wisuda.rekap.index') }}" class="btn btn-sm btn-outline-primary ms-auto no-print">
                <i class="bx bx-group me-1"></i>Cek Per Angkatan
            </a>
        @endif
    </div>

    {{-- Form pencarian NPM --}}
    <div class="card no-print">
        <div class="card-body">
            <form action="{{ route('wisuda.transkrip.index') }}" method="get" class="row g-2 align-items-end" id="form-cek">
                <div class="col-md-4 col-lg-3">
                    <label for="npm" class="form-label">Nomor Pokok Mahasiswa (NPM)</label>
                    <input type="text" name="npm" id="npm" value="{{ $npm }}" class="form-control"
                        placeholder="Contoh: 22103157201010" autocomplete="off" required {{ $data ? '' : 'autofocus' }}>
                </div>
                @if ($data)
                    @if (!empty($rekap))
                        <input type="hidden" name="rekap" value="{{ $rekap }}">
                    @endif
                    <div class="col-6 col-md-2">
                        <label for="angkatan" class="form-label">Tahun Angkatan</label>
                        <select name="angkatan" id="angkatan" class="form-select">
                            @foreach ($pilihan_angkatan as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($nilai == $angkatan)>
                                    {{ $label }}{{ $nilai == $angkatan_mahasiswa ? ' (data mhs)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label for="kurikulum" class="form-label">Kurikulum (Neo Feeder)</label>
                        @if ($daftar_kurikulum->isEmpty() && $kurikulum_dipilih)
                            <input type="hidden" name="kurikulum" value="{{ $kurikulum_dipilih }}">
                        @endif
                        <select name="kurikulum" id="kurikulum" class="form-select" @disabled($daftar_kurikulum->isEmpty())>
                            <option value="">
                                Otomatis sesuai angkatan{{ $kurikulum_otomatis ? ' — ' . $kurikulum_otomatis['nama_kurikulum'] : '' }}
                            </option>
                            @foreach ($daftar_kurikulum as $k)
                                <option value="{{ $k['id_kurikulum'] }}" @selected($k['id_kurikulum'] === $kurikulum_dipilih)>
                                    {{ $k['nama_kurikulum'] }} (berlaku {{ $k['semester_mulai_berlaku'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary"><i class="bx bx-search-alt me-1"></i>Cek Data</button>
                </div>
                @if ($data)
                    <div class="col-auto">
                        <a href="{{ route('wisuda.transkrip.index', array_filter(['npm' => $npm, 'angkatan' => $angkatan, 'kurikulum' => $kurikulum_dipilih, 'rekap' => $rekap, 'segarkan' => 1])) }}"
                            class="btn btn-outline-secondary" title="Ambil ulang data kurikulum terbaru dari Neo Feeder">
                            <i class="bx bx-refresh me-1"></i>Muat Ulang Feeder
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @if ($tidak_ditemukan)
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bx bx-user-x text-danger" style="font-size: 3rem"></i>
                <h6 class="mt-2 mb-1">Mahasiswa dengan NPM <code>{{ $npm }}</code> tidak ditemukan</h6>
                <p class="text-muted small mb-0">Periksa kembali NPM yang dimasukkan, atau cari lewat menu
                    <a href="{{ route('mahasiswa.index') }}">Data Mahasiswa</a>.</p>
            </div>
        </div>
    @elseif ($data)
        @php
            $mhs = $data['mahasiswa'];
            $r = $data['ringkasan'];
            $grupTerakhir = null;
        @endphp

        {{-- Area data: dimuat ulang lewat AJAX setelah tambah/ubah/hapus tanpa reload halaman --}}
        <div id="area-data">
        {{-- Identitas + ringkasan --}}
        <div class="card">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-5">
                        <h5 class="mb-1">{{ $mhs->nama_mahasiswa }}</h5>
                        <div class="mb-1"><span class="badge bg-light text-dark border font-monospace">{{ $mhs->npm }}</span></div>
                        <div class="small text-muted">
                            {{ $mhs->prodi->nama_program_studi_idn ?? $mhs->kode_program_studi }}
                            · Angkatan {{ substr((string) $mhs->tahun_angkatan, 0, 4) }}
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="row g-2 text-center">
                            <div class="col-6 col-md-3">
                                <div class="border rounded-3 p-2 h-100 border-primary">
                                    <div class="small text-muted">IPK</div>
                                    <div class="kartu-angka text-primary" id="r-ipk">{{ number_format($r['ipk'], 2) }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border rounded-3 p-2 h-100">
                                    <div class="small text-muted">Total SKS</div>
                                    <div class="kartu-angka" id="r-sks">{{ $r['total_sks'] }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border rounded-3 p-2 h-100">
                                    <div class="small text-muted">Matakuliah</div>
                                    <div class="kartu-angka" id="r-mk">{{ $r['jumlah_mk'] }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border rounded-3 p-2 h-100 {{ $r['jumlah_dobel'] ? 'border-danger' : '' }}">
                                    <div class="small text-muted">MK Dobel</div>
                                    <div class="kartu-angka {{ $r['jumlah_dobel'] ? 'text-danger' : '' }}">{{ $r['jumlah_dobel'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kurikulum yang dipakai untuk pencocokan --}}
        @if ($galat_feeder)
            <div class="alert alert-warning d-flex align-items-start no-print">
                <i class="bx bx-wifi-off fs-4 me-2"></i>
                <div class="flex-grow-1">{{ $galat_feeder }}</div>
                <a href="{{ route('wisuda.transkrip.index', array_filter(['npm' => $npm, 'angkatan' => $angkatan, 'kurikulum' => $kurikulum_dipilih, 'rekap' => $rekap, 'segarkan' => 1])) }}"
                    class="btn btn-sm btn-outline-warning ms-2 text-nowrap"><i class="bx bx-refresh me-1"></i>Coba Lagi</a>
            </div>
        @elseif (!empty($data['kurikulum']))
            @php
                $k = $data['kurikulum'];
                $sksCukup = $r['total_sks'] >= $k['jumlah_sks_lulus'];
            @endphp
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-5">
                            <div class="small text-muted">Kurikulum yang dipakai
                                {{ $kurikulum_dipilih ? '(dipilih manual)' : '(otomatis, angkatan ' . substr($angkatan, 0, 4) . ')' }}</div>
                            <h6 class="mb-0">{{ $k['nama_kurikulum'] }}</h6>
                            <div class="small text-muted">Berlaku mulai {{ $k['semester_mulai_berlaku'] }} · sumber: Neo Feeder</div>
                        </div>
                        <div class="col-lg-7">
                            <div class="row g-2 text-center">
                                <div class="col-6 col-md-3">
                                    <div class="border rounded-3 p-2 h-100 {{ $sksCukup ? 'border-success' : 'border-danger' }}">
                                        <div class="small text-muted">SKS / Syarat Lulus</div>
                                        <div class="kartu-angka {{ $sksCukup ? 'text-success' : 'text-danger' }}">
                                            {{ $r['total_sks'] }}<span class="fs-6 text-muted">/{{ $k['jumlah_sks_lulus'] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="border rounded-3 p-2 h-100 {{ $r['mk_kurang'] ? 'border-danger' : 'border-success' }}">
                                        <div class="small text-muted">MK Kurang (belum ada KRS)</div>
                                        <div class="kartu-angka {{ $r['mk_kurang'] ? 'text-danger' : 'text-success' }}">{{ $r['mk_kurang'] }}</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="border rounded-3 p-2 h-100 {{ $r['luar_kurikulum'] ? 'border-warning' : '' }}">
                                        <div class="small text-muted">Di Luar Kurikulum</div>
                                        <div class="kartu-angka {{ $r['luar_kurikulum'] ? 'text-warning' : '' }}">{{ $r['luar_kurikulum'] }}</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="border rounded-3 p-2 h-100 {{ $r['sks_beda'] ? 'border-warning' : '' }}">
                                        <div class="small text-muted">SKS Berbeda</div>
                                        <div class="kartu-angka {{ $r['sks_beda'] ? 'text-warning' : '' }}">{{ $r['sks_beda'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Peringatan --}}
        @if ($r['jumlah_dobel'] > 0)
            <div class="alert alert-danger d-flex align-items-start no-print" id="alert-dobel">
                <i class="bx bx-error-circle fs-4 me-2"></i>
                <div class="flex-grow-1">
                    Ditemukan <strong>{{ $r['jumlah_dobel'] }}</strong> matakuliah yang diambil lebih dari sekali (baris merah).
                    <span id="info-belum-dipilih" class="{{ $r['dobel_belum_dipilih'] ? '' : 'd-none' }}">
                        <strong id="r-belum-dipilih">{{ $r['dobel_belum_dipilih'] }}</strong> di antaranya belum dipilih —
                        sementara sistem memakai nilai tertinggi.
                    </span>
                    Klik <strong>Ambil</strong> pada baris yang ingin dipakai di transkrip.
                </div>
                <form action="{{ route('wisuda.transkrip.reset') }}" method="post" class="ms-2 form-ajax"
                    data-konfirmasi="Kembalikan semua pilihan matakuliah dobel ke pilihan otomatis (nilai tertinggi)?">
                    @csrf
                    <input type="hidden" name="npm" value="{{ $mhs->npm }}">
                    <input type="hidden" name="angkatan" value="{{ $angkatan }}">
                    <input type="hidden" name="kurikulum" value="{{ $kurikulum_dipilih }}">
                    <input type="hidden" name="rekap" value="{{ $rekap }}">
                    <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">
                        <i class="bx bx-reset me-1"></i>Reset Pilihan
                    </button>
                </form>
            </div>
        @endif
        @if ($r['tanpa_nilai'] > 0 || $r['tidak_lulus'] > 0)
            <div class="alert alert-warning d-flex align-items-start no-print">
                <i class="bx bx-info-circle fs-4 me-2"></i>
                <div>
                    @if ($r['tanpa_nilai'] > 0)
                        <div><strong>{{ $r['tanpa_nilai'] }}</strong> matakuliah belum memiliki nilai dan tidak dihitung ke IPK.</div>
                    @endif
                    @if ($r['tidak_lulus'] > 0)
                        <div><strong>{{ $r['tidak_lulus'] }}</strong> matakuliah bernilai tidak lulus menurut skala nilai prodi.</div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Transkrip --}}
        <div class="card">
            <div class="card-header d-flex align-items-center flex-wrap gap-2 py-3">
                <h6 class="mb-0 me-auto"><i class="bx bx-list-ul me-1"></i>Transkrip Nilai</h6>
                <div class="d-flex flex-wrap gap-2 no-print">
                    <label for="filter-transkrip" class="visually-hidden">Filter</label>
                    <select id="filter-transkrip" class="form-select form-select-sm" style="width: auto">
                        <option value="semua">Semua matakuliah ({{ $data['baris']->count() }})</option>
                        <option value="dobel">Hanya dobel ({{ $data['baris']->whereNotNull('grup')->count() }})</option>
                        @if (!empty($data['kurikulum']))
                            <option value="luar-kurikulum">Di luar kurikulum ({{ $data['baris']->where('di_kurikulum', false)->count() }})</option>
                        @endif
                        <option value="tanpa-nilai">Belum ada nilai ({{ $data['baris']->where('punya_nilai', false)->count() }})</option>
                        <option value="tidak-lulus">Tidak lulus ({{ $data['baris']->where('tidak_lulus', true)->count() }})</option>
                    </select>
                    <label for="cari-transkrip" class="visually-hidden">Cari matakuliah</label>
                    <input type="search" id="cari-transkrip" class="form-control form-control-sm" style="width: 220px"
                        placeholder="Cari kode / nama matakuliah">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="tombol-banding-pddikti"
                        title="Ambil nilai mahasiswa ini dari PDDikti (Neo Feeder) lalu bandingkan per matakuliah">
                        <i class="bx bx-cloud-download me-1"></i>Bandingkan PDDikti
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i>Cetak
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" id="tabel-transkrip">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 45px">No</th>
                            <th>Matakuliah</th>
                            <th>Periode</th>
                            <th class="text-center">SKS</th>
                            <th class="text-center">Angka</th>
                            <th class="text-center">Huruf</th>
                            <th class="text-center">Bobot</th>
                            <th class="text-center">Mutu</th>
                            <th class="text-center no-print" style="width: 150px">Status</th>
                            <th class="text-center no-print" style="width: 120px">PDDikti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['baris'] as $i => $b)
                            @php
                                $dobel = $b['grup'] !== null;
                                $awalGrup = $dobel && $b['grup'] !== $grupTerakhir;
                                $grupTerakhir = $b['grup'];
                                $grupInfo = $dobel ? $data['grup'][$b['grup']] : null;
                                $adaKurikulum = !empty($data['kurikulum']);
                                $mkHilang = !empty($b['mk_hilang']);
                                $luarKurikulum = $adaKurikulum && !$b['di_kurikulum'] && !$mkHilang;
                                // Selain dobel, KRS rusak & MK di luar kurikulum juga boleh dihapus
                                $bisaDihapus = !$dobel && ($mkHilang || $luarKurikulum);
                                $kategori = trim(implode(' ', array_filter([
                                    $dobel ? 'dobel' : null,
                                    $luarKurikulum || $mkHilang ? 'luar-kurikulum' : null,
                                    !$b['punya_nilai'] ? 'tanpa-nilai' : null,
                                    $b['tidak_lulus'] ? 'tidak-lulus' : null,
                                ])));
                            @endphp
                            <tr data-krs="{{ $b['krs_id'] }}" data-grup="{{ $b['grup'] }}" data-kategori="{{ $kategori }}"
                                data-cari="{{ strtolower($b['kode_mk'] . ' ' . $b['nama_mk']) }}"
                                class="{{ $dobel ? 'baris-dobel' : '' }} {{ $awalGrup ? 'awal-grup' : '' }} {{ $b['diambil'] ? '' : 'tidak-diambil' }}">
                                <td class="text-center text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <div class="fw-semibold nama-mk">{{ $b['nama_mk'] }}</div>
                                    <span class="badge bg-light text-dark border font-monospace">{{ $b['kode_mk'] }}</span>
                                    @if ($dobel)
                                        <span class="badge text-danger bg-white border border-danger ms-1">
                                            <i class="bx bx-copy"></i> Dobel {{ count($grupInfo['krs_ids']) }}x
                                        </span>
                                    @endif
                                    @if ($mkHilang)
                                        <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle ms-1"
                                            title="Jadwal/matakuliah yang dirujuk KRS ini sudah tidak ada di SIADE (KRS #{{ $b['krs_id'] }})">
                                            <i class="bx bx-unlink"></i> KRS rusak
                                        </span>
                                    @endif
                                    @if ($luarKurikulum)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1"
                                            title="Kode matakuliah ini tidak ada di kurikulum {{ $data['kurikulum']['nama_kurikulum'] }}">
                                            <i class="bx bx-error"></i> Di luar kurikulum
                                        </span>
                                    @endif
                                </td>
                                <td class="small text-nowrap">{{ $b['nama_periode'] }}</td>
                                <td class="text-center">
                                    {{ $b['sks'] }}
                                    @if (!empty($b['sks_beda']))
                                        @php $sksFeeder = rtrim(rtrim(number_format($b['sks_kurikulum'], 2), '0'), '.'); @endphp
                                        <i class="bx bx-error-circle text-warning tooltip-sks" tabindex="0" role="img" style="cursor: help"
                                            data-bs-toggle="tooltip" data-bs-html="true"
                                            data-bs-title="<div class='text-start'><strong>SKS berbeda dengan kurikulum Neo Feeder</strong><br>SIADE: {{ $b['sks'] }} SKS · Feeder: {{ $sksFeeder }} SKS<br><span class='opacity-75'>Total SKS &amp; IPK dihitung memakai SKS SIADE. Samakan master matakuliah SIADE atau kurikulum Feeder.</span></div>"
                                            aria-label="SKS berbeda: SIADE {{ $b['sks'] }} SKS, kurikulum Neo Feeder {{ $sksFeeder }} SKS"></i>
                                    @endif
                                </td>
                                <td class="text-center">{{ $b['nilai_angka'] !== null ? number_format($b['nilai_angka'], 2) : '-' }}</td>
                                <td class="text-center fw-semibold {{ $b['tidak_lulus'] ? 'text-danger' : '' }}">
                                    @if ($b['punya_nilai'])
                                        {{ $b['nilai_huruf'] }}
                                        @if ($b['tidak_lulus'] && $b['diambil'])
                                            <button type="button" class="btn btn-sm btn-outline-warning py-0 px-2 ms-1 tombol-nilai no-print"
                                                data-krs="{{ $b['krs_id'] }}" data-nama="{{ $b['nama_mk'] }}" data-kode="{{ $b['kode_mk'] }}"
                                                data-periode="{{ $b['nama_periode'] }}" data-lama="{{ $b['nilai_huruf'] }}"
                                                data-lama-angka="{{ $b['nilai_angka'] !== null ? number_format($b['nilai_angka'], 2) : '' }}"
                                                title="Perbaiki nilai tidak lulus">
                                                <i class="bx bx-revision"></i> Perbaiki
                                            </button>
                                        @endif
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 tombol-nilai no-print"
                                            data-krs="{{ $b['krs_id'] }}" data-nama="{{ $b['nama_mk'] }}" data-kode="{{ $b['kode_mk'] }}"
                                            data-periode="{{ $b['nama_periode'] }}">
                                            <i class="bx bx-edit-alt"></i> Isi Nilai
                                        </button>
                                        <span class="d-none d-print-inline">-</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $b['nilai_bobot'] !== null ? number_format($b['nilai_bobot'], 2) : '-' }}</td>
                                <td class="text-center">{{ $b['mutu'] !== null ? number_format($b['mutu'], 2) : '-' }}</td>
                                <td class="text-center no-print">
                                    @if ($dobel)
                                        <div class="form-check d-inline-flex align-items-center gap-1 mb-0">
                                            <input class="form-check-input pilih-dobel" type="radio"
                                                name="grup-{{ $b['grup'] }}" id="pilih-{{ $b['krs_id'] }}"
                                                value="{{ $b['krs_id'] }}" {{ $b['diambil'] ? 'checked' : '' }}>
                                            <label class="form-check-label small label-pilih" for="pilih-{{ $b['krs_id'] }}">Ambil</label>
                                            <button type="button" data-jenis="dobel" class="btn btn-sm btn-link text-danger p-0 ms-2 tombol-hapus"
                                                title="Hapus matakuliah dobel ini" aria-label="Hapus {{ $b['nama_mk'] }} periode {{ $b['nama_periode'] }}"
                                                data-krs="{{ $b['krs_id'] }}" data-nama="{{ $b['nama_mk'] }}" data-kode="{{ $b['kode_mk'] }}"
                                                data-periode="{{ $b['nama_periode'] }}" data-nilai="{{ $b['nilai_huruf'] ?: '-' }}">
                                                <i class="bx bx-trash fs-5"></i>
                                            </button>
                                        </div>
                                        <div class="small status-dobel">
                                            @if (!$b['diambil'])
                                                <span class="text-muted">Tidak dihitung</span>
                                            @elseif ($grupInfo['sudah_dipilih'])
                                                <span class="text-success badge-titik">Dipilih</span>
                                            @else
                                                <span class="text-danger badge-titik">Otomatis</span>
                                            @endif
                                        </div>
                                    @elseif (!$b['punya_nilai'])
                                        <span class="badge bg-warning-subtle text-warning-emphasis badge-titik">Belum ada nilai</span>
                                    @elseif ($b['tidak_lulus'])
                                        <span class="badge bg-danger-subtle text-danger-emphasis badge-titik">Tidak lulus</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success-emphasis badge-titik">Dihitung</span>
                                    @endif
                                    @if ($bisaDihapus)
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1 tombol-hapus"
                                            data-jenis="{{ $mkHilang ? 'rusak' : 'luar' }}"
                                            title="{{ $mkHilang ? 'Hapus KRS rusak ini' : 'Hapus matakuliah di luar kurikulum ini' }}"
                                            aria-label="Hapus {{ $b['nama_mk'] }} periode {{ $b['nama_periode'] }}"
                                            data-krs="{{ $b['krs_id'] }}" data-nama="{{ $mkHilang ? 'Matakuliah tidak ditemukan (KRS #' . $b['krs_id'] . ')' : $b['nama_mk'] }}"
                                            data-kode="{{ $b['kode_mk'] }}" data-periode="{{ $b['nama_periode'] }}" data-nilai="{{ $b['nilai_huruf'] ?: '-' }}">
                                            <i class="bx bx-trash fs-5"></i>
                                        </button>
                                    @endif
                                </td>
                                <td class="text-center no-print sel-pddikti" data-kode="{{ strtoupper(trim($b['kode_mk'])) }}"
                                    data-periode="{{ $b['periode'] }}" data-huruf="{{ $b['nilai_huruf'] }}">
                                    <button type="button" class="btn btn-sm btn-link p-0 tombol-pddikti" data-krs="{{ $b['krs_id'] }}"
                                        data-kode="{{ strtoupper(trim($b['kode_mk'])) }}" data-nama="{{ $b['nama_mk'] }}"
                                        data-periode="{{ $b['periode'] }}" data-nama-periode="{{ $b['nama_periode'] }}"
                                        data-huruf="{{ $b['nilai_huruf'] }}" data-angka="{{ $b['nilai_angka'] !== null ? number_format($b['nilai_angka'], 2) : '' }}">
                                        <i class="bx bx-cloud-download"></i> Ambil
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bx bx-folder-open fs-1 d-block mb-1"></i>
                                    Mahasiswa ini belum memiliki data KRS/nilai.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="baris-kosong-filter" class="d-none">
                            <td colspan="10" class="text-center py-4 text-muted">
                                Tidak ada matakuliah yang cocok dengan filter/pencarian.
                                <a href="javascript:void(0)" id="reset-filter">Tampilkan semua</a>
                            </td>
                        </tr>
                    </tbody>
                    @if ($data['baris']->isNotEmpty())
                        <tfoot class="table-light fw-semibold">
                            <tr>
                                <td colspan="3" class="text-end">Total (yang dihitung)</td>
                                <td class="text-center" id="f-sks">{{ $r['total_sks'] }}</td>
                                <td colspan="3" class="text-end">Total Mutu</td>
                                <td class="text-center" id="f-mutu">{{ number_format($r['total_mutu'], 2) }}</td>
                                <td class="no-print" colspan="2"></td>
                            </tr>
                            <tr>
                                <td colspan="7" class="text-end">Indeks Prestasi Kumulatif (IPK)</td>
                                <td class="text-center text-primary" id="f-ipk">{{ number_format($r['ipk'], 2) }}</td>
                                <td class="no-print" colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            <div class="card-footer small text-muted no-print" id="info-jumlah-baris">
                Menampilkan {{ $data['baris']->count() }} dari {{ $data['baris']->count() }} data
            </div>
        </div>

        {{-- MK kurikulum yang belum terpenuhi --}}
        @if (!empty($data['kurikulum']))
            @php $belum = $data['mk_belum_terpenuhi']; @endphp
            <div class="card">
                <div class="card-header py-3 d-flex align-items-center flex-wrap gap-2">
                    <h6 class="mb-0 me-auto"><i class="bx bx-list-check me-1"></i>Matakuliah Kurikulum yang Belum Terpenuhi</h6>
                    <span class="small text-muted">Dicocokkan berdasarkan kode matakuliah dengan {{ $data['kurikulum']['nama_kurikulum'] }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 45px">No</th>
                                <th>Matakuliah</th>
                                <th class="text-center">Semester</th>
                                <th class="text-center">SKS</th>
                                <th class="text-center">Jenis</th>
                                <th class="text-center" style="width: 150px">Status</th>
                                <th class="text-end no-print"><span class="visually-hidden">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($belum as $i => $mk)
                                <tr>
                                    <td class="text-center text-muted">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $mk['nama_mk'] }}</div>
                                        <span class="badge bg-light text-dark border font-monospace">{{ $mk['kode_mk'] }}</span>
                                    </td>
                                    <td class="text-center">{{ $mk['semester'] }}</td>
                                    <td class="text-center">{{ rtrim(rtrim(number_format($mk['sks'], 2), '0'), '.') }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $mk['wajib'] ? 'bg-primary-subtle text-primary-emphasis' : 'bg-secondary-subtle text-secondary-emphasis' }}">
                                            {{ $mk['wajib'] ? 'Wajib' : 'Pilihan' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($mk['status'] === 'belum_diambil')
                                            <span class="badge bg-danger-subtle text-danger-emphasis badge-titik">Belum diambil</span>
                                        @elseif ($mk['status'] === 'tidak_lulus')
                                            <span class="badge bg-danger-subtle text-danger-emphasis badge-titik">Tidak lulus</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning-emphasis badge-titik">Belum ada nilai</span>
                                        @endif
                                    </td>
                                    <td class="text-end no-print text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary tombol-pddikti me-1"
                                            data-krs="{{ $mk['krs_id'] }}" data-kode="{{ $mk['kode_mk'] }}" data-nama="{{ $mk['nama_mk'] }}"
                                            data-periode="" data-nama-periode="" data-huruf="{{ $mk['nilai_lama'] ?? '' }}" data-angka=""
                                            title="Ambil nilai matakuliah ini dari PDDikti">
                                            <i class="bx bx-cloud-download"></i> PDDikti
                                        </button>
                                        @if ($mk['status'] === 'belum_diambil')
                                            @php $mkLokal = $mk_lokal->get($mk['kode_mk']); @endphp
                                            @if ($mkLokal)
                                                <button type="button" class="btn btn-sm btn-primary tombol-krs"
                                                    data-kode="{{ $mk['kode_mk'] }}" data-nama="{{ $mkLokal->nama_mata_kuliah_idn }}"
                                                    data-sks="{{ $mkLokal->sks_mata_kuliah }}" data-semester="{{ $mk['semester'] }}">
                                                    <i class="bx bx-plus"></i> Tambah KRS
                                                </button>
                                            @else
                                                <span class="small text-muted" title="Tambahkan dulu matakuliah ini di menu Master Data → Kurikulum">
                                                    <i class="bx bx-info-circle"></i> Belum ada di master SIADE
                                                </span>
                                            @endif
                                        @elseif ($mk['status'] === 'belum_ada_nilai' && $mk['krs_id'])
                                            <button type="button" class="btn btn-sm btn-outline-danger tombol-nilai"
                                                data-krs="{{ $mk['krs_id'] }}" data-nama="{{ $mk['nama_mk'] }}" data-kode="{{ $mk['kode_mk'] }}"
                                                data-periode="">
                                                <i class="bx bx-edit-alt"></i> Isi Nilai
                                            </button>
                                        @elseif ($mk['status'] === 'tidak_lulus' && $mk['krs_id'])
                                            <button type="button" class="btn btn-sm btn-outline-warning tombol-nilai"
                                                data-krs="{{ $mk['krs_id'] }}" data-nama="{{ $mk['nama_mk'] }}" data-kode="{{ $mk['kode_mk'] }}"
                                                data-periode="" data-lama="{{ $mk['nilai_lama'] }}">
                                                <i class="bx bx-revision"></i> Perbaiki Nilai
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-success">
                                        <i class="bx bx-check-circle fs-3 d-block mb-1"></i>
                                        Semua matakuliah di kurikulum ini sudah diambil dan lulus.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($belum->isNotEmpty())
                    <div class="card-footer small text-muted">
                        {{ $belum->where('wajib', true)->count() }} wajib · {{ $belum->where('wajib', false)->count() }} pilihan.
                        Matakuliah pilihan tidak harus diambil semua. MK yang kodenya berbeda di transkrip (mis. dari kurikulum lama) akan muncul di sini dan sebagai "Di luar kurikulum" di atas.
                    </div>
                @endif
            </div>
        @endif
        </div>{{-- /#area-data --}}

        {{-- Modal isi nilai (untuk KRS yang nilainya kosong) --}}
        <div class="modal fade" id="modal-nilai" tabindex="-1" aria-labelledby="judul-modal-nilai" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('wisuda.transkrip.nilai') }}" method="post" class="modal-content form-ajax" novalidate>
                    @csrf
                    <input type="hidden" name="npm" value="{{ $mhs->npm }}">
                    <input type="hidden" name="angkatan" value="{{ $angkatan }}">
                    <input type="hidden" name="kurikulum" value="{{ $kurikulum_dipilih }}">
                    <input type="hidden" name="rekap" value="{{ $rekap }}">
                    <input type="hidden" name="krs_id" id="nilai-krs-id">
                    <div class="modal-header">
                        <h6 class="modal-title" id="judul-modal-nilai"><i class="bx bx-edit-alt me-1"></i>Isi Nilai</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="fw-semibold" id="nilai-nama"></div>
                            <span class="badge bg-light text-dark border font-monospace" id="nilai-kode"></span>
                            <span class="small text-muted ms-1" id="nilai-periode"></span>
                        </div>
                        <div class="alert alert-warning small py-2 d-none" id="nilai-info-lama">
                            Nilai sekarang: <strong id="nilai-lama"></strong> (tidak lulus). Nilai perbaikan akan menggantikan nilai ini di KRS;
                            nilai lama dicatat di riwayat perbaikan.
                        </div>
                        <label for="nilai-angka" class="form-label">Nilai Angka (0–100) <span class="text-danger">*</span></label>
                        <input type="number" name="nilai_angka" id="nilai-angka" class="form-control input-nilai" min="0" max="100" step="0.01" required
                            data-pratinjau="nilai-pratinjau">
                        <div class="form-text" id="nilai-pratinjau">Huruf dan bobot dihitung otomatis dari skala nilai prodi.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Simpan Nilai</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal tambah KRS untuk MK kurikulum yang belum diambil --}}
        <div class="modal fade" id="modal-krs" tabindex="-1" aria-labelledby="judul-modal-krs" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('wisuda.transkrip.krs') }}" method="post" class="modal-content form-ajax" novalidate>
                    @csrf
                    <input type="hidden" name="npm" value="{{ $mhs->npm }}">
                    <input type="hidden" name="angkatan" value="{{ $angkatan }}">
                    <input type="hidden" name="kurikulum" value="{{ $kurikulum_dipilih }}">
                    <input type="hidden" name="rekap" value="{{ $rekap }}">
                    <input type="hidden" name="kode_mk" id="krs-kode-mk">
                    <div class="modal-header">
                        <h6 class="modal-title" id="judul-modal-krs"><i class="bx bx-plus-circle me-1"></i>Tambah KRS</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="fw-semibold" id="krs-nama"></div>
                            <span class="badge bg-light text-dark border font-monospace" id="krs-kode"></span>
                            <span class="small text-muted ms-1" id="krs-info"></span>
                        </div>
                        <div class="mb-3">
                            <label for="krs-periode" class="form-label">Periode / Tahun Akademik <span class="text-danger">*</span></label>
                            <select name="periode" id="krs-periode" class="form-select" required>
                                <option value="">Pilih periode</option>
                                @foreach ($pilihan_periode as $kode => $nama)
                                    <option value="{{ $kode }}">{{ $nama }} ({{ $kode }})</option>
                                @endforeach
                            </select>
                        </div>
                        <label for="krs-nilai" class="form-label">Nilai Angka (opsional)</label>
                        <input type="number" name="nilai_angka" id="krs-nilai" class="form-control input-nilai" min="0" max="100" step="0.01"
                            data-pratinjau="krs-pratinjau" placeholder="Kosongkan jika nilai belum ada">
                        <div class="form-text" id="krs-pratinjau">Huruf dan bobot dihitung otomatis dari skala nilai prodi.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Tambah KRS</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal ambil nilai dari PDDikti per matakuliah --}}
        <div class="modal fade" id="modal-pddikti" tabindex="-1" aria-labelledby="judul-modal-pddikti" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <form action="{{ route('wisuda.transkrip.pddikti.simpan') }}" method="post" class="modal-content form-ajax" novalidate>
                    @csrf
                    <input type="hidden" name="npm" value="{{ $mhs->npm }}">
                    <input type="hidden" name="angkatan" value="{{ $angkatan }}">
                    <input type="hidden" name="kode_mk" id="pddikti-kode-mk">
                    <input type="hidden" name="krs_id" id="pddikti-krs-id">
                    <div class="modal-header">
                        <div>
                            <h6 class="modal-title mb-0" id="judul-modal-pddikti"><i class="bx bx-cloud-download me-1"></i>Ambil Nilai dari PDDikti</h6>
                            <div class="small text-muted"><span id="pddikti-nama" class="fw-semibold"></span>
                                <span id="pddikti-kode" class="badge bg-light text-dark border font-monospace"></span></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="small mb-2">Nilai di SIADE sekarang: <strong id="pddikti-siade"></strong></div>
                        <div id="pddikti-isi"></div>
                        <div class="alert alert-info small py-2 mt-3 mb-0">
                            <i class="bx bx-info-circle me-1"></i>Nilai angka PDDikti disalin ke KRS SIADE; huruf & bobot dihitung dari skala nilai prodi.
                            Nilai lama dicatat di riwayat perbaikan. Transkrip & IPK tetap dihitung dari SIADE.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link me-auto" id="pddikti-segarkan"><i class="bx bx-refresh me-1"></i>Ambil ulang dari PDDikti</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="pddikti-simpan"><i class="bx bx-save me-1"></i>Salin ke SIADE</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal konfirmasi hapus matakuliah dobel --}}
        <div class="modal fade" id="modal-hapus" tabindex="-1" aria-labelledby="judul-modal-hapus" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('wisuda.transkrip.hapus') }}" method="post" class="modal-content form-ajax" id="form-hapus">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="npm" value="{{ $mhs->npm }}">
                    <input type="hidden" name="angkatan" value="{{ $angkatan }}">
                    <input type="hidden" name="kurikulum" value="{{ $kurikulum_dipilih }}">
                    <input type="hidden" name="rekap" value="{{ $rekap }}">
                    <input type="hidden" name="krs_id" id="hapus-krs-id">
                    <div class="modal-header">
                        <h6 class="modal-title" id="judul-modal-hapus"><i class="bx bx-trash text-danger me-1"></i><span id="hapus-judul">Hapus Matakuliah Dobel</span></h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Matakuliah berikut akan <strong>dihapus dari KRS</strong> mahasiswa:</p>
                        <p class="small text-muted mb-2" id="hapus-alasan"></p>
                        <div class="alert alert-danger small py-2 d-none" id="hapus-peringatan" role="alert">
                            <i class="bx bx-error me-1"></i><strong>Matakuliah ini sudah bernilai dan ikut dihitung ke IPK.</strong>
                            Pastikan memang bukan matakuliah wajib yang kodenya berbeda dengan kurikulum Feeder sebelum menghapus.
                        </div>
                        <table class="table table-sm table-bordered mb-3">
                            <tr><th class="w-25">Matakuliah</th><td><span id="hapus-nama" class="fw-semibold"></span> <span id="hapus-kode" class="badge bg-light text-dark border font-monospace"></span></td></tr>
                            <tr><th>Periode</th><td id="hapus-periode"></td></tr>
                            <tr><th>Nilai</th><td id="hapus-nilai"></td></tr>
                        </table>
                        <div class="alert alert-warning small mb-0">
                            <i class="bx bx-info-circle me-1"></i>Data KRS ini dihapus permanen dari KRS/KHS mahasiswa.
                            Salinannya disimpan di log sehingga masih bisa dipulihkan oleh admin.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger"><i class="bx bx-trash me-1"></i>Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@if ($data)
    @push('js')
        <script>
            (function() {
                const npm = @json($data['mahasiswa']->npm);
                const urlPilih = @json(route('wisuda.transkrip.pilih'));
                const csrf = @json(csrf_token());
                const skala = @json($skala_nilai);
                const area = document.getElementById('area-data');
                const $id = (id) => document.getElementById(id);

                function toast(tipe, pesan) {
                    Lobibox.notify(tipe, {
                        pauseDelayOnHover: true, size: 'mini', rounded: true, delay: 3000,
                        icon: tipe === 'success' ? 'bx bx-check-circle' : (tipe === 'warning' ? 'bx bx-error' : 'bx bx-x-circle'),
                        delayIndicator: false, continueDelayOnInactiveTab: false,
                        position: 'bottom right', msg: pesan, sound: false,
                    });
                }

                // Pesan galat sesuai status HTTP agar penyebabnya jelas (akses, sesi, server)
                function pesanGalat(resp, res, aksi) {
                    if (res && res.message && !res.errors) return res.message;
                    if (resp.status === 403) return `✕ Anda tidak memiliki akses untuk ${aksi}. Minta admin menambahkan permission di menu Permissions.`;
                    if (resp.status === 419) return '✕ Sesi Anda sudah habis. Muat ulang halaman lalu coba lagi.';
                    if (resp.status === 404) return '✕ Alamat tidak ditemukan (route belum ada di server). Pastikan file terbaru sudah diunggah.';
                    if (resp.status >= 500) return `✕ Terjadi kesalahan di server (HTTP ${resp.status}). Periksa storage/logs/laravel.log.`;
                    return `✕ Gagal ${aksi}. Coba lagi. (HTTP ${resp.status})`;
                }

                // ---- Filter & pencarian (delegasi, karena isi area diganti saat dimuat ulang) ----
                function terapkanFilter() {
                    const filter = $id('filter-transkrip');
                    const cari = $id('cari-transkrip');
                    if (!filter) return;
                    const f = filter.value;
                    const q = cari.value.trim().toLowerCase();
                    const baris = area.querySelectorAll('#tabel-transkrip tbody tr[data-krs]');
                    let tampil = 0;
                    baris.forEach(tr => {
                        const lihat = (f === 'semua' || tr.dataset.kategori.split(' ').includes(f)) && (!q || tr.dataset.cari.includes(q));
                        tr.classList.toggle('d-none', !lihat);
                        if (lihat) tampil++;
                    });
                    $id('baris-kosong-filter').classList.toggle('d-none', tampil > 0 || baris.length === 0);
                    $id('info-jumlah-baris').textContent = `Menampilkan ${tampil} dari ${baris.length} data`;
                }
                area.addEventListener('change', (e) => { if (e.target.id === 'filter-transkrip') terapkanFilter(); });
                area.addEventListener('input', (e) => { if (e.target.id === 'cari-transkrip') terapkanFilter(); });
                area.addEventListener('click', (e) => {
                    if (e.target.id !== 'reset-filter') return;
                    $id('filter-transkrip').value = 'semua';
                    $id('cari-transkrip').value = '';
                    terapkanFilter();
                });

                // ---- Muat ulang area data tanpa reload halaman ----
                async function muatUlang() {
                    const filterLama = $id('filter-transkrip')?.value;
                    const cariLama = $id('cari-transkrip')?.value;
                    const url = new URL(window.location.href);
                    url.searchParams.delete('segarkan'); // jangan ambil ulang Feeder setiap simpan
                    area.classList.add('memuat');
                    try {
                        const resp = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        if (!resp.ok) throw new Error();
                        const doc = new DOMParser().parseFromString(await resp.text(), 'text/html');
                        const baru = doc.getElementById('area-data');
                        if (!baru) throw new Error();
                        area.innerHTML = baru.innerHTML;
                        const filter = $id('filter-transkrip');
                        if (filter && [...filter.options].some(o => o.value === filterLama)) filter.value = filterLama;
                        if ($id('cari-transkrip')) $id('cari-transkrip').value = cariLama || '';
                        terapkanFilter();
                        if (typeof tandaiPddikti === 'function') tandaiPddikti();
                    } catch (err) {
                        toast('error', '✕ Gagal memuat data. Periksa koneksi Anda.');
                    } finally {
                        area.classList.remove('memuat');
                    }
                }

                // ---- Kirim form (isi/perbaiki nilai, tambah KRS, hapus, reset) lewat AJAX ----
                async function kirim(form) {
                    const btn = form.querySelector('button[type="submit"]');
                    const teksBtn = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';
                    try {
                        const resp = await fetch(form.action, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: new FormData(form),
                        });
                        const res = await resp.json().catch(() => ({}));
                        if (resp.status === 422 && res.errors) {
                            toast('warning', '⚠ Periksa kembali isian form Anda.');
                            return;
                        }
                        if (!resp.ok || !res.success) {
                            toast('error', pesanGalat(resp, res, 'memproses data ini'));
                            return;
                        }
                        const modal = form.closest('.modal');
                        if (modal) bootstrap.Modal.getOrCreateInstance(modal).hide();
                        toast('success', res.message);
                        await muatUlang();
                    } catch (err) {
                        toast('error', '✕ Gagal memproses data. Periksa koneksi Anda.');
                    } finally {
                        btn.disabled = false;
                        btn.innerHTML = teksBtn;
                    }
                }

                document.addEventListener('submit', (e) => {
                    const form = e.target.closest('.form-ajax');
                    if (!form) return;
                    e.preventDefault();
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        toast('warning', '⚠ Periksa kembali isian form Anda.');
                        return;
                    }
                    if (form.dataset.konfirmasi && !confirm(form.dataset.konfirmasi)) return;
                    kirim(form);
                });

                // ---- Filter angkatan & kurikulum langsung memuat ulang ----
                const formCek = $id('form-cek');
                $id('angkatan')?.addEventListener('change', () => {
                    const kur = $id('kurikulum');
                    if (kur) kur.value = '';
                    formCek.submit();
                });
                $id('kurikulum')?.addEventListener('change', () => formCek.submit());

                // Tooltip ikon SKS berbeda; dibuat saat disentuh agar tetap jalan
                // walau tabel dimuat ulang lewat AJAX
                ['mouseover', 'focusin'].forEach(ev => document.addEventListener(ev, (e) => {
                    const el = e.target.closest?.('.tooltip-sks');
                    if (!el || bootstrap.Tooltip.getInstance(el)) return;
                    bootstrap.Tooltip.getOrCreateInstance(el, { container: 'body' }).show();
                }));

                // NPM diganti: angkatan, kurikulum & link rekap milik mahasiswa sebelumnya
                // tidak ikut dikirim, supaya mahasiswa baru memakai angkatan & kurikulumnya sendiri
                const npmTampil = @json($npm);
                formCek.addEventListener('submit', () => {
                    if ($id('npm').value.trim() === npmTampil) return;
                    formCek.querySelectorAll('[name="angkatan"], [name="kurikulum"], [name="rekap"]')
                        .forEach(el => el.disabled = true);
                });
                @if ($galat_feeder && str_starts_with($galat_feeder, 'Gagal'))
                    toast('error', '✕ Gagal memuat data. Periksa koneksi Anda.');
                @endif

                // ---- Buka modal isi nilai / tambah KRS / hapus ----
                const modalNilai = new bootstrap.Modal($id('modal-nilai'));
                const modalKrs = new bootstrap.Modal($id('modal-krs'));
                const modalHapus = new bootstrap.Modal($id('modal-hapus'));
                const isi = (id, teks) => $id(id).textContent = teks;

                // Pratinjau huruf/bobot saat nilai angka diketik
                document.querySelectorAll('.input-nilai').forEach(input => {
                    const target = $id(input.dataset.pratinjau);
                    const bawaan = target.textContent;
                    input.addEventListener('input', () => {
                        const n = parseFloat(input.value);
                        if (input.value === '' || isNaN(n)) { target.textContent = bawaan; target.className = 'form-text'; return; }
                        const s = skala.find(s => n >= parseFloat(s.nilai_mulai) && n <= parseFloat(s.nilai_sampai));
                        target.textContent = s
                            ? `Hasil: ${s.nama} (bobot ${parseFloat(s.bobot).toFixed(2)})${s.lulus === 'T' ? ' — tidak lulus' : ''}`
                            : 'Nilai di luar skala nilai prodi.';
                        target.className = 'form-text fw-semibold ' + (!s ? 'text-danger' : (s.lulus === 'T' ? 'text-danger' : 'text-success'));
                    });
                });

                document.addEventListener('click', (e) => {
                    const tNilai = e.target.closest('.tombol-nilai');
                    if (tNilai) {
                        const d = tNilai.dataset;
                        $id('nilai-krs-id').value = d.krs;
                        isi('nilai-nama', d.nama); isi('nilai-kode', d.kode); isi('nilai-periode', d.periode);
                        // Mode perbaikan (nilai tidak lulus) vs isi nilai kosong
                        const perbaikan = !!d.lama;
                        $id('judul-modal-nilai').innerHTML = perbaikan
                            ? '<i class="bx bx-revision me-1"></i>Perbaiki Nilai Tidak Lulus'
                            : '<i class="bx bx-edit-alt me-1"></i>Isi Nilai';
                        $id('nilai-info-lama').classList.toggle('d-none', !perbaikan);
                        isi('nilai-lama', (d.lama || '') + (d.lamaAngka ? ` / ${d.lamaAngka}` : ''));
                        const inp = $id('nilai-angka');
                        inp.value = ''; inp.dispatchEvent(new Event('input'));
                        modalNilai.show();
                        setTimeout(() => inp.focus(), 300);
                        return;
                    }
                    const tKrs = e.target.closest('.tombol-krs');
                    if (tKrs) {
                        const d = tKrs.dataset;
                        $id('krs-kode-mk').value = d.kode;
                        isi('krs-nama', d.nama); isi('krs-kode', d.kode);
                        isi('krs-info', `${d.sks} SKS · semester ${d.semester}`);
                        $id('krs-periode').value = '';
                        const inp = $id('krs-nilai');
                        inp.value = ''; inp.dispatchEvent(new Event('input'));
                        modalKrs.show();
                        return;
                    }
                    const tHapus = e.target.closest('.tombol-hapus');
                    if (tHapus) {
                        const d = tHapus.dataset;
                        $id('hapus-krs-id').value = d.krs;
                        isi('hapus-nama', d.nama); isi('hapus-kode', d.kode);
                        isi('hapus-periode', d.periode); isi('hapus-nilai', d.nilai);
                        const jenis = {
                            dobel: ['Hapus Matakuliah Dobel', 'Matakuliah ini diambil lebih dari sekali; salah satu tetap tersisa.'],
                            luar: ['Hapus Matakuliah di Luar Kurikulum', 'Kode matakuliah ini tidak ada di kurikulum Neo Feeder yang dipakai.'],
                            rusak: ['Hapus KRS Rusak', 'Jadwal/matakuliah yang dirujuk KRS ini sudah tidak ada di SIADE.'],
                        }[d.jenis || 'dobel'];
                        isi('hapus-judul', jenis[0]); isi('hapus-alasan', jenis[1]);
                        $id('hapus-peringatan').classList.toggle('d-none', d.jenis !== 'luar' || !d.nilai || d.nilai === '-');
                        modalHapus.show();
                    }
                });

                // ---- Nilai PDDikti: bandingkan & salin per matakuliah ----
                const urlPddikti = @json(route('wisuda.transkrip.pddikti'));
                const modalPddikti = new bootstrap.Modal($id('modal-pddikti'));
                const esc = (t) => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
                const hurufSiade = (angka) => skala.find(s => angka >= parseFloat(s.nilai_mulai) && angka <= parseFloat(s.nilai_sampai));
                let pddikti = null; // { KODE: [baris...] } setelah diambil

                async function ambilPddikti(segarkan = false) {
                    if (pddikti && !segarkan) return pddikti;
                    const url = new URL(urlPddikti);
                    url.searchParams.set('npm', npm);
                    if (segarkan) url.searchParams.set('segarkan', 1);
                    const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const res = await resp.json().catch(() => ({}));
                    if (!resp.ok || !res.success) throw new Error(pesanGalat(resp, res, 'mengambil nilai PDDikti'));
                    pddikti = Array.isArray(res.nilai) ? {} : res.nilai;
                    return pddikti;
                }

                // Status satu baris PDDikti dibanding nilai SIADE (d = data tombol: krs, huruf)
                function statusPddikti(d, b) {
                    if (!d.krs) return { label: 'Belum ada KRS', kelas: 'bg-info-subtle text-info-emphasis', ket: 'KRS baru akan dibuat dengan nilai PDDikti' };
                    if (!String(d.huruf || '').trim()) return { label: 'Kosong di SIADE', kelas: 'bg-info-subtle text-info-emphasis', ket: 'Nilai SIADE masih kosong, bisa diisi dari PDDikti' };
                    if (sama(b.nilai_huruf, d.huruf)) return { label: 'Sama', kelas: 'bg-success-subtle text-success-emphasis', ket: 'Huruf SIADE dan PDDikti sama' };
                    return { label: 'Berbeda', kelas: 'bg-warning-subtle text-warning-emphasis', ket: 'SIADE dan PDDikti punya nilai yang berbeda' };
                }

                // Baris PDDikti yang dipakai untuk satu MK: periode sama, kalau tidak ada yang terakhir
                const pasangan = (baris, periode) => baris.find(b => b.periode === periode) || baris[baris.length - 1];
                const sama = (a, b) => String(a || '').trim().toUpperCase() === String(b || '').trim().toUpperCase();

                // Isi kolom "PDDikti" di tabel transkrip dengan huruf PDDikti (hijau = sama, oranye = beda)
                function tandaiPddikti() {
                    if (!pddikti) return;
                    let beda = 0, tidakAda = 0, kosong = 0;
                    area.querySelectorAll('.sel-pddikti').forEach(td => {
                        const baris = pddikti[td.dataset.kode] || [];
                        const tombol = td.querySelector('.tombol-pddikti');
                        let label;
                        if (!baris.length) {
                            tidakAda++;
                            label = '<span class="small text-muted">Tidak ada</span>';
                        } else {
                            const p = pasangan(baris, td.dataset.periode);
                            const siadeKosong = !String(td.dataset.huruf || '').trim();
                            const cocok = !siadeKosong && sama(p.nilai_huruf, td.dataset.huruf);
                            if (siadeKosong) kosong++; else if (!cocok) beda++;
                            const warna = cocok ? 'text-success' : (siadeKosong ? 'text-info' : 'text-warning');
                            const ket = cocok ? 'Sama dengan SIADE' : (siadeKosong ? 'Nilai SIADE kosong' : 'Berbeda dengan SIADE');
                            label = `<span class="fw-semibold ${warna}" title="${ket}">${esc(p.nilai_huruf || '-')}</span>`
                                + (p.nilai_angka !== null ? ` <span class="small text-muted">${esc(p.nilai_angka)}</span>` : '')
                                + (cocok ? ' <i class="bx bx-check text-success"></i>' : '')
                                + (siadeKosong ? '<div class="small text-info">kosong di SIADE</div>' : '');
                        }
                        td.innerHTML = `<div>${label}</div>`;
                        if (tombol) {
                            tombol.innerHTML = baris.length ? '<i class="bx bx-transfer-alt"></i> Salin' : '<i class="bx bx-cloud-download"></i> Cek';
                            td.appendChild(tombol);
                        }
                    });
                    return { beda, tidakAda, kosong };
                }

                async function bandingkanSemua(tombol) {
                    const teks = tombol.innerHTML;
                    tombol.disabled = true;
                    tombol.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengambil...';
                    try {
                        await ambilPddikti(true);
                        const h = tandaiPddikti();
                        const bagian = [];
                        if (h.beda) bagian.push(`${h.beda} MK nilainya berbeda (oranye)`);
                        if (h.kosong) bagian.push(`${h.kosong} MK kosong di SIADE tapi ada di PDDikti (biru)`);
                        toast(bagian.length ? 'warning' : 'success', bagian.length
                            ? `⚠ ${bagian.join(', ')}.`
                            : '✓ Semua nilai yang ada di PDDikti sama dengan SIADE.');
                    } catch (err) {
                        toast('error', err.message);
                    } finally {
                        tombol.disabled = false;
                        tombol.innerHTML = teks;
                    }
                }

                function isiModalPddikti(d) {
                    const baris = pddikti[d.kode] || [];
                    $id('pddikti-siade').textContent = d.krs
                        ? `${d.huruf || 'kosong'}${d.angka ? ' / ' + d.angka : ''}${d.namaPeriode ? ' · ' + d.namaPeriode : ''}`
                        : 'belum ada KRS (KRS baru dibuat pada periode PDDikti)';
                    if (!baris.length) {
                        $id('pddikti-isi').innerHTML = `<div class="text-center text-muted py-4"><i class="bx bx-search-alt fs-1 d-block mb-1"></i>
                            Matakuliah ${esc(d.kode)} tidak ditemukan di PDDikti untuk mahasiswa ini.</div>`;
                        $id('pddikti-simpan').disabled = true;
                        return;
                    }
                    const dipilih = pasangan(baris, d.periode);
                    $id('pddikti-isi').innerHTML = `
                        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th style="width:40px"><span class="visually-hidden">Pilih</span></th><th>Periode</th><th>Kelas</th>
                                <th class="text-center">Angka</th><th class="text-center">Huruf PDDikti</th><th class="text-center">Hasil di SIADE</th><th class="text-center">Status</th></tr></thead>
                            <tbody>${baris.map((b, i) => {
                                const s = b.nilai_angka !== null ? hurufSiade(b.nilai_angka) : null;
                                const status = statusPddikti(d, b);
                                return `<tr>
                                    <td><input class="form-check-input" type="radio" name="periode" value="${esc(b.periode)}" id="pd-${i}" ${b === dipilih ? 'checked' : ''} required></td>
                                    <td><label for="pd-${i}" class="mb-0">${esc(b.periode)}</label></td>
                                    <td class="small">${esc(b.kelas || '-')}</td>
                                    <td class="text-center">${b.nilai_angka !== null ? esc(b.nilai_angka) : '-'}</td>
                                    <td class="text-center fw-semibold">${esc(b.nilai_huruf || '-')}</td>
                                    <td class="text-center">${s ? `<span class="fw-semibold ${s.lulus === 'T' ? 'text-danger' : ''}">${esc(s.nama)}</span> <span class="small text-muted">(${parseFloat(s.bobot).toFixed(2)})</span>` : '<span class="small text-muted">dari huruf</span>'}</td>
                                    <td class="text-center"><span class="badge ${status.kelas} badge-titik" title="${esc(status.ket)}">${status.label}</span></td>
                                </tr>`;
                            }).join('')}</tbody>
                        </table></div>
                        ${baris.length > 1 ? '<div class="small text-muted mt-1">MK ini tercatat lebih dari sekali di PDDikti; pilih periode yang nilainya disalin.</div>' : ''}`;
                    $id('pddikti-simpan').disabled = false;
                }

                let pddiktiAktif = null;
                async function bukaPddikti(d, segarkan = false) {
                    pddiktiAktif = d;
                    $id('pddikti-kode-mk').value = d.kode;
                    $id('pddikti-krs-id').value = d.krs || '';
                    isi('pddikti-nama', d.nama); isi('pddikti-kode', d.kode);
                    $id('pddikti-siade').textContent = '';
                    $id('pddikti-isi').innerHTML = '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-1"></span>Mengambil nilai dari PDDikti...</div>';
                    $id('pddikti-simpan').disabled = true;
                    modalPddikti.show();
                    try {
                        await ambilPddikti(segarkan);
                        isiModalPddikti(d);
                        tandaiPddikti();
                    } catch (err) {
                        $id('pddikti-isi').innerHTML = `<div class="alert alert-danger small mb-0">${esc(err.message)}</div>`;
                        toast('error', '✕ Gagal memuat data. Periksa koneksi Anda.');
                    }
                }

                document.addEventListener('click', (e) => {
                    const tBanding = e.target.closest('#tombol-banding-pddikti');
                    if (tBanding) return bandingkanSemua(tBanding);
                    const t = e.target.closest('.tombol-pddikti');
                    if (t) return bukaPddikti({ ...t.dataset });
                    if (e.target.closest('#pddikti-segarkan') && pddiktiAktif) bukaPddikti(pddiktiAktif, true);
                });

                // ---- Pilih matakuliah dobel ----
                area.addEventListener('change', async (e) => {
                    const radio = e.target.closest('.pilih-dobel');
                    if (!radio) return;

                    const grupRadio = area.querySelectorAll(`input[name="${radio.name}"]`);
                    const sebelumnya = [...grupRadio].find(el => el.defaultChecked);
                    grupRadio.forEach(el => el.disabled = true);

                    try {
                        const resp = await fetch(urlPilih, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify({ npm: npm, krs_id: Number(radio.value) }),
                        });
                        const res = await resp.json().catch(() => ({}));
                        if (!resp.ok || !res.success) throw new Error(res.message);

                        toast('success', '✓ Data berhasil diperbarui');
                        await muatUlang();
                    } catch (err) {
                        // Kembalikan pilihan ke kondisi sebelum diklik
                        if (sebelumnya) sebelumnya.checked = true;
                        grupRadio.forEach(el => el.disabled = false);
                        toast('error', err.message || '✕ Gagal memperbarui data. Coba lagi.');
                    }
                });
            })();
        </script>
    @endpush
@endif
