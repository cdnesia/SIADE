@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header py-3">
            <h6 class="mb-0">Pencarian Pendaftar</h6>
            <small class="text-muted">Pendaftar PMB yang belum memiliki NIM, tanpa memperhatikan status pembayaran. NPM
                dapat diterbitkan per pendaftar atau sekaligus untuk yang dicentang.</small>
        </div>
        <div class="card-body">
            @php
                // Label filter aktif untuk ditampilkan sebagai chip yang bisa dihapus satu per satu
                $filterAktif = collect([
                    'prodi' => ['bx-book-open', isset($filter['prodi']) ? $prodi[$filter['prodi']]->nama ?? $filter['prodi'] : null],
                    'kelas' => ['bx-chalkboard', isset($filter['kelas']) ? $kelas[$filter['kelas']]->nama ?? $filter['kelas'] : null],
                    'jalur' => ['bx-directions', isset($filter['jalur']) ? $jalur->firstWhere('id', $filter['jalur'])->nama ?? $filter['jalur'] : null],
                ])->filter(fn($f) => filled($f[1]));
            @endphp
            <form method="GET" action="{{ route($modul) }}" id="form-filter" class="filter-panel border rounded-3 p-3 mb-3">
                <div class="d-flex align-items-center mb-3">
                    <span class="filter-ikon me-2"><i class='bx bx-filter-alt'></i></span>
                    <div>
                        <div class="fw-semibold">Filter Pendaftar</div>
                        <small class="text-muted">Pilihan langsung diterapkan</small>
                    </div>
                    @if ($filterAktif->isNotEmpty())
                        <a href="{{ route($modul, ['tahun' => $tahun]) }}" class="btn btn-sm btn-outline-secondary ms-auto">
                            <i class='bx bx-reset'></i> Reset
                        </a>
                    @endif
                </div>
                <div class="row g-3">
                    <div class="col-6 col-lg-2">
                        <label class="form-label small fw-semibold text-muted mb-1" for="f-tahun">
                            <i class='bx bx-calendar'></i> Tahun PMB</label>
                        <select name="tahun" id="f-tahun" class="form-select filter-otomatis">
                            @foreach ($daftar_tahun as $t)
                                <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>{{ substr($t, 4) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label small fw-semibold text-muted mb-1" for="f-kelas">
                            <i class='bx bx-chalkboard'></i> Kelas</label>
                        <select name="kelas" id="f-kelas" class="form-select filter-otomatis">
                            <option value="">Semua kelas</option>
                            @foreach ($kelas as $k)
                                <option value="{{ $k->id }}" {{ ($filter['kelas'] ?? '') == $k->id ? 'selected' : '' }}>
                                    {{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label small fw-semibold text-muted mb-1" for="f-prodi">
                            <i class='bx bx-book-open'></i> Program Studi</label>
                        <select name="prodi" id="f-prodi" class="form-select filter-otomatis filter-cari"
                            data-placeholder="Semua prodi">
                            <option value=""></option>
                            @foreach ($prodi as $p)
                                <option value="{{ $p->kode }}" {{ ($filter['prodi'] ?? '') == $p->kode ? 'selected' : '' }}>
                                    {{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label small fw-semibold text-muted mb-1" for="f-jalur">
                            <i class='bx bx-directions'></i> Jalur Masuk</label>
                        <select name="jalur" id="f-jalur" class="form-select filter-otomatis filter-cari"
                            data-placeholder="Semua jalur">
                            <option value=""></option>
                            @foreach ($jalur as $j)
                                <option value="{{ $j->id }}" data-jumlah="{{ $j->jumlah }}"
                                    {{ ($filter['jalur'] ?? '') == $j->id ? 'selected' : '' }}>{{ $j->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 border-top mt-3 pt-3">
                    <span class="small text-muted me-1">
                        Menampilkan <strong class="text-body">{{ $pendaftar->count() }}</strong> dari
                        {{ $ringkasan['total'] }} pendaftar
                    </span>
                    @foreach ($filterAktif as $kunci => [$ikon, $label])
                        <a href="{{ route($modul, array_filter(['tahun' => $tahun] + \Illuminate\Support\Arr::except($filter, $kunci))) }}"
                            class="filter-chip" title="Hapus filter ini">
                            <i class='bx {{ $ikon }}'></i> {{ $label }} <i class='bx bx-x'></i>
                        </a>
                    @endforeach
                </div>
            </form>

            <div class="row g-3 mb-4">
                <div class="col-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-muted small">Belum ada NIM</div>
                        <div class="fs-4 fw-bold">{{ $ringkasan['total'] }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-muted small">Siap generate NPM</div>
                        <div class="fs-4 fw-bold text-primary">{{ $ringkasan['bisa_generate'] }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-muted small">Prodi belum dipilih</div>
                        <div class="fs-4 fw-bold text-danger">{{ $ringkasan['tanpa_prodi'] }}</div>
                    </div>
                </div>
            </div>

            @if ($pendaftar->isEmpty())
                <div class="text-center text-muted border rounded-3 py-5">
                    <i class='bx bx-user-x fs-1 d-block mb-2'></i>
                    Tidak ada pendaftar tanpa NIM yang sesuai filter.
                    @if (array_filter($filter))
                        <div class="mt-2"><a href="{{ route($modul, ['tahun' => $tahun]) }}">Reset filter</a></div>
                    @endif
                </div>
            @else
                @can('mahasiswa-baru.pencarian.generate-nim')
                    <form method="POST" action="{{ route('mahasiswa-baru.pencarian.generate-nim') }}" id="form-generate"
                        class="d-flex flex-wrap align-items-center gap-2 border rounded-3 bg-light px-3 py-2 mb-3">
                        @csrf
                        <span class="small"><strong id="jumlah-dicentang">0</strong> pendaftar dicentang</span>
                        <button type="button" class="btn btn-link btn-sm p-0 ms-2" id="pilih-semua">Centang semua</button>
                        <button type="button" class="btn btn-link btn-sm p-0 text-secondary d-none" id="batal-pilih">Batalkan
                            pilihan</button>
                        <button type="submit" class="btn btn-primary btn-sm ms-auto" id="btn-generate" disabled>
                            <i class='bx bx-id-card'></i> Generate NPM
                        </button>
                    </form>
                @endcan
                <div class="table-responsive">
                    <table class="table table-hover align-middle example" style="width:100%">
                        <thead class="small text-muted">
                            <tr>
                                @can('mahasiswa-baru.pencarian.generate-nim')
                                    <th style="width:36px" class="text-center">
                                        <input type="checkbox" class="form-check-input" id="cek-halaman"
                                            aria-label="Centang semua di halaman ini">
                                    </th>
                                @endcan
                                <th>No</th>
                                <th>No. Pendaftaran</th>
                                <th>Nama</th>
                                <th>Program Studi</th>
                                <th>Kelas</th>
                                <th>Jalur / Gelombang</th>
                                <th>Kontak</th>
                                <th>Status</th>
                                @can('mahasiswa-baru.pencarian.generate-nim')
                                    <th class="text-end">Aksi</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendaftar as $item)
                                <tr>
                                    @can('mahasiswa-baru.pencarian.generate-nim')
                                        <td class="text-center">
                                            @if ($item->bisa_generate)
                                                <input type="checkbox" class="form-check-input cek-mhs"
                                                    value="{{ $item->nomor_pmb }}" aria-label="Pilih {{ $item->nama_daftar }}">
                                            @endif
                                        </td>
                                    @endcan
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-nowrap">{{ $item->pmb }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $item->nama_daftar }}</div>
                                        <small class="text-muted">{{ $item->sekolah_asal ?: '-' }}</small>
                                    </td>
                                    <td>{{ $prodi[$item->prodi]->nama ?? ($item->prodi ? 'Prodi ' . $item->prodi : '-') }}</td>
                                    <td>{{ $item->nama_kelas ?: '-' }}</td>
                                    <td>
                                        <div>{{ $item->nama_jalur ?: '-' }}</div>
                                        <small class="text-muted">{{ $item->gelombang ?: '-' }}</small>
                                    </td>
                                    <td>
                                        <div class="text-nowrap">{{ $item->hp_daftar ?: '-' }}</div>
                                        <small class="text-muted">{{ $item->email ?: '-' }}</small>
                                    </td>
                                    <td class="text-nowrap">
                                        @if ($item->bisa_generate)
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis">Belum ada NIM</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger-emphasis"
                                                title="NPM tidak bisa dibuat sebelum prodi pendaftar diisi">Prodi belum dipilih</span>
                                        @endif
                                    </td>
                                    @can('mahasiswa-baru.pencarian.generate-nim')
                                        <td class="text-end text-nowrap">
                                            @if ($item->bisa_generate)
                                                <form action="{{ route('mahasiswa-baru.pencarian.generate-nim') }}"
                                                    method="POST" class="d-inline form-satu"
                                                    data-pesan="Terbitkan NPM untuk {{ $item->nama_daftar }} ({{ $prodi[$item->prodi]->nama }})? NPM diterbitkan tanpa cek pembayaran.">
                                                    @csrf
                                                    <input type="hidden" name="nomor[]" value="{{ $item->nomor_pmb }}">
                                                    <button type="submit" class="btn btn-outline-primary btn-sm">
                                                        <i class="bx bx-id-card me-1"></i>Generate NPM
                                                    </button>
                                                </form>
                                            @else
                                                <span class="small text-muted">-</span>
                                            @endif
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
@push('css')
    <link href="{{ asset('') }}assets/plugins/datatable/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <style>
        .filter-panel {
            background: var(--bs-tertiary-bg, #f8f9fa);
        }

        .filter-ikon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: .5rem;
            background: rgba(var(--bs-primary-rgb), .1);
            color: var(--bs-primary);
            font-size: 1.25rem;
        }

        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .2rem .6rem;
            border-radius: 50rem;
            font-size: .8rem;
            background: rgba(var(--bs-primary-rgb), .1);
            color: var(--bs-primary);
            text-decoration: none;
        }

        .filter-chip:hover {
            background: rgba(var(--bs-primary-rgb), .2);
            color: var(--bs-primary);
        }

        .filter-chip .bx-x {
            font-size: 1rem;
        }

        .filter-jumlah {
            float: right;
            font-size: .75rem;
            padding: 0 .45rem;
            border-radius: 50rem;
            background: var(--bs-secondary-bg, #e9ecef);
            color: var(--bs-secondary-color, #6c757d);
        }
    </style>
@endpush
@push('js')
    <script src="{{ asset('') }}assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('') }}assets/plugins/datatable/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function() {
            // Prodi & jalur bisa dicari; jumlah pendaftar per jalur tampil di sisi kanan opsi
            $('.filter-cari').each(function() {
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    allowClear: true,
                    placeholder: $(this).data('placeholder'),
                    templateResult: function(opsi) {
                        const jumlah = $(opsi.element).data('jumlah');
                        return jumlah ? $('<span>').text(opsi.text).append($('<span class="filter-jumlah">').text(jumlah)) : opsi.text;
                    },
                });
            });
            $('.filter-otomatis').on('change', function() {
                $('#form-filter').trigger('submit');
            });

            const adaCeklis = $('#cek-halaman').length > 0;
            const tabel = $('.example').DataTable({
                lengthChange: false,
                order: [[adaCeklis ? 1 : 0, 'asc']],
                columnDefs: adaCeklis ? [{ targets: 0, orderable: false, searchable: false }] : [],
                pageLength: 50,
                language: {
                    search: 'Cari:',
                    zeroRecords: 'Tidak ada data yang cocok',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '(disaring dari _MAX_ data)',
                    paginate: { previous: '‹', next: '›' },
                },
            });

            // Checklist: dipilih disimpan di Set agar tetap ingat lintas halaman/pencarian DataTables
            const dipilih = new Set();
            const semuaCek = () => $(tabel.rows().nodes()).find('.cek-mhs');

            function perbaruiCeklis() {
                $('#jumlah-dicentang').text(dipilih.size);
                $('#btn-generate').prop('disabled', dipilih.size === 0);
                $('#batal-pilih').toggleClass('d-none', dipilih.size === 0);
                const diHalaman = $(tabel.rows({ page: 'current' }).nodes()).find('.cek-mhs');
                const tercentang = diHalaman.filter(':checked').length;
                $('#cek-halaman').prop('checked', diHalaman.length > 0 && tercentang === diHalaman.length)
                    .prop('indeterminate', tercentang > 0 && tercentang < diHalaman.length);
            }

            $(document).on('change', '.cek-mhs', function() {
                this.checked ? dipilih.add(this.value) : dipilih.delete(this.value);
                perbaruiCeklis();
            });
            $('#cek-halaman').on('change', function() {
                const centang = this.checked;
                $(tabel.rows({ page: 'current' }).nodes()).find('.cek-mhs').each(function() {
                    this.checked = centang;
                    centang ? dipilih.add(this.value) : dipilih.delete(this.value);
                });
                perbaruiCeklis();
            });
            // Mengikuti filter dan kotak pencarian yang sedang aktif
            $('#pilih-semua').on('click', function() {
                $(tabel.rows({ search: 'applied' }).nodes()).find('.cek-mhs').each(function() {
                    this.checked = true;
                    dipilih.add(this.value);
                });
                perbaruiCeklis();
            });
            $('#batal-pilih').on('click', function() {
                semuaCek().prop('checked', false);
                dipilih.clear();
                perbaruiCeklis();
            });
            tabel.on('draw', perbaruiCeklis);

            $('#form-generate').on('submit', function(e) {
                if (!dipilih.size || !confirm(`Terbitkan NPM untuk ${dipilih.size} pendaftar?\n\n` +
                        'NPM diterbitkan tanpa cek pembayaran dan tidak bisa dibatalkan dari halaman ini.')) {
                    e.preventDefault();
                    return;
                }
                $(this).find('input[name="nomor[]"]').remove();
                dipilih.forEach(n => $(this).append(`<input type="hidden" name="nomor[]" value="${n}">`));
                $('#btn-generate').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');
            });

            // Aksi per baris (event delegation agar tetap jalan setelah paging DataTables)
            $(document).on('submit', '.form-satu', function(e) {
                if (!confirm(this.dataset.pesan)) {
                    e.preventDefault();
                    return;
                }
                $(this).find('button').prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm me-1"></span>Memproses...');
            });
        });
    </script>
@endpush
