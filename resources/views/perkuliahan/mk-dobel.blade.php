@extends('layouts.app')

@push('css')
    <style>
        .badge-titik::before { content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: currentColor; margin-right: 5px; vertical-align: middle; }
        .baris-rincian > td { background: var(--bs-tertiary-bg, #f8f9fa); }
        .tabel-dobel th, .tabel-dobel td { white-space: nowrap; }
        .tabel-dobel tr.awal-grup td { border-top: 2px solid var(--bs-border-color); }
        .komponen-nilai { font-size: .75rem; color: var(--bs-secondary-color); }
    </style>
@endpush

@section('content')
    @php
        $labelPeriode = fn ($k) => substr($k, 0, 4) . (substr($k, 4) === '2' ? ' Genap' : (substr($k, 4) === '1' ? ' Ganjil' : ''));
        $angka = fn ($n) => $n === null ? '-' : rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
        $bolehUpdate = auth()->user()->can('mk-dobel.update');
        $bolehHapus = auth()->user()->can('mk-dobel.destroy');
    @endphp

    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-0"><i class="bx bx-copy-alt me-2"></i>Matakuliah Dobel Mahasiswa</h5>
            <small class="text-muted">Mahasiswa yang mengambil matakuliah dengan <strong>kode yang sama lebih dari sekali</strong> di KRS.
                Bandingkan nilainya, lalu perbarui nilai atau hapus KRS yang tidak dipakai. KRS yang dihapus tercatat di log dan bisa dipulihkan.</small>
        </div>
    </div>

    <div class="card">
        {{-- Toolbar: jumlah per halaman → filter → pencarian --}}
        <div class="card-header py-3">
            <form action="{{ route('mk-dobel.index') }}" method="get" class="d-flex align-items-center flex-wrap gap-2" id="form-filter">
                <div class="d-flex align-items-center gap-2 small">
                    <label for="per_halaman" class="text-nowrap mb-0">Tampilkan</label>
                    <select name="per_halaman" id="per_halaman" class="form-select form-select-sm kirim-otomatis" style="width: auto">
                        @foreach ([10, 20, 50, 100] as $n)
                            <option value="{{ $n }}" @selected($n == $per_halaman)>{{ $n }}</option>
                        @endforeach
                    </select>
                    <span class="text-nowrap">data</span>
                </div>
                <label for="prodi" class="visually-hidden">Program studi</label>
                <select name="prodi" id="prodi" class="form-select form-select-sm" style="width: auto; max-width: 320px">
                    <option value="">Semua prodi ({{ $jumlah_prodi->sum() }})</option>
                    @foreach ($daftar_prodi as $p)
                        <option value="{{ $p->kode_program_studi }}" @selected($p->kode_program_studi == $prodi)>
                            {{ $p->nama_program_studi_idn }} ({{ $jumlah_prodi[$p->kode_program_studi] ?? 0 }})
                        </option>
                    @endforeach
                </select>
                <label for="angkatan" class="visually-hidden">Angkatan</label>
                <select name="angkatan" id="angkatan" class="form-select form-select-sm kirim-otomatis" style="width: auto">
                    <option value="">Semua angkatan ({{ $jumlah_angkatan->sum() }})</option>
                    @foreach ($jumlah_angkatan as $nilai => $jumlah)
                        <option value="{{ $nilai }}" @selected($nilai == $angkatan)>{{ $labelPeriode($nilai) }} ({{ $jumlah }})</option>
                    @endforeach
                </select>
                <div class="ms-md-auto">
                    <label for="cari" class="visually-hidden">Cari mahasiswa</label>
                    <div class="input-group input-group-sm">
                        <input type="search" name="cari" id="cari" value="{{ $cari }}" class="form-control" placeholder="Cari NPM / nama" style="width: 200px">
                        <button type="submit" class="btn btn-outline-secondary" aria-label="Cari"><i class="bx bx-search"></i></button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 45px">No</th>
                        <th>Mahasiswa</th>
                        <th>Program Studi</th>
                        <th class="text-center">MK Dobel</th>
                        <th class="text-center">Baris KRS</th>
                        <th class="text-end"><span class="visually-hidden">Rincian</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($hasil as $i => $m)
                        <tr>
                            <td class="text-center text-muted">{{ $hasil->firstItem() + $i }}</td>
                            <td>
                                <div class="fw-semibold">{{ $m->nama_mahasiswa }}</div>
                                <span class="badge bg-light text-dark border font-monospace">{{ $m->npm }}</span>
                                <span class="small text-muted">angkatan {{ $labelPeriode($m->tahun_angkatan) }}</span>
                            </td>
                            <td class="small">{{ $m->nama_program_studi_idn ?? $m->kode_program_studi }}</td>
                            <td class="text-center">
                                <span class="badge bg-warning-subtle text-warning-emphasis badge-titik">{{ $m->jumlah_mk }} MK</span>
                            </td>
                            <td class="text-center">{{ $m->jumlah_krs }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" data-bs-toggle="collapse"
                                    data-bs-target="#rincian-{{ $m->npm }}" aria-expanded="true" aria-controls="rincian-{{ $m->npm }}">
                                    <i class="bx bx-chevron-up"></i> Rincian
                                </button>
                            </td>
                        </tr>
                        <tr class="baris-rincian">
                            <td colspan="6" class="p-0 border-0">
                                <div class="collapse show" id="rincian-{{ $m->npm }}">
                                    <div class="px-3 py-2">
                                        <div class="table-responsive bg-white border rounded-3">
                                            <table class="table table-sm align-middle mb-0 tabel-dobel">
                                                <thead>
                                                    <tr class="small text-muted">
                                                        <th class="ps-3">Matakuliah</th>
                                                        <th>Periode</th>
                                                        <th class="text-center">SKS</th>
                                                        <th>Komponen Nilai</th>
                                                        <th class="text-center">Angka</th>
                                                        <th class="text-center">Huruf</th>
                                                        <th class="text-center">Bobot</th>
                                                        <th>Keterangan</th>
                                                        <th class="text-end pe-3"><span class="visually-hidden">Aksi</span></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($m->grup as $kode => $baris)
                                                        @php
                                                            // Percobaan dengan bobot tertinggi (lalu periode terbaru) — calon nilai yang dipakai
                                                            $terbaik = $baris->sortByDesc(fn ($b) => sprintf('%06.2f|%s|%012d', $b->nilai_huruf ? $b->nilai_bobot : -1, $b->kode_tahun_akademik, $b->id))->first();
                                                        @endphp
                                                        @foreach ($baris as $b)
                                                            @php
                                                                $kosong = $b->nilai_huruf === null || $b->nilai_huruf === '';
                                                                $komponen = collect(['Sikap' => $b->nilai_sikap, 'Kuis' => $b->nilai_kuis, 'UTS' => $b->nilai_uts, 'KU' => $b->nilai_ku, 'KH' => $b->nilai_kh, 'UAS' => $b->nilai_uas])
                                                                    ->filter(fn ($v) => $v !== null && (float) $v > 0);
                                                                $label = $b->kode_mata_kuliah . ' — ' . $b->nama_mata_kuliah_idn . ' (' . ($b->nama_tahun_akademik ?? $b->kode_tahun_akademik) . ')';
                                                            @endphp
                                                            <tr class="{{ $loop->first && !$loop->parent->first ? 'awal-grup' : '' }}">
                                                                <td class="ps-3">
                                                                    @if ($loop->first)
                                                                        <div class="fw-semibold text-wrap">{{ $b->nama_mata_kuliah_idn }}</div>
                                                                        <span class="badge bg-light text-dark border font-monospace">{{ $b->kode_mata_kuliah }}</span>
                                                                        <span class="small text-muted">{{ $baris->count() }}× diambil</span>
                                                                    @else
                                                                        <span class="small text-muted ps-2">↳ percobaan ke-{{ $loop->iteration }}</span>
                                                                    @endif
                                                                </td>
                                                                <td class="small">{{ $b->nama_tahun_akademik ?? $b->kode_tahun_akademik }}</td>
                                                                <td class="text-center">{{ $angka($b->sks_mata_kuliah) }}</td>
                                                                <td class="komponen-nilai">
                                                                    {{ $komponen->isEmpty() ? '-' : $komponen->map(fn ($v, $k) => $k . ' ' . $angka($v))->implode(' · ') }}
                                                                </td>
                                                                <td class="text-center">{{ $kosong ? '-' : $angka($b->nilai_angka) }}</td>
                                                                <td class="text-center fw-semibold {{ !$kosong && $b->lulus === 'T' ? 'text-danger' : '' }}">{{ $kosong ? '-' : $b->nilai_huruf }}</td>
                                                                <td class="text-center">{{ $kosong ? '-' : $angka($b->nilai_bobot) }}</td>
                                                                <td>
                                                                    @if ($kosong)
                                                                        <span class="badge bg-secondary-subtle text-secondary-emphasis badge-titik">Nilai kosong</span>
                                                                    @elseif ($b->id === $terbaik->id)
                                                                        <span class="badge bg-success-subtle text-success-emphasis badge-titik">Nilai tertinggi</span>
                                                                    @elseif ($b->lulus === 'T')
                                                                        <span class="badge bg-danger-subtle text-danger-emphasis badge-titik">Tidak lulus</span>
                                                                    @endif
                                                                    @if ($b->jadwal_id == 0)
                                                                        <span class="badge bg-info-subtle text-info-emphasis">KRS manual</span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-end pe-3">
                                                                    @if ($bolehUpdate || $bolehHapus)
                                                                        <div class="dropdown">
                                                                            <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="dropdown" aria-expanded="false"
                                                                                aria-label="Aksi untuk {{ $label }}">⋯</button>
                                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                                @if ($bolehUpdate)
                                                                                    <li>
                                                                                        <button type="button" class="dropdown-item tombol-update" data-id="{{ $b->id }}"
                                                                                            data-label="{{ $label }}" data-nama="{{ $m->nama_mahasiswa }} · {{ $m->npm }}"
                                                                                            data-prodi="{{ $m->kode_program_studi }}" data-nilai="{{ $kosong ? '' : $angka($b->nilai_angka) }}"
                                                                                            data-lama="{{ $kosong ? 'kosong' : $b->nilai_huruf . ' (' . $angka($b->nilai_angka) . ')' }}">
                                                                                            <i class="bx bx-edit-alt me-1"></i>Update nilai
                                                                                        </button>
                                                                                    </li>
                                                                                @endif
                                                                                @if ($bolehHapus)
                                                                                    <li>
                                                                                        <button type="button" class="dropdown-item text-danger tombol-hapus" data-id="{{ $b->id }}"
                                                                                            data-label="{{ $label }}" data-nama="{{ $m->nama_mahasiswa }} · {{ $m->npm }}"
                                                                                            data-nilai="{{ $kosong ? 'nilai kosong' : 'nilai ' . $b->nilai_huruf . ' (' . $angka($b->nilai_angka) . ')' }}">
                                                                                            <i class="bx bx-trash me-1"></i>Hapus KRS ini
                                                                                        </button>
                                                                                    </li>
                                                                                @endif
                                                                            </ul>
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bx bx-check-double fs-1 d-block mb-1"></i>
                                @if ($prodi || $angkatan || $cari !== '')
                                    Tidak ada mahasiswa dengan MK dobel yang cocok dengan filter/pencarian.
                                    <a href="{{ route('mk-dobel.index') }}">Tampilkan semua</a>
                                @else
                                    Tidak ada mahasiswa yang mengambil matakuliah dobel. Semua KRS sudah bersih.
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

    {{-- Modal update nilai --}}
    <div class="modal fade" id="modal-update" tabindex="-1" aria-labelledby="judul-update" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content form-ajax" method="post" data-aksi="update">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <div>
                        <h6 class="modal-title mb-0" id="judul-update">Update Nilai</h6>
                        <div class="small text-muted" data-isi="nama"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="small mb-3"><span data-isi="label" class="fw-semibold"></span><br>
                        Nilai saat ini: <span data-isi="lama"></span></p>
                    <label for="nilai_angka" class="form-label">Nilai angka (0–100)</label>
                    <input type="number" name="nilai_angka" id="nilai_angka" class="form-control" min="0" max="100" step="0.01" required>
                    <div class="form-text" id="pratinjau-nilai">Huruf & bobot dihitung otomatis dari skala nilai prodi.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal konfirmasi hapus --}}
    <div class="modal fade" id="modal-hapus" tabindex="-1" aria-labelledby="judul-hapus" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content form-ajax" method="post" data-aksi="hapus">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <div>
                        <h6 class="modal-title mb-0" id="judul-hapus">Hapus KRS Dobel</h6>
                        <div class="small text-muted" data-isi="nama"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">KRS berikut akan dihapus permanen dari data mahasiswa:</p>
                    <p class="small mb-2"><span data-isi="label" class="fw-semibold"></span> — <span data-isi="nilai"></span></p>
                    <div class="alert alert-warning small mb-0">
                        <i class="bx bx-info-circle me-1"></i>Minimal satu KRS untuk matakuliah ini akan tetap tersisa.
                        Isi KRS yang dihapus disimpan di log sehingga masih bisa dipulihkan.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="bx bx-trash me-1"></i>Hapus</button>
                </div>
            </form>
        </div>
    </div>

    <script type="application/json" id="data-skala">{!! json_encode($skala_nilai, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}</script>
@endsection

@push('js')
    <script>
        (function() {
            const toast = (tipe, pesan) => Lobibox.notify(tipe, {
                size: 'mini', rounded: true, delay: 3000, delayIndicator: false, position: 'bottom right', sound: false, msg: pesan,
                icon: { success: 'bx bx-check-circle', error: 'bx bx-x-circle', warning: 'bx bx-error', info: 'bx bx-info-circle' }[tipe],
            });

            // Toast hasil aksi sebelum halaman dimuat ulang
            const tertunda = sessionStorage.getItem('toast-mk-dobel');
            if (tertunda) {
                sessionStorage.removeItem('toast-mk-dobel');
                const t = JSON.parse(tertunda);
                toast(t.tipe, t.pesan);
            }

            // Filter langsung memuat ulang; ganti prodi mereset angkatan
            document.addEventListener('change', (e) => { if (e.target.classList.contains('kirim-otomatis')) e.target.form.submit(); });
            document.getElementById('prodi').addEventListener('change', function() {
                document.getElementById('angkatan').value = '';
                this.form.submit();
            });

            // Ikon tombol rincian mengikuti status buka/tutup
            document.querySelectorAll('.baris-rincian .collapse').forEach(el => {
                el.addEventListener('show.bs.collapse', () => document.querySelector(`[data-bs-target="#${el.id}"] i`).className = 'bx bx-chevron-up');
                el.addEventListener('hide.bs.collapse', () => document.querySelector(`[data-bs-target="#${el.id}"] i`).className = 'bx bx-chevron-down');
            });

            const skala = JSON.parse(document.getElementById('data-skala').textContent);
            const urlDasar = @json(url('perkuliahan/mk-dobel'));
            const modalUpdate = document.getElementById('modal-update');
            const modalHapus = document.getElementById('modal-hapus');
            const inputNilai = document.getElementById('nilai_angka');
            const pratinjau = document.getElementById('pratinjau-nilai');
            const teksPratinjau = pratinjau.textContent;
            let skalaAktif = [];

            const isiModal = (modal, data) => {
                modal.querySelectorAll('[data-isi]').forEach(el => el.textContent = data[el.dataset.isi] ?? '');
                modal.querySelector('form').action = `${urlDasar}/${data.id}`;
            };

            const tampilPratinjau = () => {
                const n = parseFloat(inputNilai.value);
                if (inputNilai.value === '' || isNaN(n)) { pratinjau.textContent = teksPratinjau; pratinjau.className = 'form-text'; return; }
                const s = skalaAktif.find(s => n >= parseFloat(s.nilai_mulai) && n <= parseFloat(s.nilai_sampai));
                pratinjau.textContent = s ? `Menjadi ${s.nama} (bobot ${parseFloat(s.bobot)})${s.lulus === 'T' ? ' — tidak lulus' : ''}` : 'Skala nilai prodi untuk nilai ini tidak ditemukan.';
                pratinjau.className = 'form-text ' + (!s || s.lulus === 'T' ? 'text-danger' : 'text-success');
            };
            inputNilai.addEventListener('input', tampilPratinjau);

            document.addEventListener('click', (e) => {
                const up = e.target.closest('.tombol-update');
                if (up) {
                    isiModal(modalUpdate, up.dataset);
                    skalaAktif = skala[up.dataset.prodi] || [];
                    inputNilai.value = up.dataset.nilai;
                    tampilPratinjau();
                    bootstrap.Modal.getOrCreateInstance(modalUpdate).show();
                    return;
                }
                const del = e.target.closest('.tombol-hapus');
                if (del) {
                    isiModal(modalHapus, del.dataset);
                    bootstrap.Modal.getOrCreateInstance(modalHapus).show();
                }
            });
            modalUpdate.addEventListener('shown.bs.modal', () => inputNilai.focus());

            // Kirim update/hapus lewat AJAX, lalu muat ulang halaman agar rincian sesuai database
            document.addEventListener('submit', async (e) => {
                const form = e.target.closest('.form-ajax');
                if (!form) return;
                e.preventDefault();
                if (!form.checkValidity()) {
                    form.reportValidity();
                    toast('warning', '⚠ Periksa kembali isian form Anda.');
                    return;
                }
                const btn = form.querySelector('[type="submit"]');
                const teksBtn = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';
                const gagalUmum = form.dataset.aksi === 'hapus' ? '✕ Gagal menghapus data. Coba lagi.' : '✕ Gagal memperbarui data. Coba lagi.';
                try {
                    const resp = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: new FormData(form),
                    });
                    const res = await resp.json().catch(() => ({}));
                    if (resp.status === 422 && res.errors) {
                        toast('warning', '⚠ Periksa kembali isian form Anda.');
                        return;
                    }
                    if (!resp.ok || !res.success) {
                        toast('error', res.message || (resp.status === 403 ? '✕ Anda tidak memiliki akses untuk aksi ini.' : gagalUmum));
                        return;
                    }
                    bootstrap.Modal.getOrCreateInstance(form.closest('.modal')).hide();
                    sessionStorage.setItem('toast-mk-dobel', JSON.stringify({ tipe: 'success', pesan: res.message }));
                    window.location.reload();
                } catch (err) {
                    toast('error', gagalUmum);
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = teksBtn;
                }
            });
        })();
    </script>
@endpush
