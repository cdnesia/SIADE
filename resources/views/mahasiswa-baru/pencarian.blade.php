@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header py-3">
            <h6 class="mb-0">Pencarian Pendaftar</h6>
            <small class="text-muted">Seluruh pendaftar PMB tanpa memperhatikan status pembayaran. NPM dapat diterbitkan
                per pendaftar atau sekaligus untuk yang dicentang.</small>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route($modul) }}" class="row g-2 align-items-end mb-3">
                <div class="col-6 col-md-2">
                    <label class="form-label" for="f-tahun">Tahun PMB</label>
                    <select name="tahun" id="f-tahun" class="form-select" onchange="this.form.submit()">
                        @foreach ($daftar_tahun as $t)
                            <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>{{ substr($t, 4) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="f-prodi">Program Studi</label>
                    <select name="prodi" id="f-prodi" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua prodi</option>
                        @foreach ($prodi as $p)
                            <option value="{{ $p->kode }}" {{ ($filter['prodi'] ?? '') == $p->kode ? 'selected' : '' }}>
                                {{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="f-kelas">Kelas</label>
                    <select name="kelas" id="f-kelas" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua kelas</option>
                        @foreach ($kelas as $k)
                            <option value="{{ $k->id }}" {{ ($filter['kelas'] ?? '') == $k->id ? 'selected' : '' }}>
                                {{ $k->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="f-status">Status</label>
                    <select name="status" id="f-status" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua</option>
                        <option value="belum_nim" {{ ($filter['status'] ?? '') == 'belum_nim' ? 'selected' : '' }}>Belum ada
                            NIM</option>
                        <option value="belum_master" {{ ($filter['status'] ?? '') == 'belum_master' ? 'selected' : '' }}>
                            Ber-NIM, belum di master</option>
                        <option value="sudah_master" {{ ($filter['status'] ?? '') == 'sudah_master' ? 'selected' : '' }}>
                            Sudah di master</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    @if (array_filter($filter))
                        <a href="{{ route($modul, ['tahun' => $tahun]) }}" class="btn btn-outline-secondary w-100">
                            <i class='bx bx-reset'></i> Reset Filter
                        </a>
                    @endif
                </div>
            </form>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-muted small">Total pendaftar</div>
                        <div class="fs-4 fw-bold">{{ $ringkasan['total'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-muted small">Belum ada NIM</div>
                        <div class="fs-4 fw-bold text-secondary">{{ $ringkasan['belum_nim'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-muted small">Ber-NIM, belum di master</div>
                        <div class="fs-4 fw-bold text-warning">{{ $ringkasan['belum_master'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-muted small">Sudah di master</div>
                        <div class="fs-4 fw-bold text-success">{{ $ringkasan['sudah_master'] }}</div>
                    </div>
                </div>
            </div>

            @if ($pendaftar->isEmpty())
                <div class="text-center text-muted border rounded-3 py-5">
                    <i class='bx bx-user-x fs-1 d-block mb-2'></i>
                    Tidak ada pendaftar yang sesuai filter.
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
                        <button type="button" class="btn btn-link btn-sm p-0 ms-2" id="pilih-semua">Centang semua yang belum
                            ada NIM</button>
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
                                <th>NIM</th>
                                @canany(['mahasiswa-baru.pencarian.generate-nim', 'mahasiswa.sync.import'])
                                    <th class="text-end">Aksi</th>
                                @endcanany
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
                                        @if ($item->nim)
                                            <div class="fw-bold">{{ $item->nim }}</div>
                                            @if ($item->di_master)
                                                <span class="badge bg-success-subtle text-success-emphasis">Sudah di master</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning-emphasis">Belum di master</span>
                                            @endif
                                        @elseif (!$item->bisa_generate)
                                            <span class="badge bg-danger-subtle text-danger-emphasis"
                                                title="NPM tidak bisa dibuat sebelum prodi pendaftar diisi">Prodi belum dipilih</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis">Belum ada NIM</span>
                                        @endif
                                    </td>
                                    @canany(['mahasiswa-baru.pencarian.generate-nim', 'mahasiswa.sync.import'])
                                        <td class="text-end text-nowrap">
                                            @if ($item->bisa_generate)
                                                @can('mahasiswa-baru.pencarian.generate-nim')
                                                    <form action="{{ route('mahasiswa-baru.pencarian.generate-nim') }}"
                                                        method="POST" class="d-inline form-satu"
                                                        data-pesan="Terbitkan NPM untuk {{ $item->nama_daftar }} ({{ $prodi[$item->prodi]->nama }})? NPM diterbitkan tanpa cek pembayaran.">
                                                        @csrf
                                                        <input type="hidden" name="nomor[]" value="{{ $item->nomor_pmb }}">
                                                        <button type="submit" class="btn btn-outline-primary btn-sm">
                                                            <i class="bx bx-id-card me-1"></i>Generate NPM
                                                        </button>
                                                    </form>
                                                @endcan
                                            @elseif ($item->nim && !$item->di_master)
                                                @can('mahasiswa.sync.import')
                                                    <form action="{{ route('mahasiswa.sync.import') }}" method="POST"
                                                        class="d-inline form-satu"
                                                        data-pesan="Masukkan {{ $item->nama_daftar }} (NIM {{ $item->nim }}) ke master mahasiswa? Akun login juga dibuat dengan username dan password = NIM.">
                                                        @csrf
                                                        <input type="hidden" name="nomor[]" value="{{ $item->nomor }}">
                                                        <button type="submit" class="btn btn-success btn-sm">
                                                            <i class="bx bx-import me-1"></i>Masuk ke Master
                                                        </button>
                                                    </form>
                                                @endcan
                                            @else
                                                <span class="small text-muted">-</span>
                                            @endif
                                        </td>
                                    @endcanany
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
@endpush
@push('js')
    <script src="{{ asset('') }}assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('') }}assets/plugins/datatable/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function() {
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
