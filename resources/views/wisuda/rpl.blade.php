@extends('layouts.app')

@push('css')
    <style>
        .badge-titik::before { content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: currentColor; margin-right: 5px; vertical-align: middle; }
        .kartu-angka { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
        .angka-masalah { color: var(--bs-danger); font-weight: 600; }
        #tabel-detail tr.tidak-dipakai td { opacity: .5; }
    </style>
@endpush

@section('content')
    @php
        $nimParam = $daftar_nim->implode(',');
        $parameterTetap = array_filter(['prodi' => $prodi, 'angkatan' => $angkatan, 'nim' => $nimParam]);
        $parameterRekap = array_filter($parameterTetap + [
            'keluar' => $keluar !== 'semua' ? $keluar : null,
            'status' => $status !== 'semua' ? $status : null,
            'cari' => $cari,
        ]);
        $labelPeriode = fn ($k) => substr($k, 0, 4) . (substr($k, 4) === '2' ? ' Genap' : (substr($k, 4) === '1' ? ' Ganjil' : ''));
    @endphp

    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-0"><i class="bx bx-transfer-alt me-2"></i>Nilai Konversi Mahasiswa RPL</h5>
            <small class="text-muted">Hanya <strong>nilai hasil konversi RPL</strong> yang diambil dari Neo Feeder. Matakuliah kurikulum lain dibiarkan kosong
                di file export agar bisa diisi manual di Excel. Data hanya dibaca, tidak mengubah SIADE.</small>
        </div>
        <a href="{{ route('wisuda.rekap.index') }}" class="btn btn-sm btn-outline-primary ms-auto">
            <i class="bx bx-group me-1"></i>Cek Per Angkatan
        </a>
    </div>

    {{-- Pilih prodi, periode masuk, NIM --}}
    <div class="card">
        <div class="card-body">
            <form action="{{ route('wisuda.rpl.index') }}" method="get" class="row g-2 align-items-end" id="form-rpl">
                <div class="col-md-5">
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
                <div class="col-md-3">
                    <label for="angkatan" class="form-label">Periode Masuk</label>
                    <select name="angkatan" id="angkatan" class="form-select" @disabled($daftar_angkatan->isEmpty())>
                        <option value="semua">Semua periode masuk{{ $daftar_angkatan->isNotEmpty() ? ' (' . $daftar_angkatan->sum() . ' mhs)' : '' }}</option>
                        @foreach ($daftar_angkatan as $nilai => $jumlah)
                            <option value="{{ $nilai }}" @selected($nilai == $angkatan)>{{ $labelPeriode($nilai) }} ({{ $jumlah }} mhs)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label for="nim" class="form-label mb-1">Saring NIM <span class="text-muted small">(opsional — kosongkan untuk menampilkan semua mahasiswa RPL)</span></label>
                    <textarea name="nim" id="nim" rows="2" class="form-control font-monospace"
                        placeholder="NIM sesuai Neo Feeder, satu per baris atau dipisah koma, contoh: S12460412">{{ $nim_teks }}</textarea>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary"><i class="bx bx-search-alt me-1"></i>Tampilkan</button>
                </div>
                @if ($prodi)
                    <div class="col-auto">
                        <a href="{{ route('wisuda.rpl.index', $parameterRekap + ['segarkan' => 1]) }}" class="btn btn-outline-secondary"
                            title="Ambil ulang data RPL & kurikulum terbaru dari Neo Feeder">
                            <i class="bx bx-refresh me-1"></i>Muat Ulang Feeder
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @if ($galat_feeder)
        <div class="alert alert-warning d-flex align-items-start">
            <i class="bx bx-wifi-off fs-4 me-2"></i>
            <div class="flex-grow-1">{{ $galat_feeder }}</div>
            <a href="{{ route('wisuda.rpl.index', $parameterRekap + ['segarkan' => 1]) }}"
                class="btn btn-sm btn-outline-warning ms-2 text-nowrap"><i class="bx bx-refresh me-1"></i>Coba Lagi</a>
        </div>
    @endif

    @if (!$hasil)
        @if (!$galat_feeder)
            <div class="card">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bx bx-filter-alt" style="font-size: 3rem"></i>
                    <h6 class="mt-2 mb-1">Pilih program studi untuk menampilkan mahasiswa RPL</h6>
                    <p class="small mb-0">Data mahasiswa dan nilai konversi RPL diambil dari Neo Feeder.</p>
                </div>
            </div>
        @endif
    @else
        @if (count($nim_tidak_ada))
            <div class="alert alert-warning small">
                <i class="bx bx-error me-1"></i><strong>{{ count($nim_tidak_ada) }} NIM bukan mahasiswa RPL prodi/periode ini di Neo Feeder:</strong>
                @foreach ($nim_tidak_ada as $n)
                    <span class="badge bg-white text-dark border font-monospace">{{ $n }}</span>
                @endforeach
            </div>
        @endif

        {{-- Ringkasan --}}
        @php $ra = $ringkasan_rpl; @endphp
        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                <div class="card h-100 mb-0"><div class="card-body">
                    <div class="small text-muted">Mahasiswa RPL</div>
                    <div class="kartu-angka">{{ $total_mahasiswa }}</div>
                    <div class="small text-muted">{{ $ra['lulus'] }} sudah lulus di Feeder</div>
                </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 mb-0 border-success"><div class="card-body">
                    <div class="small text-muted">Konversi Lengkap</div>
                    <div class="kartu-angka text-success">{{ $ra['siap'] }}</div>
                    <div class="small text-muted">{{ $total_mahasiswa - $ra['siap'] }} perlu diisi manual</div>
                </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 mb-0"><div class="card-body">
                    <div class="small text-muted">MK Perlu Diisi Manual</div>
                    <div class="kartu-angka text-danger">{{ $ra['mk_perlu_diisi'] }}</div>
                    <div class="small text-muted">total dari semua mahasiswa</div>
                </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 mb-0"><div class="card-body">
                    <div class="small text-muted">Rata-rata SKS Konversi</div>
                    <div class="kartu-angka">{{ $ra['rata_sks_konversi'] }}</div>
                    <div class="small text-muted">SKS diakui dari RPL</div>
                </div></div>
            </div>
        </div>

        <div class="card">
            {{-- Toolbar: jumlah per halaman → filter → pencarian --}}
            <div class="card-header py-3">
                <form action="{{ route('wisuda.rpl.index') }}" method="get" class="d-flex align-items-center flex-wrap gap-2">
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
                    <label for="keluar" class="visually-hidden">Status mahasiswa di Feeder</label>
                    <select name="keluar" id="keluar" class="form-select form-select-sm kirim-otomatis" style="width: auto">
                        @foreach ($opsi_keluar as $kunci => $label)
                            <option value="{{ $kunci }}" @selected($kunci === $keluar)>{{ $label }} ({{ $jumlah_per_keluar[$kunci] }})</option>
                        @endforeach
                    </select>
                    <label for="status" class="visually-hidden">Filter status transkrip</label>
                    <select name="status" id="status" class="form-select form-select-sm kirim-otomatis" style="width: auto">
                        @foreach ($opsi_filter as $kunci => $label)
                            <option value="{{ $kunci }}" @selected($kunci === $status)>{{ $label }} ({{ $jumlah_per_filter[$kunci] }})</option>
                        @endforeach
                    </select>
                    <div class="ms-md-auto">
                        <label for="cari" class="visually-hidden">Cari mahasiswa</label>
                        <div class="input-group input-group-sm">
                            <input type="search" name="cari" id="cari" value="{{ $cari }}" class="form-control" placeholder="Cari NIM / nama" style="width: 200px">
                            <button type="submit" class="btn btn-outline-secondary" aria-label="Cari"><i class="bx bx-search"></i></button>
                        </div>
                    </div>
                    <a href="{{ route('wisuda.rpl.export', $parameterRekap) }}" class="btn btn-sm btn-success text-nowrap"
                        title="Export sesuai prodi, periode masuk, filter & pencarian yang aktif">
                        <i class="bx bx-spreadsheet me-1"></i>Export Excel
                    </a>
                </form>
                @if ($kurikulum_per_angkatan)
                    <div class="small text-muted mt-2">
                        Dicocokkan otomatis per periode masuk:
                        @foreach ($kurikulum_per_angkatan as $angk => $k)
                            <span class="text-nowrap">{{ $labelPeriode($angk) }} → <strong>{{ $k['nama_kurikulum'] ?? 'tidak tersedia' }}</strong>{{ $k ? ' (' . $k['jumlah_sks_lulus'] . ' SKS)' : '' }}</span>{{ $loop->last ? '' : ' ·' }}
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 45px">No</th>
                            <th>Mahasiswa</th>
                            <th class="text-center">MK Konversi</th>
                            <th class="text-center">IPK Konversi</th>
                            <th class="text-center">SKS Konversi</th>
                            <th class="text-center">Perlu Diisi Manual</th>
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
                                    <span class="small text-muted">{{ $b['jenis_daftar'] }} · masuk {{ $labelPeriode($b['angkatan']) }}</span>
                                    @if ($b['keterangan_keluar'])
                                        <span class="badge {{ $b['keterangan_keluar'] === 'Lulus' ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary-emphasis' }} badge-titik">{{ $b['keterangan_keluar'] }}</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $r['mk_konversi'] }} MK</td>
                                <td class="text-center fw-semibold">{{ $r['total_sks'] > 0 ? number_format($r['ipk'], 2) : '-' }}</td>
                                <td class="text-center text-nowrap">
                                    <span class="{{ in_array('sks-kurang', $b['masalah']) ? 'angka-masalah' : '' }}">{{ rtrim(rtrim(number_format($r['total_sks'], 1), '0'), '.') }}</span>
                                    @isset($r['syarat_sks_lulus'])
                                        <span class="text-muted small">/ {{ $r['syarat_sks_lulus'] }}</span>
                                    @endisset
                                </td>
                                <td class="text-center">
                                    @isset($r['mk_kurang'])
                                        <span class="{{ $r['mk_kurang'] ? 'angka-masalah' : 'text-muted' }}">{{ $r['mk_kurang'] }} MK</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endisset
                                </td>
                                <td>
                                    @if ($b['siap'])
                                        <span class="badge bg-success-subtle text-success-emphasis badge-titik">Konversi lengkap</span>
                                    @elseif (!$b['kurikulum'])
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis badge-titik">Kurikulum tidak tersedia</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis badge-titik">Perlu diisi manual</span>
                                        @if (in_array('tidak-lulus', $b['masalah']))
                                            <span class="badge bg-danger-subtle text-danger-emphasis badge-titik">Ada nilai tidak lulus</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary text-nowrap tombol-detail" data-id="{{ $b['id_registrasi'] }}">
                                        Detail <i class="bx bx-chevron-right"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bx bx-search-alt fs-1 d-block mb-1"></i>
                                    @if ($total_mahasiswa === 0)
                                        Tidak ada mahasiswa RPL untuk pilihan ini di Neo Feeder. Coba pilih periode masuk atau prodi lain.
                                    @else
                                        Tidak ada mahasiswa yang cocok dengan filter/pencarian.
                                        <a href="{{ route('wisuda.rpl.index', $parameterTetap) }}">Tampilkan semua</a>
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

        {{-- Data transkrip untuk jendela detail (hanya mahasiswa di halaman ini) --}}
        @php
            $dataDetail = [];
            foreach ($hasil as $b) {
                $dataDetail[$b['id_registrasi']] = [
                    'npm' => $b['npm'], 'nama' => $b['nama'], 'jenis' => $b['jenis_daftar'],
                    'masuk' => $labelPeriode($b['angkatan']), 'asal' => trim(($b['pt_asal'] ?? '') . ($b['prodi_asal'] ? ' — ' . $b['prodi_asal'] : '')),
                    'kurikulum' => $b['kurikulum']['nama_kurikulum'] ?? null,
                    'ringkasan' => $b['ringkasan'],
                    'baris' => $b['transkrip']->map(fn ($t) => [
                        'sumber' => $t['sumber'], 'periode' => $t['nama_periode'], 'kode' => $t['kode_mk'], 'nama' => $t['nama_mk'],
                        'sks' => $t['sks'], 'huruf' => $t['nilai_huruf'], 'indeks' => $t['nilai_bobot'], 'asal' => $t['asal'],
                        'dipakai' => $t['diambil'], 'dobel' => $t['dobel'], 'tidak_lulus' => $t['tidak_lulus'],
                        'luar' => isset($t['di_kurikulum']) && !$t['di_kurikulum'],
                    ])->values(),
                    'belum' => $b['mk_belum']->map(fn ($m) => [
                        'kode' => $m['kode_mk'], 'nama' => $m['nama_mk'], 'sks' => $m['sks'], 'semester' => $m['semester'],
                        'wajib' => $m['wajib'], 'status' => $m['status'],
                    ])->values(),
                ];
            }
        @endphp
        <script type="application/json" id="data-detail">{!! json_encode($dataDetail, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}</script>

        {{-- Jendela detail transkrip --}}
        <div class="modal fade" id="modal-detail" tabindex="-1" aria-labelledby="judul-detail" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h6 class="modal-title mb-0" id="judul-detail"></h6>
                            <div class="small text-muted" id="info-detail"></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-2 text-center mb-3" id="kartu-detail"></div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0" id="tabel-detail">
                                <thead class="table-light">
                                    <tr><th>Matakuliah</th><th>Periode</th><th class="text-center">SKS</th><th class="text-center">Nilai</th><th class="text-center">Indeks</th><th>Keterangan</th></tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <section class="mt-4 d-none" id="bagian-belum">
                            <h6 class="mb-1"><i class="bx bx-edit-alt me-1 text-danger"></i>Matakuliah Kurikulum yang Perlu Diisi Manual</h6>
                            <p class="small text-muted mb-2">Belum ada nilai konversinya di Neo Feeder. Di file export, baris ini kosong (kuning) untuk diisi.</p>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light"><tr><th>Matakuliah</th><th class="text-center">SKS · Smt</th><th>Status</th></tr></thead>
                                    <tbody id="tabel-belum"></tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('js')
    <script>
        // Ganti prodi → langsung muat daftar RPL prodi tersebut (periode masuk direset)
        $('#prodi').on('change', function() {
            const url = new URL(document.getElementById('form-rpl').action);
            url.searchParams.set('prodi', this.value);
            window.location = url;
        });
        document.getElementById('angkatan')?.addEventListener('change', () => document.getElementById('form-rpl').submit());
        // Ringkas daftar NIM jadi dipisah koma agar URL pendek
        document.getElementById('form-rpl').addEventListener('submit', function() {
            const t = document.getElementById('nim');
            t.value = [...new Set(t.value.split(/[\s,;]+/).filter(Boolean))].join(',');
        });
        document.addEventListener('change', (e) => { if (e.target.classList.contains('kirim-otomatis')) e.target.form.submit(); });

        @if ($galat_feeder)
            Lobibox.notify('error', { size: 'mini', rounded: true, delay: 3000, delayIndicator: false, position: 'bottom right',
                msg: '✕ Gagal memuat data. Periksa koneksi Anda.', sound: false, icon: 'bx bx-x-circle' });
        @endif

        @if ($hasil)
            (function() {
                const data = JSON.parse(document.getElementById('data-detail').textContent);
                const modal = new bootstrap.Modal(document.getElementById('modal-detail'));
                const esc = (t) => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
                const angka = (n, d = 2) => n === null || n === undefined ? '-' : Number(n).toFixed(d);
                const sks = (n) => String(parseFloat(n));
                const kartu = (label, nilai, kelas = '') => `<div class="col-6 col-md-3"><div class="border rounded-3 p-2 h-100 ${kelas}">
                    <div class="small text-muted">${label}</div><div class="kartu-angka">${nilai}</div></div></div>`;

                document.addEventListener('click', (e) => {
                    const t = e.target.closest('.tombol-detail');
                    if (!t) return;
                    const d = data[t.dataset.id];
                    if (!d) return;
                    const r = d.ringkasan;
                    document.getElementById('judul-detail').textContent = `${d.nama} · ${d.npm}`;
                    document.getElementById('info-detail').textContent =
                        `${d.jenis} · masuk ${d.masuk}${d.asal ? ' · asal ' + d.asal : ''} · kurikulum: ${d.kurikulum || 'tidak tersedia'}`;
                    document.getElementById('kartu-detail').innerHTML =
                        kartu('MK Konversi', r.mk_konversi) +
                        kartu('IPK Konversi', r.total_sks > 0 ? angka(r.ipk) : '-') +
                        kartu('SKS Konversi / Syarat', `${sks(r.total_sks)}${r.syarat_sks_lulus ? `<span class="fs-6 text-muted">/${r.syarat_sks_lulus}</span>` : ''}`) +
                        kartu('Perlu Diisi Manual', r.mk_kurang ?? '-', r.mk_kurang ? 'border-danger' : '');

                    document.querySelector('#tabel-detail tbody').innerHTML = d.baris.length ? d.baris.map(b => {
                        const ket = [
                            b.asal ? `<span class="text-muted">Asal: ${esc(b.asal)}</span>` : '',
                            b.dobel ? (b.dipakai ? '<span class="badge bg-info-subtle text-info-emphasis">Dobel · dipakai</span>' : '<span class="badge bg-secondary-subtle text-secondary-emphasis">Dobel · tidak dihitung</span>') : '',
                            b.luar ? '<span class="badge bg-warning-subtle text-warning-emphasis">Di luar kurikulum</span>' : '',
                            !b.huruf ? '<span class="badge bg-warning-subtle text-warning-emphasis">Nilai kosong</span>' : '',
                        ].filter(Boolean).join(' ');
                        return `<tr class="${b.dipakai ? '' : 'tidak-dipakai'}">
                            <td><div class="fw-semibold">${esc(b.nama)}</div><span class="badge bg-light text-dark border font-monospace">${esc(b.kode)}</span></td>
                            <td class="small text-nowrap">${esc(b.periode)}</td>
                            <td class="text-center">${sks(b.sks)}</td>
                            <td class="text-center fw-semibold ${b.tidak_lulus ? 'text-danger' : ''}">${esc(b.huruf || '-')}</td>
                            <td class="text-center">${angka(b.indeks)}</td>
                            <td class="small">${ket}</td></tr>`;
                    }).join('') : '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada nilai konversi RPL di Neo Feeder.</td></tr>';

                    document.getElementById('bagian-belum').classList.toggle('d-none', d.belum.length === 0);
                    document.getElementById('tabel-belum').innerHTML = d.belum.map(m => `<tr>
                        <td><div class="fw-semibold">${esc(m.nama)}</div><span class="badge bg-light text-dark border font-monospace">${esc(m.kode)}</span>
                            <span class="badge ${m.wajib ? 'bg-primary-subtle text-primary-emphasis' : 'bg-secondary-subtle text-secondary-emphasis'}">${m.wajib ? 'Wajib' : 'Pilihan'}</span></td>
                        <td class="text-center">${sks(m.sks)} · ${esc(m.semester)}</td>
                        <td>${{ belum_diambil: '<span class="badge bg-warning-subtle text-warning-emphasis badge-titik">Isi manual</span>',
                                tidak_lulus: '<span class="badge bg-danger-subtle text-danger-emphasis badge-titik">Tidak lulus</span>' }[m.status]
                            || '<span class="badge bg-warning-subtle text-warning-emphasis badge-titik">Belum ada nilai</span>'}</td></tr>`).join('');
                    modal.show();
                });
            })();
        @endif
    </script>
@endpush
