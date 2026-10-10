@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header py-3">
            <h6 class="mb-0"><i class="bx bx-search-alt me-1"></i>Pencarian Pendaftar</h6>
            <small class="text-muted">Cari seluruh pendaftar PMB tanpa memperhatikan status pembayaran. NPM dapat
                diterbitkan untuk pendaftar yang belum memiliki NIM.</small>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route($modul) }}" class="row g-2 align-items-end mb-3">
                <div class="col-12 col-md-2">
                    <label class="form-label" for="f-tahun">Tahun PMB</label>
                    <select name="tahun" id="f-tahun" class="form-select">
                        @foreach ($daftar_tahun as $t)
                            <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>{{ substr($t, 4) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-7">
                    <label class="form-label" for="f-q">Kata Kunci</label>
                    <input type="search" name="q" id="f-q" class="form-control" value="{{ $q }}" autofocus
                        minlength="{{ \App\Http\Controllers\PencarianPendaftarController::MIN_KATA_KUNCI }}"
                        placeholder="Nama, nomor pendaftaran, NIM, no. HP, atau email">
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bx bx-search me-1"></i>Cari
                    </button>
                    @if ($q !== '')
                        <a href="{{ route($modul, ['tahun' => $tahun]) }}" class="btn btn-outline-secondary w-100">
                            <i class="bx bx-reset me-1"></i>Reset
                        </a>
                    @endif
                </div>
            </form>

            @if (mb_strlen($q) < \App\Http\Controllers\PencarianPendaftarController::MIN_KATA_KUNCI)
                <div class="text-center text-muted border rounded-3 py-5">
                    <i class="bx bx-search-alt fs-1 d-block mb-2"></i>
                    Masukkan minimal {{ \App\Http\Controllers\PencarianPendaftarController::MIN_KATA_KUNCI }} karakter
                    untuk mencari pendaftar.
                </div>
            @elseif ($pendaftar->isEmpty())
                <div class="text-center text-muted border rounded-3 py-5">
                    <i class="bx bx-user-x fs-1 d-block mb-2"></i>
                    Tidak ada pendaftar tahun {{ substr($tahun, 4) }} yang cocok dengan "{{ $q }}".
                    <div class="mt-2"><a href="{{ route($modul, ['tahun' => $tahun]) }}">Cari dengan kata kunci lain</a>
                    </div>
                </div>
            @else
                <div class="small text-muted mb-2">
                    Ditemukan <strong>{{ $pendaftar->count() }}</strong> pendaftar.
                    @if ($terpotong)
                        Hanya {{ \App\Http\Controllers\PencarianPendaftarController::MAKS_HASIL }} hasil pertama yang
                        ditampilkan, persempit kata kunci pencarian.
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" style="width:100%">
                        <thead class="small text-muted">
                            <tr>
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
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-nowrap">{{ $item->pmb }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $item->nama_daftar }}</div>
                                        <small class="text-muted">{{ $item->sekolah_asal ?: '-' }}</small>
                                    </td>
                                    <td>{{ $prodi[$item->prodi]->nama ?? 'Prodi ' . ($item->prodi ?: '-') }}</td>
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
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis">Belum ada NIM</span>
                                        @endif
                                    </td>
                                    @canany(['mahasiswa-baru.pencarian.generate-nim', 'mahasiswa.sync.import'])
                                        <td class="text-end text-nowrap">
                                            @if (!$item->nim)
                                                @can('mahasiswa-baru.pencarian.generate-nim')
                                                <form action="{{ route('mahasiswa-baru.pencarian.generate-nim', $item->nomor_pmb) }}"
                                                    method="POST" class="d-inline form-generate"
                                                    data-nama="{{ $item->nama_daftar }}"
                                                    data-prodi="{{ $prodi[$item->prodi]->nama ?? $item->prodi }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        <i class="bx bx-id-card me-1"></i>Generate NPM
                                                    </button>
                                                </form>
                                                @endcan
                                            @elseif (!$item->di_master)
                                                @can('mahasiswa.sync.import')
                                                    <form action="{{ route('mahasiswa.sync.import') }}" method="POST"
                                                        class="d-inline form-master" data-nama="{{ $item->nama_daftar }}"
                                                        data-nim="{{ $item->nim }}">
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
@push('js')
    <script>
        // Konfirmasi sebelum aksi; tombol dikunci agar tidak terkirim dua kali
        document.querySelectorAll('.form-generate, .form-master').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                var pesan = form.classList.contains('form-generate') ?
                    'Terbitkan NPM untuk ' + form.dataset.nama + ' (' + form.dataset.prodi + ')?\n' +
                    'NPM diterbitkan tanpa cek pembayaran dan tidak bisa dibatalkan dari halaman ini.' :
                    'Masukkan ' + form.dataset.nama + ' (NIM ' + form.dataset.nim + ') ke master mahasiswa?\n' +
                    'Akun login juga dibuat dengan username dan password = NIM.';
                if (!confirm(pesan)) {
                    e.preventDefault();
                    return;
                }
                var btn = form.querySelector('button');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';
            });
        });
    </script>
@endpush
