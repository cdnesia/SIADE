@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2 py-3">
            <div>
                <h6 class="mb-0">Sinkron Mahasiswa dari PMB</h6>
                <small class="text-muted">Pratinjau pendaftar yang sudah memiliki NIM sebelum dimasukkan ke data
                    mahasiswa.</small>
            </div>
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
                        <option value="belum" {{ ($filter['status'] ?? '') == 'belum' ? 'selected' : '' }}>Belum di master
                        </option>
                        <option value="sudah" {{ ($filter['status'] ?? '') == 'sudah' ? 'selected' : '' }}>Sudah di master
                        </option>
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
                <div class="col-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-muted small">Total ber-NIM</div>
                        <div class="fs-4 fw-bold">{{ $ringkasan['total'] }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-muted small">Belum di master</div>
                        <div class="fs-4 fw-bold text-warning">{{ $ringkasan['belum'] }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-muted small">Sudah di master</div>
                        <div class="fs-4 fw-bold text-success">{{ $ringkasan['di_master'] }}</div>
                    </div>
                </div>
            </div>

            @if ($pendaftar->isEmpty())
                <div class="text-center text-muted border rounded-3 py-5">
                    <i class='bx bx-user-x fs-1 d-block mb-2'></i>
                    Tidak ada pendaftar ber-NIM yang sesuai filter.
                    @if (array_filter($filter))
                        <div class="mt-2"><a href="{{ route($modul, ['tahun' => $tahun]) }}">Reset filter</a></div>
                    @endif
                </div>
            @else
                @can('mahasiswa.sync.import')
                    <form method="POST" action="{{ route('mahasiswa.sync.import') }}" id="form-import"
                        class="d-flex flex-wrap align-items-center gap-2 border rounded-3 bg-light px-3 py-2 mb-3">
                        @csrf
                        <span class="small"><strong id="jumlah-dicentang">0</strong> mahasiswa dicentang</span>
                        <button type="button" class="btn btn-link btn-sm p-0 ms-2" id="pilih-semua-belum">Centang semua yang
                            belum di master</button>
                        <button type="button" class="btn btn-link btn-sm p-0 text-secondary d-none" id="batal-pilih">Batalkan
                            pilihan</button>
                        <button type="submit" class="btn btn-primary btn-sm ms-auto" id="btn-import" disabled>
                            <i class='bx bx-import'></i> Masukkan ke Master
                        </button>
                    </form>
                @endcan
                <div class="table-responsive">
                    <table class="table table-hover align-middle example" style="width:100%">
                        <thead class="small text-muted">
                            <tr>
                                @can('mahasiswa.sync.import')
                                    <th style="width:36px" class="text-center">
                                        <input type="checkbox" class="form-check-input" id="cek-halaman"
                                            aria-label="Centang semua di halaman ini">
                                    </th>
                                @endcan
                                <th>No</th>
                                <th>NIM</th>
                                <th>Nama</th>
                                <th>Program Studi</th>
                                <th>Kelas</th>
                                <th>Jurusan Sekolah</th>
                                <th>Status</th>
                                @canany(['mahasiswa.sync.update', 'mahasiswa.sync.generate-nim'])
                                    <th class="text-end">Aksi</th>
                                @endcanany
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendaftar as $item)
                                <tr>
                                    @can('mahasiswa.sync.import')
                                        <td class="text-center">
                                            @if (!$item->di_master)
                                                <input type="checkbox" class="form-check-input cek-mhs"
                                                    value="{{ $item->nomor }}" aria-label="Pilih {{ $item->nama_daftar }}">
                                            @endif
                                        </td>
                                    @endcan
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold text-nowrap">{{ $item->nim }}</td>
                                    <td>
                                        {{ $item->nama_daftar }}
                                        <div class="small text-muted">{{ $item->pmb }} &middot; {{ $item->nama_jalur }}</div>
                                    </td>
                                    <td>{{ $prodi[$item->prodi]->nama ?? ($nama_prodi_lokal[$item->prodi] ?? $item->prodi) }}</td>
                                    <td class="text-nowrap">{{ $item->nama_kelas ?: '-' }}</td>
                                    <td>{{ $item->jurusan ?: '-' }}</td>
                                    <td class="text-nowrap">
                                        @if ($item->di_master)
                                            <span class="badge bg-success-subtle text-success-emphasis">Sudah di master</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning-emphasis">Belum di master</span>
                                        @endif
                                    </td>
                                    @canany(['mahasiswa.sync.update', 'mahasiswa.sync.generate-nim'])
                                        <td class="text-end text-nowrap">
                                            @if ($item->di_master)
                                                <span class="small text-muted" title="NIM sudah dipakai di master mahasiswa">Terkunci</span>
                                            @else
                                                @can('mahasiswa.sync.update')
                                                    <button type="button" class="btn btn-warning btn-sm btn-edit"
                                                        title="Edit kelas, prodi, jurusan" data-bs-toggle="modal"
                                                        data-bs-target="#modal-edit"
                                                        data-url="{{ route('mahasiswa.sync.update', $item->nomor_pmb) }}"
                                                        data-nama="{{ $item->nama_daftar }}" data-nim="{{ $item->nim }}"
                                                        data-prodi="{{ $item->prodi }}" data-kelas="{{ $item->kelas }}"
                                                        data-jurusan="{{ $item->jurusan }}">
                                                        <i class='bx bx-edit me-0'></i>
                                                    </button>
                                                @endcan
                                                @can('mahasiswa.sync.generate-nim')
                                                    <form action="{{ route('mahasiswa.sync.generate-nim', $item->nomor_pmb) }}"
                                                        method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-primary btn-sm"
                                                            title="Generate NIM ulang"
                                                            onclick="return confirm('Generate NIM baru untuk {{ addslashes($item->nama_daftar) }}? NIM {{ $item->nim }} akan diganti.')">
                                                            <i class='bx bx-refresh me-0'></i> NIM
                                                        </button>
                                                    </form>
                                                @endcan
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

    @can('mahasiswa.sync.update')
        <div class="modal fade" id="modal-edit" tabindex="-1" aria-labelledby="modal-edit-judul" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" id="form-edit" class="modal-content">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <div>
                            <h6 class="modal-title mb-0" id="modal-edit-judul">Edit Data Pendaftar</h6>
                            <small class="text-muted" id="edit-info"></small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <div class="col-12">
                            <label class="form-label" for="edit-prodi">Program Studi</label>
                            <select name="prodi" id="edit-prodi" class="form-select" required>
                                @foreach ($prodi as $p)
                                    <option value="{{ $p->kode }}">{{ $p->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="edit-kelas">Kelas</label>
                            <select name="kelas" id="edit-kelas" class="form-select" required>
                                @foreach ($kelas as $k)
                                    <option value="{{ $k->id }}" data-prodi="{{ $k->list_prodi }}">{{ $k->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="edit-jurusan">Jurusan Sekolah</label>
                            <select name="jurusan" id="edit-jurusan" class="form-select">
                                <option value="">-</option>
                                @foreach ($jurusan as $j)
                                    <option value="{{ $j }}">{{ $j }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="generate_nim" value="1"
                                    id="edit-generate">
                                <label class="form-check-label" for="edit-generate">Generate NIM ulang</label>
                            </div>
                            <div class="alert alert-warning small py-2 mt-2 mb-0 d-none" id="edit-peringatan">
                                <i class='bx bx-info-circle'></i> Prodi berubah, NIM akan dibuat ulang otomatis sesuai kode
                                prodi baru.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm"><i class='bx bx-save'></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
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
                $('#btn-import').prop('disabled', dipilih.size === 0);
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
            $('#pilih-semua-belum').on('click', function() {
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

            $('#form-import').on('submit', function(e) {
                if (!dipilih.size || !confirm(`Masukkan ${dipilih.size} mahasiswa ke master mahasiswa?\n\n` +
                        'va_code akan kosong dan dosen PA belum diisi, lengkapi setelahnya.')) {
                    e.preventDefault();
                    return;
                }
                $(this).find('input[name="nomor[]"]').remove();
                dipilih.forEach(n => $(this).append(`<input type="hidden" name="nomor[]" value="${n}">`));
                $('#btn-import').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');
            });

            let prodiAwal = null;

            // Kelas hanya yang dibuka untuk prodi terpilih (list_prodi kosong = semua prodi)
            function saringKelas() {
                const prodi = $('#edit-prodi').val();
                $('#edit-kelas option').each(function() {
                    const daftar = $(this).data('prodi') || '';
                    $(this).prop('disabled', daftar !== '' && !daftar.includes('.' + prodi + '.'));
                });
                if ($('#edit-kelas option:selected').prop('disabled')) {
                    $('#edit-kelas').val($('#edit-kelas option:not(:disabled)').first().val());
                }
            }

            function cekPerubahanProdi() {
                const berubah = $('#edit-prodi').val() !== prodiAwal;
                $('#edit-peringatan').toggleClass('d-none', !berubah);
                $('#edit-generate').prop('checked', berubah || $('#edit-generate').data('manual') === true)
                    .prop('disabled', berubah);
            }

            // Isi modal dari tombol edit yang diklik (event delegation agar tetap jalan setelah paging DataTables)
            $(document).on('click', '.btn-edit', function() {
                const b = $(this).data();
                prodiAwal = String(b.prodi);
                $('#form-edit').attr('action', b.url);
                $('#edit-info').text(`${b.nim} · ${b.nama}`);
                $('#edit-prodi').val(prodiAwal);
                $('#edit-kelas').val(String(b.kelas));
                const jurusan = b.jurusan ? String(b.jurusan) : '';
                if (jurusan && !$(`#edit-jurusan option[value="${jurusan}"]`).length) {
                    $('#edit-jurusan').append(new Option(jurusan, jurusan));
                }
                $('#edit-jurusan').val(jurusan);
                $('#edit-generate').data('manual', false).prop('checked', false);
                saringKelas();
                cekPerubahanProdi();
            });

            $('#edit-prodi').on('change', function() {
                saringKelas();
                cekPerubahanProdi();
            });
            $('#edit-generate').on('change', function() {
                $(this).data('manual', this.checked);
            });

            // Checkbox disabled tidak ikut terkirim; prodi berubah tetap memicu generate di server
            $('#form-edit').on('submit', function() {
                $('#edit-generate').prop('disabled', false);
            });
        });
    </script>
@endpush
