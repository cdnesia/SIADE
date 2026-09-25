@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Verifikasi Penerima Beasiswa</h6>
            <small class="text-muted">Centang mahasiswa yang diverifikasi sebagai penerima beasiswa pada semester terpilih
                agar dapat melakukan kontrak KRS.</small>
        </div>
        <div class="card-body">
            {{-- Filter --}}
            <form method="GET" action="{{ route($modul . '.index') }}" class="row g-2 align-items-end mb-3">
                <div class="col-md-4">
                    <label for="semester" class="form-label">Tahun Akademik</label>
                    <select name="semester" id="semester" class="form-select">
                        @foreach ($tahunAkademik as $ta)
                            <option value="{{ $ta->kode_tahun_akademik }}" @selected($ta->kode_tahun_akademik == $semester)>
                                {{ $ta->kode_tahun_akademik }} - {{ $ta->nama_tahun_akademik }}
                                {{ $ta->status == 'A' ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="lembaga" class="form-label">Beasiswa</label>
                    <select name="lembaga" id="lembaga" class="form-select">
                        <option value="">Semua Beasiswa</option>
                        @foreach ($lembaga as $l)
                            <option value="{{ $l->id }}" @selected($l->id == $idLembaga)>
                                {{ $l->nama_beasiswa }} ({{ $l->nama_lembaga }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bx bx-filter-alt me-0"></i> Tampilkan</button>
                </div>
            </form>

            {{-- Ringkasan & aksi massal --}}
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="badge bg-light text-dark border">Total: <span id="total">{{ $data->count() }}</span></span>
                <span class="badge bg-success">Terverifikasi: <span id="total-verif">{{ $totalTerverifikasi }}</span></span>
                <span class="badge bg-secondary">Belum: <span id="total-belum">{{ $data->count() - $totalTerverifikasi }}</span></span>
                @if ($data->isNotEmpty())
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" id="btn-verif-semua">
                            <i class="bx bx-check-double me-0"></i> Verifikasi Semua yang Tampil
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btn-batal-semua">
                            <i class="bx bx-x me-0"></i> Batalkan Semua yang Tampil
                        </button>
                    </div>
                @endif
            </div>

            <div class="table-responsive">
                <table id="tabel-verifikasi" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th width="30px">No</th>
                            <th width="70px" class="text-center">Verifikasi</th>
                            <th>NPM</th>
                            <th>Nama Mahasiswa</th>
                            <th>Program Studi</th>
                            <th>Beasiswa</th>
                            <th>Tanggungan</th>
                            <th>Diverifikasi Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input cek-verifikasi"
                                        style="width:1.3em;height:1.3em;cursor:pointer"
                                        value="{{ $item->npm }}|{{ $item->id_lembaga }}"
                                        aria-label="Verifikasi {{ $item->nama_mahasiswa }}"
                                        @checked($item->terverifikasi)>
                                </td>
                                <td>{{ $item->npm }}</td>
                                <td>{{ $item->nama_mahasiswa }}</td>
                                <td>{{ $item->program_studi }}</td>
                                <td>
                                    {{ $item->nama_beasiswa }}
                                    <br><small class="text-muted">{{ $item->nama_lembaga }}</small>
                                </td>
                                <td>
                                    @if ($item->jenis_tanggungan == 'penuh')
                                        <span class="badge bg-primary">Penuh</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Sebagian</span>
                                        <br><small>Rp {{ number_format($item->jumlah_jaminan, 0, ',', '.') }}</small>
                                    @endif
                                </td>
                                <td class="info-verifikator">
                                    @if ($item->diverifikasi_oleh)
                                        {{ $item->diverifikasi_oleh }}
                                        <br><small class="text-muted">{{ $item->diverifikasi_pada?->format('d/m/Y H:i') }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($data->isEmpty())
                <div class="text-center py-4">
                    <i class="bx bx-info-circle" style="font-size: 36px; color: #6c757d;"></i>
                    <p class="text-muted mt-2 mb-2">Belum ada penerima beasiswa yang terdaftar pada semester ini.</p>
                    <a href="{{ route('penerima-beasiswa.index') }}" class="btn btn-sm btn-outline-primary">Lihat Data Penerima Beasiswa</a>
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
            const semester = @json($semester);
            const urlSimpan = @json(route($modul . '.store'));
            const csrf = $('meta[name="csrf-token"]').attr('content');

            const tabel = $('#tabel-verifikasi').DataTable({
                scrollX: true,
                pageLength: 25,
                columnDefs: [{ orderable: false, targets: 1 }],
            });

            function notif(tipe, pesan) {
                Lobibox.notify(tipe, {
                    pauseDelayOnHover: true,
                    size: 'mini',
                    rounded: true,
                    icon: tipe === 'success' ? 'bx bx-check-circle' : 'bx bx-x-circle',
                    delayIndicator: false,
                    position: 'bottom right',
                    delay: 3000,
                    msg: pesan,
                    sound: false,
                });
            }

            // Hitung ulang ringkasan dari seluruh baris (semua halaman)
            function hitungUlang() {
                const semua = tabel.$('.cek-verifikasi');
                const verif = semua.filter(':checked').length;
                $('#total-verif').text(verif);
                $('#total-belum').text(semua.length - verif);
            }

            // Kirim verifikasi ke server; jika gagal, kembalikan status checkbox
            function simpan($checkbox, status) {
                const items = $checkbox.map((i, el) => el.value).get();
                $checkbox.prop('disabled', true);

                return $.ajax({
                    url: urlSimpan,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf },
                    data: { semester, items, terverifikasi: status ? 1 : 0 },
                }).done(function(res) {
                    $checkbox.prop('checked', status);
                    $checkbox.closest('tr').find('.info-verifikator')
                        .html(`${res.verifikator}<br><small class="text-muted">${res.waktu}</small>`);
                    notif('success', '✓ ' + res.message);
                }).fail(function(xhr) {
                    $checkbox.prop('checked', !status);
                    notif('error', '✕ ' + (xhr.responseJSON?.message ?? 'Gagal memperbarui data. Coba lagi.'));
                }).always(function() {
                    $checkbox.prop('disabled', false);
                    hitungUlang();
                });
            }

            // Ceklis satu per satu
            $('#tabel-verifikasi').on('change', '.cek-verifikasi', function() {
                simpan($(this), this.checked);
            });

            // Aksi massal: semua baris yang lolos filter/pencarian tabel (semua halaman)
            function aksiMassal(status) {
                const target = $(tabel.rows({ search: 'applied' }).nodes())
                    .find('.cek-verifikasi')
                    .filter(status ? ':not(:checked)' : ':checked');

                if (target.length === 0) {
                    notif('info', 'Tidak ada data yang perlu diubah.');
                    return;
                }
                const pesan = status
                    ? `Verifikasi ${target.length} mahasiswa untuk semester ${semester}?`
                    : `Batalkan verifikasi ${target.length} mahasiswa untuk semester ${semester}?`;
                if (confirm(pesan)) {
                    simpan(target, status);
                }
            }

            $('#btn-verif-semua').on('click', () => aksiMassal(true));
            $('#btn-batal-semua').on('click', () => aksiMassal(false));
        });
    </script>
@endpush
