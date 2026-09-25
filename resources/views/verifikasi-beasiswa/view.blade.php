@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Verifikasi Penerima Beasiswa</h6>
            <small class="text-muted">Centang mahasiswa yang diverifikasi sebagai penerima beasiswa pada semester terpilih
                agar dapat melakukan kontrak KRS.</small>
        </div>
        <div class="card-body">
            {{-- Toolbar: filter (kiri) & aksi massal (kanan) --}}
            <div class="d-flex flex-wrap align-items-end gap-2 mb-3">
                <form method="GET" action="{{ route($modul . '.index') }}" id="form-filter"
                    class="d-flex flex-wrap align-items-end gap-2">
                    <div>
                        <label for="semester" class="form-label small text-muted mb-1">Tahun Akademik</label>
                        <select name="semester" id="semester" class="form-select form-select-sm" style="min-width:210px">
                            @foreach ($tahunAkademik as $ta)
                                <option value="{{ $ta->kode_tahun_akademik }}" @selected($ta->kode_tahun_akademik == $semester)>
                                    {{ $ta->kode_tahun_akademik }} - {{ $ta->nama_tahun_akademik }}
                                    {{ $ta->status == 'A' ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="lembaga" class="form-label small text-muted mb-1">Beasiswa</label>
                        <select name="lembaga" id="lembaga" class="form-select form-select-sm" style="min-width:240px">
                            <option value="">Semua Beasiswa</option>
                            @foreach ($lembaga as $l)
                                <option value="{{ $l->id }}" @selected($l->id == $idLembaga)>
                                    {{ $l->nama_beasiswa }} ({{ $l->nama_lembaga }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <noscript><button type="submit" class="btn btn-sm btn-primary">Tampilkan</button></noscript>
                </form>

                @if ($data->isNotEmpty())
                    <div class="ms-auto btn-group btn-group-sm" role="group" aria-label="Aksi massal">
                        <button type="button" class="btn btn-success" id="btn-verif-semua"
                            title="Verifikasi semua baris sesuai filter & pencarian">
                            <i class="bx bx-check-double me-1"></i>Verifikasi Semua
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btn-batal-semua"
                            title="Batalkan verifikasi semua baris sesuai filter & pencarian">
                            <i class="bx bx-undo me-1"></i>Batalkan Semua
                        </button>
                    </div>
                @endif
            </div>

            {{-- Ringkasan progres verifikasi --}}
            @php $persen = $data->count() ? round($totalTerverifikasi / $data->count() * 100) : 0; @endphp
            <div class="border rounded p-2 px-3 mb-3">
                <div class="d-flex flex-wrap align-items-center gap-3 small">
                    <span><span class="text-muted">Total</span> <b id="total">{{ $data->count() }}</b></span>
                    <span><i class="bx bxs-circle text-success"></i> <span class="text-muted">Terverifikasi</span>
                        <b id="total-verif">{{ $totalTerverifikasi }}</b></span>
                    <span><i class="bx bxs-circle text-secondary"></i> <span class="text-muted">Belum</span>
                        <b id="total-belum">{{ $data->count() - $totalTerverifikasi }}</b></span>
                    <span class="ms-auto text-muted"><span id="persen">{{ $persen }}</span>%</span>
                </div>
                <div class="progress mt-2" style="height:6px">
                    <div class="progress-bar bg-success" id="bar-progres" role="progressbar" style="width: {{ $persen }}%"
                        aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
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
                const persen = semua.length ? Math.round(verif / semua.length * 100) : 0;
                $('#total-verif').text(verif);
                $('#total-belum').text(semua.length - verif);
                $('#persen').text(persen);
                $('#bar-progres').css('width', persen + '%').attr('aria-valuenow', persen);
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

            // Filter langsung diterapkan saat pilihan berubah
            $('#semester, #lembaga').on('change', () => $('#form-filter').trigger('submit'));

            $('#btn-verif-semua').on('click', () => aksiMassal(true));
            $('#btn-batal-semua').on('click', () => aksiMassal(false));
        });
    </script>
@endpush
