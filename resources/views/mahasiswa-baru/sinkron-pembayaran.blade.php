@extends('layouts.app')
@section('content')
    @php
        $rp = fn($n) => 'Rp' . number_format((float) $n, 0, ',', '.');
        // Rincian tagihan JSON -> daftar [nama, nominal] untuk ditampilkan
        $rincian = fn($json) => collect(is_string($json) ? json_decode($json, true) : $json)
            ->map(fn($i) => [$i['nama_bipot'] ?? 'Bipot ' . ($i['id_bipot'] ?? '-'), $i['nominal'] ?? 0]);
        $badgeStatus = [
            'siap' => 'bg-primary-subtle text-primary-emphasis',
            'sudah' => 'bg-success-subtle text-success-emphasis',
            'belum_bayar' => 'bg-secondary-subtle text-secondary-emphasis',
        ];
        $bisaProses = auth()->user()->can('mahasiswa-baru.sinkron-pembayaran.proses');
        $adaSiap = $ringkasan['siap'] > 0 && $bisaProses;
    @endphp
    <div class="card">
        <div class="card-header py-3">
            <h6 class="mb-0">Sinkron Pembayaran</h6>
            <small class="text-muted">Memindahkan pembayaran daftar ulang dari tagihan nomor pendaftaran PMB ke tagihan NIM
                lewat API tagihan: nominal terbayar NIM ditambah pembayaran PMB, sisa tagihan dihitung ulang. Periksa dulu hasilnya, lalu sinkronkan.</small>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route($modul) }}" class="filter-panel border rounded-3 p-3 mb-3">
                <div class="d-flex align-items-center mb-3">
                    <span class="filter-ikon me-2"><i class='bx bx-search-alt'></i></span>
                    <div>
                        <div class="fw-semibold">Cari Berdasarkan NIM</div>
                        <small class="text-muted">Maksimal {{ $maks_nim }} NIM, pisahkan dengan baris baru, koma, atau
                            spasi</small>
                    </div>
                </div>
                <label class="form-label small fw-semibold text-muted mb-1" for="f-nim">
                    <i class='bx bx-id-card'></i> NIM</label>
                <textarea name="nim" id="f-nim" rows="3" class="form-control font-monospace" required
                    placeholder="S12654001&#10;S12654002">{{ $input_nim }}</textarea>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class='bx bx-search'></i> Periksa Tagihan
                    </button>
                    @if ($input_nim !== '')
                        <a href="{{ route($modul) }}" class="btn btn-outline-secondary btn-sm"><i class='bx bx-reset'></i>
                            Kosongkan</a>
                    @endif
                </div>
            </form>

            @if ($daftar_nim && $hasil->isNotEmpty())
                <div class="row g-3 mb-4">
                    <div class="col-6 col-lg-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="text-muted small">NIM diperiksa</div>
                            <div class="fs-4 fw-bold">{{ $ringkasan['total'] }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="text-muted small">Siap disinkron</div>
                            <div class="fs-4 fw-bold text-primary">{{ $ringkasan['siap'] }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="text-muted small">Sudah disinkron</div>
                            <div class="fs-4 fw-bold text-success">{{ $ringkasan['sudah'] }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="text-muted small">Tidak bisa disinkron</div>
                            <div class="fs-4 fw-bold text-danger">{{ $ringkasan['lainnya'] }}</div>
                        </div>
                    </div>
                </div>

                @if ($adaSiap)
                    <form method="POST" action="{{ route('mahasiswa-baru.sinkron-pembayaran.proses') }}" id="form-sinkron"
                        class="d-flex flex-wrap align-items-center gap-2 border rounded-3 bg-light px-3 py-2 mb-3">
                        @csrf
                        <input type="hidden" name="kembali_nim" value="{{ $input_nim }}">
                        <span class="small"><strong id="jumlah-dicentang">0</strong> NIM dicentang</span>
                        <button type="button" class="btn btn-link btn-sm p-0 ms-2" id="pilih-semua">Centang semua</button>
                        <button type="submit" class="btn btn-primary btn-sm ms-auto" id="btn-sinkron" disabled>
                            <i class='bx bx-transfer'></i> Sinkronkan
                        </button>
                    </form>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="small text-muted">
                            <tr>
                                @if ($adaSiap)
                                    <th style="width:36px" class="text-center">
                                        <input type="checkbox" class="form-check-input" id="cek-semua"
                                            aria-label="Centang semua yang siap">
                                    </th>
                                @endif
                                <th>Mahasiswa</th>
                                <th>Tagihan PMB (sumber)</th>
                                <th>Tagihan NIM (tujuan)</th>
                                <th>Rincian</th>
                                <th>Setelah Sinkron</th>
                                <th>Status</th>
                                @if ($adaSiap)
                                    <th class="text-end">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hasil as $h)
                                <tr>
                                    @if ($adaSiap)
                                        <td class="text-center">
                                            @if ($h['bisa_sinkron'])
                                                <input type="checkbox" class="form-check-input cek-nim"
                                                    value="{{ $h['nim'] }}" aria-label="Pilih {{ $h['nim'] }}">
                                            @endif
                                        </td>
                                    @endif
                                    <td>
                                        <div class="fw-semibold">{{ $h['nim'] }}</div>
                                        <small class="text-muted d-block">{{ $h['nama'] ?? '-' }}</small>
                                        <small class="text-muted">{{ $h['nomor_pendaftaran'] ?? '-' }}</small>
                                    </td>
                                    <td class="small">
                                        @if ($h['sumber'])
                                            <div>TA {{ $h['sumber']['tahun_akademik'] }} · #{{ $h['sumber']['id'] }}</div>
                                            <div>Total {{ $rp($h['sumber']['total_tagihan']) }}</div>
                                            <div class="fw-semibold text-success">Terbayar
                                                {{ $rp($h['sumber']['nominal_terbayar']) }}</div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if ($h['tujuan'])
                                            <div>TA {{ $h['tujuan']['tahun_akademik'] }} · #{{ $h['tujuan']['id'] }}</div>
                                            <div>Total {{ $rp($h['tujuan']['total_tagihan']) }}</div>
                                            <div>Ditagih {{ $rp($h['tujuan']['nominal_ditagih']) }}</div>
                                            <div>Terbayar {{ $rp($h['tujuan']['nominal_terbayar']) }}</div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if (is_null($h['cocok']))
                                            <span class="text-muted">-</span>
                                        @else
                                            @if ($h['cocok'])
                                                <span class="badge bg-success-subtle text-success-emphasis">Cocok</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning-emphasis"
                                                    title="Rincian & potongan tagihan NIM akan diganti dengan rincian tagihan PMB">Tidak
                                                    cocok</span>
                                            @endif
                                            <details class="mt-1">
                                                <summary class="text-primary">Lihat rincian</summary>
                                                <div class="row g-2 mt-1 rincian">
                                                    @foreach (['PMB' => $h['sumber'], 'NIM' => $h['tujuan']] as $label => $t)
                                                        <div class="col-12">
                                                            <div class="fw-semibold">{{ $label }}</div>
                                                            @foreach ($rincian($t['detail_tagihan']) as [$nama, $nominal])
                                                                <div class="d-flex justify-content-between gap-2">
                                                                    <span>{{ $nama }}</span><span
                                                                        class="text-nowrap">{{ $rp($nominal) }}</span>
                                                                </div>
                                                            @endforeach
                                                            @if ((float) $t['total_potongan'] > 0)
                                                                <div class="d-flex justify-content-between gap-2 text-danger">
                                                                    <span>Potongan</span><span
                                                                        class="text-nowrap">-{{ $rp($t['total_potongan']) }}</span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if ($h['bisa_sinkron'])
                                            <div>Terbayar <strong>{{ $rp($h['terbayar_baru']) }}</strong></div>
                                            <div>Ditagih <strong>{{ $rp($h['ditagih_baru']) }}</strong></div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span
                                            class="badge {{ $badgeStatus[$h['status']] ?? 'bg-danger-subtle text-danger-emphasis' }} text-wrap text-start">{{ $h['keterangan'] }}</span>
                                    </td>
                                    @if ($adaSiap)
                                        <td class="text-end text-nowrap">
                                            @if ($h['bisa_sinkron'])
                                                <form action="{{ route('mahasiswa-baru.sinkron-pembayaran.proses') }}"
                                                    method="POST" class="d-inline form-satu"
                                                    data-pesan="Pindahkan pembayaran {{ $rp($h['nominal_dipindahkan']) }} dari {{ $h['nomor_pendaftaran'] }} ke tagihan {{ $h['nim'] }}?{{ $h['cocok'] ? '' : ' Rincian tagihan NIM akan diganti dengan rincian tagihan PMB.' }}">
                                                    @csrf
                                                    <input type="hidden" name="nim[]" value="{{ $h['nim'] }}">
                                                    <input type="hidden" name="kembali_nim" value="{{ $input_nim }}">
                                                    <button type="submit" class="btn btn-outline-primary btn-sm">
                                                        <i class="bx bx-transfer me-1"></i>Sinkronkan
                                                    </button>
                                                </form>
                                            @else
                                                <span class="small text-muted">-</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif (!$daftar_nim)
                <div class="text-center text-muted border rounded-3 py-5">
                    <i class='bx bx-transfer fs-1 d-block mb-2'></i>
                    Masukkan NIM di atas untuk memeriksa tagihan PMB dan tagihan NIM-nya.
                </div>
            @endif
        </div>
    </div>

@endsection
@push('css')
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

        details summary {
            cursor: pointer;
        }

        .rincian {
            min-width: 240px;
        }
    </style>
@endpush
@push('js')
    <script>
        $(function() {
            const dipilih = new Set();

            function perbaruiCeklis() {
                const semua = $('.cek-nim');
                $('#jumlah-dicentang').text(dipilih.size);
                $('#btn-sinkron').prop('disabled', dipilih.size === 0);
                $('#cek-semua').prop('checked', semua.length > 0 && dipilih.size === semua.length)
                    .prop('indeterminate', dipilih.size > 0 && dipilih.size < semua.length);
            }

            $('.cek-nim').on('change', function() {
                this.checked ? dipilih.add(this.value) : dipilih.delete(this.value);
                perbaruiCeklis();
            });
            $('#cek-semua, #pilih-semua').on('click', function() {
                const centang = this.id === 'pilih-semua' ? true : this.checked;
                $('.cek-nim').each(function() {
                    this.checked = centang;
                    centang ? dipilih.add(this.value) : dipilih.delete(this.value);
                });
                perbaruiCeklis();
            });

            $('#form-sinkron').on('submit', function(e) {
                if (!dipilih.size || !confirm(`Sinkronkan pembayaran ${dipilih.size} NIM?\n\n` +
                        'Pembayaran PMB ditambahkan ke tagihan NIM dan tidak bisa dibatalkan dari halaman ini.')) {
                    e.preventDefault();
                    return;
                }
                $(this).find('input[name="nim[]"]').remove();
                dipilih.forEach(n => $(this).append($('<input type="hidden" name="nim[]">').val(n)));
                $('#btn-sinkron').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');
            });

            $('.form-satu').on('submit', function(e) {
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
