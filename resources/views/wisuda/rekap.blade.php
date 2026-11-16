@extends('layouts.app')

@push('css')
    <style>
        .badge-titik::before { content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: currentColor; margin-right: 5px; vertical-align: middle; }
        .kartu-angka { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
        .angka-masalah { color: var(--bs-danger); font-weight: 600; }
        /* Saat area data dimuat ulang lewat AJAX */
        #area-rekap { transition: opacity .15s; }
        #area-rekap.memuat { opacity: .5; pointer-events: none; }
    </style>
@endpush

@section('content')
    @php
        // Filter rekap yang dibawa ke halaman detail agar bisa kembali ke posisi yang sama
        // NIM dibawa dalam bentuk dipisah koma agar URL tetap ringkas
        $nimParam = $daftar_nim->implode(',');
        $parameterRekap = array_filter([
            'prodi' => $prodi, 'angkatan' => $angkatan, 'nim' => $nimParam, 'kurikulum' => $kurikulum_dipilih,
            'status' => $status !== 'semua' ? $status : null, 'cari' => $cari,
            'per_halaman' => $per_halaman != 20 ? $per_halaman : null,
            'page' => request('page') > 1 ? request('page') : null,
        ]);
        $parameterTetap = array_filter(['prodi' => $prodi, 'angkatan' => $angkatan, 'nim' => $nimParam, 'kurikulum' => $kurikulum_dipilih]);
    @endphp

    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-0"><i class="bx bx-award me-2"></i>Cek Transkrip Per Angkatan</h5>
            <small class="text-muted">Masukkan daftar NIM yang akan dicek, lalu sistem memeriksa IPK, nilai kosong, matakuliah dobel,
                dan kesesuaian kurikulum angkatannya. Nilai dari SIADE, kurikulum dari Neo Feeder.</small>
        </div>
        <a href="{{ route('wisuda.transkrip.index') }}" class="btn btn-sm btn-outline-primary ms-auto">
            <i class="bx bx-user me-1"></i>Cek Per Mahasiswa
        </a>
    </div>

    {{-- Pilih prodi, angkatan, kurikulum --}}
    <div class="card">
        <div class="card-body">
            <form action="{{ route('wisuda.rekap.index') }}" method="get" class="row g-2 align-items-end" id="form-rekap">
                <div class="col-md-4">
                    <label for="prodi" class="form-label">Program Studi</label>
                    <select name="prodi" id="prodi" class="form-select select2" data-placeholder="Pilih program studi" required>
                        <option value=""></option>
                        @foreach ($daftar_prodi as $p)
                            <option value="{{ $p->kode_program_studi }}" @selected($p->kode_program_studi == $prodi)>
                                {{ $p->nama_program_studi_idn }} ({{ $p->kode_program_studi }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="angkatan" class="form-label">Tahun Angkatan</label>
                    <select name="angkatan" id="angkatan" class="form-select" required @disabled($daftar_angkatan->isEmpty())>
                        <option value="">{{ $prodi ? 'Pilih angkatan' : 'Pilih prodi dulu' }}</option>
                        @foreach ($daftar_angkatan as $nilai => $jumlah)
                            <option value="{{ $nilai }}" @selected($nilai == $angkatan)>
                                {{ substr($nilai, 0, 4) }}{{ substr($nilai, 4) === '2' ? ' Genap' : '' }} ({{ $jumlah }} mhs)
                            </option>
                        @endforeach
                    </select>
                </div>
                @if ($daftar_kurikulum->isEmpty() && $kurikulum_dipilih)
                    {{-- Feeder gagal: pilihan kurikulum tetap dibawa saat form dikirim --}}
                    <input type="hidden" name="kurikulum" value="{{ $kurikulum_dipilih }}">
                @endif
                @if ($daftar_kurikulum->isNotEmpty())
                    <div class="col-md-4">
                        <label for="kurikulum" class="form-label">Kurikulum (Neo Feeder)</label>
                        <select name="kurikulum" id="kurikulum" class="form-select">
                            <option value="">Otomatis sesuai angkatan{{ !empty($kurikulum_otomatis) ? ' — ' . $kurikulum_otomatis['nama_kurikulum'] : '' }}</option>
                            @foreach ($daftar_kurikulum as $k)
                                <option value="{{ $k['id_kurikulum'] }}" @selected($k['id_kurikulum'] === $kurikulum_dipilih)>
                                    {{ $k['nama_kurikulum'] }} (berlaku {{ $k['semester_mulai_berlaku'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-12">
                    <label for="nim" class="form-label mb-1">Daftar NIM yang akan dicek <span class="text-danger">*</span></label>
                    <textarea name="nim" id="nim" rows="5" class="form-control font-monospace" required
                        placeholder="Satu NIM per baris (bisa juga dipisah koma/spasi), contoh:&#10;21103160201013&#10;21103160201016&#10;21103160201093">{{ $nim_teks }}</textarea>
                    <div class="form-text"><span id="jumlah-nim">{{ $daftar_nim->count() }}</span> NIM · bisa langsung tempel dari Excel.
                        Hanya NIM dari prodi & angkatan yang dipilih yang diperiksa.</div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary"><i class="bx bx-search-alt me-1"></i>Cek NIM</button>
                </div>
                @if ($hasil)
                    <div class="col-auto">
                        <a href="{{ route('wisuda.rekap.index', $parameterTetap + ['segarkan' => 1]) }}" class="btn btn-outline-secondary"
                            title="Ambil ulang data kurikulum terbaru dari Neo Feeder">
                            <i class="bx bx-refresh me-1"></i>Muat Ulang Feeder
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @if (!$hasil)
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="bx bx-filter-alt" style="font-size: 3rem"></i>
                <h6 class="mt-2 mb-1">Pilih program studi, tahun angkatan, lalu masukkan daftar NIM</h6>
                <p class="small mb-0">Sistem akan memeriksa transkrip setiap NIM yang dimasukkan.</p>
            </div>
        </div>
    @else
        @if ($galat_feeder)
            <div class="alert alert-warning d-flex align-items-start">
                <i class="bx bx-wifi-off fs-4 me-2"></i>
                <div class="flex-grow-1">{{ $galat_feeder }}</div>
                <a href="{{ route('wisuda.rekap.index', $parameterTetap + ['segarkan' => 1]) }}"
                    class="btn btn-sm btn-outline-warning ms-2 text-nowrap"><i class="bx bx-refresh me-1"></i>Coba Lagi</a>
            </div>
        @endif

        @if (count($nim_tidak_ada) || count($nim_beda))
            <div class="alert alert-warning">
                <div class="d-flex align-items-start">
                    <i class="bx bx-error fs-4 me-2"></i>
                    <div class="flex-grow-1">
                        <strong>{{ count($nim_tidak_ada) + count($nim_beda) }} NIM tidak diperiksa.</strong>
                        @if (count($nim_tidak_ada))
                            <div class="small mt-1">Tidak terdaftar di SIADE ({{ count($nim_tidak_ada) }}):
                                @foreach ($nim_tidak_ada as $n)
                                    <span class="badge bg-white text-dark border font-monospace">{{ $n }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if (count($nim_beda))
                            <div class="small mt-1">Bukan prodi/angkatan yang dipilih ({{ count($nim_beda) }}):
                                @foreach ($nim_beda as $m)
                                    <a href="{{ route('wisuda.transkrip.index', ['npm' => $m['npm']]) }}" class="badge bg-white text-dark border font-monospace text-decoration-none"
                                        title="{{ $m['nama'] }} — {{ $m['prodi'] }} angkatan {{ $m['angkatan'] }}">{{ $m['npm'] }}</a>
                                @endforeach
                                <span class="text-muted">(arahkan kursor untuk melihat prodi & angkatannya, klik untuk cek per mahasiswa)</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Area data: dimuat ulang lewat AJAX setelah simpan tanpa reload halaman --}}
        <div id="area-rekap">
        {{-- Ringkasan angkatan --}}
        @php $ra = $ringkasan_angkatan; @endphp
        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                <div class="card h-100 mb-0"><div class="card-body">
                    <div class="small text-muted">Mahasiswa Diperiksa</div>
                    <div class="kartu-angka">{{ $total_mahasiswa }}</div>
                    <div class="small text-muted">dari {{ $daftar_nim->count() }} NIM dimasukkan</div>
                </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 mb-0 border-success"><div class="card-body">
                    <div class="small text-muted">Siap Wisuda</div>
                    <div class="kartu-angka text-success">{{ $ra['siap'] }}</div>
                    <div class="small text-muted">{{ $total_mahasiswa - $ra['siap'] }} perlu dicek</div>
                </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 mb-0"><div class="card-body">
                    <div class="small text-muted">Rata-rata IPK</div>
                    <div class="kartu-angka text-primary">{{ number_format($ra['rata_ipk'], 2) }}</div>
                </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 mb-0 {{ $ra['nilai_kosong'] ? 'border-danger' : '' }}"><div class="card-body">
                    <div class="small text-muted">Nilai Kosong</div>
                    <div class="kartu-angka {{ $ra['nilai_kosong'] ? 'text-danger' : '' }}">{{ $ra['nilai_kosong'] }}</div>
                    <div class="small text-muted">pada {{ $ra['mhs_nilai_kosong'] }} mahasiswa</div>
                </div></div>
            </div>
        </div>

        <div class="card">
            {{-- Toolbar: jumlah per halaman → filter status → pencarian --}}
            <div class="card-header py-3">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <form action="{{ route('wisuda.rekap.index') }}" method="get" class="d-flex align-items-center flex-wrap gap-2 w-100" id="form-toolbar">
                        @foreach ($parameterTetap as $k => $v)
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endforeach
                        <div class="d-flex align-items-center gap-2 small">
                            <label for="per_halaman" class="text-nowrap mb-0">Tampilkan</label>
                            <select name="per_halaman" id="per_halaman" class="form-select form-select-sm kirim-otomatis" style="width: auto">
                                @foreach ([10, 20, 50, 100] as $n)
                                    <option value="{{ $n }}" @selected($n == $per_halaman)>{{ $n }}</option>
                                @endforeach
                            </select>
                            <span class="text-nowrap">data</span>
                        </div>
                        <label for="status" class="visually-hidden">Filter status</label>
                        <select name="status" id="status" class="form-select form-select-sm kirim-otomatis" style="width: auto">
                            @foreach ($opsi_filter as $kunci => $label)
                                <option value="{{ $kunci }}" @selected($kunci === $status)>{{ $label }} ({{ $jumlah_per_filter[$kunci] }})</option>
                            @endforeach
                        </select>
                        <div class="ms-md-auto">
                            <label for="cari" class="visually-hidden">Cari mahasiswa</label>
                            <div class="input-group input-group-sm">
                                <input type="search" name="cari" id="cari" value="{{ $cari }}" class="form-control" placeholder="Cari NPM / nama" style="width: 220px">
                                <button type="submit" class="btn btn-outline-secondary" aria-label="Cari"><i class="bx bx-search"></i></button>
                            </div>
                        </div>
                        <a href="{{ route('wisuda.rekap.export', array_diff_key($parameterRekap, ['page' => 1, 'per_halaman' => 1])) }}"
                            class="btn btn-sm btn-success text-nowrap" title="Export sesuai NIM, kurikulum, filter & pencarian yang aktif">
                            <i class="bx bx-spreadsheet me-1"></i>Export Excel
                        </a>
                    </form>
                </div>
                @if ($kurikulum)
                    <div class="small text-muted mt-2">
                        Dicocokkan dengan <strong>{{ $kurikulum['nama_kurikulum'] }}</strong>
                        · syarat lulus {{ $kurikulum['jumlah_sks_lulus'] }} SKS
                        {{ $kurikulum_dipilih ? '· dipilih manual' : '· otomatis sesuai angkatan' }}
                    </div>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 45px">No</th>
                            <th>Mahasiswa</th>
                            <th class="text-center">IPK</th>
                            <th class="text-center">SKS</th>
                            <th class="text-center">Nilai Kosong / Tidak Lulus</th>
                            <th class="text-center">Dobel</th>
                            <th class="text-center">MK Kurang</th>
                            <th>Status</th>
                            <th class="text-end"><span class="visually-hidden">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($hasil as $i => $b)
                            @php $r = $b['ringkasan']; @endphp
                            <tr>
                                <td class="text-center text-muted">{{ $hasil->firstItem() + $i }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $b['nama'] }}</div>
                                    <span class="badge bg-light text-dark border font-monospace">{{ $b['npm'] }}</span>
                                </td>
                                <td class="text-center fw-semibold">{{ $r['total_sks'] > 0 ? number_format($r['ipk'], 2) : '-' }}</td>
                                <td class="text-center text-nowrap">
                                    <span class="{{ in_array('sks-kurang', $b['masalah']) ? 'angka-masalah' : '' }}">{{ $r['total_sks'] }}</span>
                                    @isset($r['syarat_sks_lulus'])
                                        <span class="text-muted small">/ {{ $r['syarat_sks_lulus'] }}</span>
                                    @endisset
                                </td>
                                <td class="text-center">
                                    @if (count($b['daftar_nilai_kosong']) || count($b['daftar_tidak_lulus']))
                                        <div class="d-flex flex-column align-items-center gap-1">
                                            @if (count($b['daftar_nilai_kosong']))
                                                <button type="button" class="btn btn-sm btn-outline-danger text-nowrap tombol-isi"
                                                    data-npm="{{ $b['npm'] }}" data-fokus="nilai" title="Isi nilai yang masih kosong">
                                                    <i class="bx bx-edit-alt"></i> Isi {{ count($b['daftar_nilai_kosong']) }}
                                                </button>
                                            @endif
                                            @if (count($b['daftar_tidak_lulus']))
                                                <button type="button" class="btn btn-sm btn-outline-warning text-nowrap tombol-isi"
                                                    data-npm="{{ $b['npm'] }}" data-fokus="tidak-lulus" title="Perbaiki nilai tidak lulus">
                                                    <i class="bx bx-revision"></i> Perbaiki {{ count($b['daftar_tidak_lulus']) }}
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($r['jumlah_dobel'])
                                        <span class="{{ $r['dobel_belum_dipilih'] ? 'angka-masalah' : '' }}">{{ $r['jumlah_dobel'] }}</span>
                                        @if ($r['dobel_belum_dipilih'])
                                            <div class="small text-danger">{{ $r['dobel_belum_dipilih'] }} belum dipilih</div>
                                        @endif
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @isset($r['mk_kurang'])
                                        <span class="{{ $r['mk_kurang'] ? 'angka-masalah' : 'text-muted' }}">{{ $r['mk_kurang'] }}</span>
                                        @if ($b['belum_diambil'])
                                            <div>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-nowrap tombol-isi"
                                                    data-npm="{{ $b['npm'] }}" data-fokus="krs">
                                                    <i class="bx bx-plus"></i> Tambah KRS ({{ $b['belum_diambil'] }})
                                                </button>
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endisset
                                </td>
                                <td>
                                    @if ($b['siap'])
                                        <span class="badge bg-success-subtle text-success-emphasis badge-titik">Siap wisuda</span>
                                    @elseif (empty($b['masalah']))
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis badge-titik">Kurikulum tidak tersedia</span>
                                    @else
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach ($b['masalah'] as $m)
                                                <span class="badge bg-danger-subtle text-danger-emphasis badge-titik">{{ $opsi_filter[$m] }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('wisuda.transkrip.index', array_filter(['npm' => $b['npm'], 'angkatan' => $angkatan, 'kurikulum' => $kurikulum_dipilih, 'rekap' => http_build_query($parameterRekap)])) }}"
                                        class="btn btn-sm {{ $b['siap'] ? 'btn-outline-primary' : 'btn-primary' }} text-nowrap">
                                        {{ $b['siap'] ? 'Lihat' : 'Perbaiki' }} <i class="bx bx-chevron-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bx bx-search-alt fs-1 d-block mb-1"></i>
                                    @if ($total_mahasiswa === 0)
                                        Tidak ada NIM yang terdaftar di prodi dan angkatan ini. Periksa kembali daftar NIM atau pilihan prodi/angkatan.
                                    @else
                                        Tidak ada mahasiswa yang cocok dengan filter/pencarian.
                                        <a href="{{ route('wisuda.rekap.index', $parameterTetap) }}">Tampilkan semua</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex align-items-center flex-wrap gap-2">
                <div class="small text-muted me-auto">
                    @if ($hasil->total())
                        Menampilkan {{ $hasil->firstItem() }}–{{ $hasil->lastItem() }} dari {{ $hasil->total() }} data
                    @else
                        Menampilkan 0 dari 0 data
                    @endif
                </div>
                {{ $hasil->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        </div>

        {{-- Data untuk jendela isi; ikut diperbarui saat area dimuat ulang --}}
        @php
            $dataIsi = [];
            foreach ($hasil as $b) {
                $dataIsi[$b['npm']] = [
                    'nama' => $b['nama'],
                    'nilai' => $b['daftar_nilai_kosong'],
                    'tidak_lulus' => $b['daftar_tidak_lulus'],
                    'krs' => $b['daftar_belum_diambil'],
                    'url' => route('wisuda.transkrip.index', array_filter([
                        'npm' => $b['npm'], 'angkatan' => $angkatan, 'kurikulum' => $kurikulum_dipilih,
                        'rekap' => http_build_query($parameterRekap),
                    ])),
                ];
            }
        @endphp
        <script type="application/json" id="data-isi">{!! json_encode($dataIsi, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}</script>
        </div>{{-- /#area-rekap --}}

        {{-- Jendela isi nilai kosong & tambah KRS (diisi lewat JS sesuai mahasiswa yang diklik) --}}
        <div class="modal fade" id="modal-isi" tabindex="-1" aria-labelledby="judul-modal-isi" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form action="{{ route('wisuda.rekap.simpan') }}" method="post" class="modal-content" id="form-isi" novalidate>
                    @csrf
                    <input type="hidden" name="npm" id="isi-npm">
                    <input type="hidden" name="angkatan" value="{{ $angkatan }}">
                    <input type="hidden" name="kembali" value="{{ http_build_query($parameterRekap) }}">
                    <div class="modal-header">
                        <div>
                            <h6 class="modal-title mb-0" id="judul-modal-isi">Isi / Perbaiki Nilai & KRS</h6>
                            <div class="small text-muted"><span id="isi-nama" class="fw-semibold"></span> · <span id="isi-npm-teks" class="font-monospace"></span></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info d-flex align-items-center flex-wrap gap-2 py-2 small">
                            <i class="bx bx-cloud-download fs-5"></i>
                            <div class="flex-grow-1">Isi otomatis dari nilai yang tercatat di PDDikti (Neo Feeder). Periksa dulu, lalu klik Simpan.</div>
                            <button type="button" class="btn btn-sm btn-primary" id="isi-pddikti"><i class="bx bx-cloud-download me-1"></i>Ambil dari PDDikti</button>
                        </div>
                        <section id="bagian-nilai" class="mb-4">
                            <h6 class="mb-1"><i class="bx bx-edit-alt me-1 text-danger"></i>Nilai Kosong</h6>
                            <p class="small text-muted mb-2">Ketik nilai angka 0–100. Huruf & bobot dihitung dari skala nilai prodi. Baris yang dikosongkan tidak disimpan.</p>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr><th>Matakuliah</th><th>Periode</th><th class="text-center">SKS</th><th style="width: 140px">Nilai Angka</th><th style="width: 130px">Hasil</th></tr>
                                    </thead>
                                    <tbody id="isi-tabel-nilai"></tbody>
                                </table>
                            </div>
                        </section>
                        <section id="bagian-tidak-lulus" class="mb-4">
                            <h6 class="mb-1"><i class="bx bx-revision me-1 text-warning"></i>Perbaikan Nilai Tidak Lulus</h6>
                            <p class="small text-muted mb-2">Isi nilai perbaikan untuk menggantikan nilai lama di KRS. Nilai lama dicatat di riwayat perbaikan. Baris yang dikosongkan tidak diubah.</p>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr><th>Matakuliah</th><th>Periode</th><th class="text-center">SKS</th><th class="text-center">Nilai Sekarang</th><th style="width: 140px">Nilai Perbaikan</th><th style="width: 130px">Hasil</th></tr>
                                    </thead>
                                    <tbody id="isi-tabel-tidak-lulus"></tbody>
                                </table>
                            </div>
                        </section>
                        <section id="bagian-krs">
                            <h6 class="mb-1"><i class="bx bx-plus-circle me-1 text-primary"></i>MK Kurikulum yang Belum Ada KRS</h6>
                            <p class="small text-muted mb-2">Centang MK yang ingin ditambahkan, pilih periodenya. Nilai boleh dikosongkan.</p>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr><th style="width: 40px"><span class="visually-hidden">Tambah</span></th><th>Matakuliah</th><th class="text-center">SKS · Smt</th><th style="width: 200px">Periode</th><th style="width: 130px">Nilai Angka</th><th style="width: 130px">Hasil</th></tr>
                                    </thead>
                                    <tbody id="isi-tabel-krs"></tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                    <div class="modal-footer">
                        <a href="#" id="isi-link-detail" class="btn btn-link me-auto">Buka transkrip lengkap</a>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('js')
    <script>
        // Ganti prodi → muat ulang daftar angkatan (angkatan & kurikulum direset)
        $('#prodi').on('change', function() {
            const form = document.getElementById('form-rekap');
            form.querySelector('#angkatan').value = '';
            const kur = form.querySelector('#kurikulum');
            if (kur) kur.value = '';
            const url = new URL(form.action);
            url.searchParams.set('prodi', this.value);
            const nim = document.getElementById('nim').value.trim();
            if (nim) url.searchParams.set('nim', nim.split(/[\s,;]+/).join(','));
            window.location = url;
        });
        // Ganti angkatan → kurikulum kembali otomatis
        document.getElementById('angkatan').addEventListener('change', () => {
            const kur = document.getElementById('kurikulum');
            if (kur) kur.value = '';
        });
        // Hitung jumlah NIM saat diketik/ditempel
        document.getElementById('nim').addEventListener('input', function() {
            const n = new Set(this.value.split(/[\s,;]+/).filter(Boolean)).size;
            document.getElementById('jumlah-nim').textContent = n;
        });
        // Ringkas isi textarea jadi dipisah koma saat dikirim agar URL pendek
        document.getElementById('form-rekap').addEventListener('submit', function() {
            const t = document.getElementById('nim');
            t.value = [...new Set(t.value.split(/[\s,;]+/).filter(Boolean))].join(',');
        });
        // Jumlah per halaman & filter status langsung diterapkan
        // (delegasi, karena toolbar ikut diganti saat area dimuat ulang)
        document.addEventListener('change', (e) => { if (e.target.classList.contains('kirim-otomatis')) e.target.form.submit(); });

        @if ($hasil)
            // ---- Jendela isi nilai & tambah KRS ----
            (function() {
                const csrf = @json(csrf_token());
                const area = document.getElementById('area-rekap');
                const dataIsi = () => JSON.parse(document.getElementById('data-isi').textContent);
                const skala = @json($skala_nilai);
                const periode = @json($pilihan_periode);
                const modal = new bootstrap.Modal(document.getElementById('modal-isi'));
                const form = document.getElementById('form-isi');
                const esc = (t) => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
                const sksTeks = (n) => String(parseFloat(n));
                const opsiPeriode = '<option value="">Pilih periode</option>' +
                    Object.entries(periode).map(([k, v]) => `<option value="${esc(k)}">${esc(v)}</option>`).join('');

                function toast(tipe, pesan) {
                    Lobibox.notify(tipe, { size: 'mini', rounded: true, delay: 3000, delayIndicator: false, position: 'bottom right', msg: pesan, sound: false,
                        icon: tipe === 'success' ? 'bx bx-check-circle' : (tipe === 'warning' ? 'bx bx-error' : 'bx bx-x-circle') });
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

                // Muat ulang ringkasan + tabel tanpa reload halaman (posisi scroll tetap)
                async function muatUlang() {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('segarkan'); // jangan ambil ulang Feeder setiap simpan
                    area.classList.add('memuat');
                    try {
                        const resp = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        if (!resp.ok) throw new Error();
                        const baru = new DOMParser().parseFromString(await resp.text(), 'text/html').getElementById('area-rekap');
                        if (!baru) throw new Error();
                        area.innerHTML = baru.innerHTML;
                    } catch (err) {
                        toast('error', '✕ Gagal memuat data. Periksa koneksi Anda.');
                    } finally {
                        area.classList.remove('memuat');
                    }
                }

                // Pratinjau huruf/bobot di sel "Hasil" pada baris yang sama
                function pratinjau(input) {
                    const sel = input.closest('tr').querySelector('.hasil-nilai');
                    const n = parseFloat(input.value);
                    if (input.value === '' || isNaN(n)) { sel.innerHTML = '<span class="text-muted">-</span>'; return; }
                    const s = skala.find(s => n >= parseFloat(s.nilai_mulai) && n <= parseFloat(s.nilai_sampai));
                    sel.innerHTML = !s ? '<span class="text-danger small">Di luar skala</span>'
                        : `<span class="fw-semibold ${s.lulus === 'T' ? 'text-danger' : 'text-success'}">${esc(s.nama)}</span> <span class="small text-muted">(${parseFloat(s.bobot).toFixed(2)})</span>`;
                }

                document.addEventListener('click', (e) => {
                    const tombol = e.target.closest('.tombol-isi');
                    if (!tombol) return;
                    const npm = tombol.dataset.npm;
                    const d = dataIsi()[npm];
                    if (!d) return;
                    document.getElementById('isi-npm').value = npm;
                    document.getElementById('isi-npm-teks').textContent = npm;
                    document.getElementById('isi-nama').textContent = d.nama;
                    document.getElementById('isi-link-detail').href = d.url;

                    document.getElementById('bagian-nilai').classList.toggle('d-none', d.nilai.length === 0);
                    document.getElementById('isi-tabel-nilai').innerHTML = d.nilai.map(n => `
                        <tr data-kode="${esc(String(n.kode_mk).trim().toUpperCase())}" data-periode="${esc(n.kode_periode)}">
                            <td><div class="fw-semibold">${esc(n.nama_mk)}</div><span class="badge bg-light text-dark border font-monospace">${esc(n.kode_mk)}</span></td>
                            <td class="small">${esc(n.periode)}</td>
                            <td class="text-center">${esc(n.sks)}</td>
                            <td><input type="number" name="nilai[${n.krs_id}]" class="form-control form-control-sm input-nilai" min="0" max="100" step="0.01"
                                aria-label="Nilai angka ${esc(n.nama_mk)}" placeholder="0–100"></td>
                            <td class="hasil-nilai"><span class="text-muted">-</span></td>
                        </tr>`).join('');

                    document.getElementById('bagian-tidak-lulus').classList.toggle('d-none', d.tidak_lulus.length === 0);
                    document.getElementById('isi-tabel-tidak-lulus').innerHTML = d.tidak_lulus.map(n => `
                        <tr data-kode="${esc(String(n.kode_mk).trim().toUpperCase())}" data-periode="${esc(n.kode_periode)}" data-huruf="${esc(n.huruf)}">
                            <td><div class="fw-semibold">${esc(n.nama_mk)}</div><span class="badge bg-light text-dark border font-monospace">${esc(n.kode_mk)}</span></td>
                            <td class="small">${esc(n.periode)}</td>
                            <td class="text-center">${esc(n.sks)}</td>
                            <td class="text-center"><span class="fw-semibold text-danger">${esc(n.huruf)}</span>${n.angka ? ` <span class="small text-muted">(${esc(n.angka)})</span>` : ''}</td>
                            <td><input type="number" name="nilai[${n.krs_id}]" class="form-control form-control-sm input-nilai" min="0" max="100" step="0.01"
                                aria-label="Nilai perbaikan ${esc(n.nama_mk)}" placeholder="0–100"></td>
                            <td class="hasil-nilai"><span class="text-muted">-</span></td>
                        </tr>`).join('');

                    document.getElementById('bagian-krs').classList.toggle('d-none', d.krs.length === 0);
                    document.getElementById('isi-tabel-krs').innerHTML = d.krs.map(m => {
                        const kode = esc(m.kode_mk);
                        const mati = !m.ada_di_master;
                        return `
                        <tr class="${mati ? 'text-muted' : ''}" data-kode="${kode}" data-krs-baru="1">
                            <td><input class="form-check-input centang-krs" type="checkbox" name="krs[${kode}][tambah]" value="1" id="krs-${kode}"
                                ${mati ? 'disabled' : ''} aria-label="Tambah KRS ${esc(m.nama_mk)}"></td>
                            <td><label for="krs-${kode}" class="fw-semibold mb-0">${esc(m.nama_mk)}</label><br>
                                <span class="badge bg-light text-dark border font-monospace">${kode}</span>
                                <span class="badge ${m.wajib ? 'bg-primary-subtle text-primary-emphasis' : 'bg-secondary-subtle text-secondary-emphasis'}">${m.wajib ? 'Wajib' : 'Pilihan'}</span>
                                ${mati ? '<div class="small"><i class="bx bx-info-circle"></i> Belum ada di master kurikulum SIADE</div>' : ''}</td>
                            <td class="text-center">${sksTeks(m.sks)} · ${esc(m.semester)}</td>
                            <td><select name="krs[${kode}][periode]" class="form-select form-select-sm pilih-periode" disabled aria-label="Periode ${esc(m.nama_mk)}">${opsiPeriode}</select></td>
                            <td><input type="number" name="krs[${kode}][nilai]" class="form-control form-control-sm input-nilai" min="0" max="100" step="0.01"
                                disabled placeholder="opsional" aria-label="Nilai angka ${esc(m.nama_mk)}"></td>
                            <td class="hasil-nilai"><span class="text-muted">-</span></td>
                        </tr>`;
                    }).join('');

                    const btn = form.querySelector('button[type="submit"]');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bx bx-save me-1"></i>Simpan';
                    modal.show();

                    // Fokus ke bagian yang diklik
                    setTimeout(() => {
                        const target = { krs: 'bagian-krs', 'tidak-lulus': 'bagian-tidak-lulus' }[tombol.dataset.fokus] || 'bagian-nilai';
                        document.getElementById(target).scrollIntoView({ block: 'start' });
                        if (target !== 'bagian-krs') document.querySelector(`#${target} .input-nilai`)?.focus();
                    }, 350);
                });

                // ---- Isi otomatis dari PDDikti ----
                const urlPddikti = @json(route('wisuda.transkrip.pddikti'));
                const keterangan = (tr, html) => {
                    let el = tr.querySelector('.info-pddikti');
                    if (!el) {
                        el = document.createElement('div');
                        el.className = 'small info-pddikti';
                        tr.children[tr.dataset.krsBaru ? 1 : 0].appendChild(el); // sel nama matakuliah
                    }
                    el.innerHTML = html;
                };

                document.getElementById('isi-pddikti').addEventListener('click', async function() {
                    const npm = document.getElementById('isi-npm').value;
                    const teks = this.innerHTML;
                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengambil...';
                    try {
                        const url = new URL(urlPddikti);
                        url.searchParams.set('npm', npm);
                        const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        const res = await resp.json().catch(() => ({}));
                        if (!resp.ok || !res.success) throw new Error(pesanGalat(resp, res, 'mengambil nilai PDDikti'));
                        const nilai = Array.isArray(res.nilai) ? {} : res.nilai;
                        let terisi = 0;

                        form.querySelectorAll('tbody tr[data-kode]').forEach(tr => {
                            const baris = nilai[tr.dataset.kode] || [];
                            if (!baris.length) { keterangan(tr, '<span class="text-muted">Tidak ada di PDDikti</span>'); return; }
                            const p = baris.find(b => b.periode === tr.dataset.periode) || baris[baris.length - 1];
                            const label = `PDDikti: <strong>${esc(p.nilai_huruf || '-')}</strong>${p.nilai_angka !== null ? ' (' + esc(p.nilai_angka) + ')' : ''} · ${esc(p.periode)}`;
                            if (p.nilai_angka === null) { keterangan(tr, `<span class="text-muted">${label} — tanpa nilai angka</span>`); return; }
                            if (tr.dataset.huruf && String(p.nilai_huruf).toUpperCase() === tr.dataset.huruf.toUpperCase()) {
                                keterangan(tr, `<span class="text-muted">${label} — sama dengan SIADE</span>`);
                                return;
                            }

                            if (tr.dataset.krsBaru) {
                                const centang = tr.querySelector('.centang-krs');
                                if (centang.disabled) { keterangan(tr, `<span class="text-muted">${label}</span>`); return; }
                                centang.checked = true;
                                centang.dispatchEvent(new Event('change', { bubbles: true }));
                                const sel = tr.querySelector('.pilih-periode');
                                if (![...sel.options].some(o => o.value === p.periode)) sel.add(new Option(p.periode, p.periode));
                                sel.value = p.periode;
                            }
                            const input = tr.querySelector('.input-nilai');
                            input.value = p.nilai_angka;
                            pratinjau(input);
                            keterangan(tr, `<span class="text-primary">${label}</span>`);
                            terisi++;
                        });

                        toast(terisi ? 'success' : 'warning', terisi
                            ? `✓ ${terisi} isian diambil dari PDDikti. Periksa lalu klik Simpan.`
                            : '⚠ Tidak ada nilai PDDikti yang bisa diisikan untuk mahasiswa ini.');
                    } catch (err) {
                        toast('error', err.message);
                    } finally {
                        this.disabled = false;
                        this.innerHTML = teks;
                    }
                });

                form.addEventListener('input', (e) => { if (e.target.classList.contains('input-nilai')) pratinjau(e.target); });
                form.addEventListener('change', (e) => {
                    if (!e.target.classList.contains('centang-krs')) return;
                    const tr = e.target.closest('tr');
                    tr.querySelectorAll('.pilih-periode, .input-nilai').forEach(el => {
                        el.disabled = !e.target.checked;
                        if (el.classList.contains('pilih-periode')) el.required = e.target.checked;
                    });
                });

                form.addEventListener('submit', (e) => {
                    const adaNilai = [...form.querySelectorAll('#isi-tabel-nilai .input-nilai, #isi-tabel-tidak-lulus .input-nilai')].some(i => i.value !== '');
                    const adaKrs = form.querySelectorAll('.centang-krs:checked').length > 0;
                    e.preventDefault();
                    if (!form.checkValidity() || (!adaNilai && !adaKrs)) {
                        form.reportValidity();
                        toast('warning', '⚠ Periksa kembali isian form Anda.');
                        return;
                    }
                    simpan();
                });

                // Simpan lewat AJAX; jika berhasil, tutup jendela lalu perbarui tabel di tempat
                async function simpan() {
                    const btn = form.querySelector('button[type="submit"]');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
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
                        if (res.message) toast('success', res.message);
                        if (res.error) toast(res.error.startsWith('⚠') ? 'warning' : 'error', res.error);
                        if (!res.message && !res.error) toast('error', pesanGalat(resp, res, 'menyimpan data'));
                        if (res.success) {
                            modal.hide();
                            await muatUlang();
                        }
                    } catch (err) {
                        toast('error', '✕ Gagal menyimpan data. Periksa koneksi Anda.');
                    } finally {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bx bx-save me-1"></i>Simpan';
                    }
                }
            })();
        @endif

        @if ($galat_feeder && str_starts_with($galat_feeder, 'Gagal'))
            Lobibox.notify('error', {
                size: 'mini', rounded: true, delay: 3000, icon: 'bx bx-x-circle', delayIndicator: false,
                position: 'bottom right', msg: '✕ Gagal memuat data. Periksa koneksi Anda.', sound: false,
            });
        @endif
    </script>
@endpush
