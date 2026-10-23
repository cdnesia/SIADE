@extends('layouts.app')
@section('content')
    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-0"><i class="bx bx-import me-2"></i>Import Mahasiswa Baru</h5>
            <small class="text-muted">Tambahkan mahasiswa baru secara massal dari file Excel. Data diperiksa dulu sebelum
                disimpan, akun login dibuat otomatis dengan password = NPM.</small>
        </div>
        <a href="{{ route($modul . '.template') }}" class="btn btn-sm btn-outline-success ms-auto">
            <i class="bx bx-download me-1"></i>Download Template
        </a>
    </div>

    @if (session('import_selesai'))
        <div class="alert alert-success d-flex align-items-start">
            <i class="bx bx-check-circle fs-4 me-2"></i>
            <div>
                <strong>{{ session('import_selesai')['disimpan'] }}</strong> mahasiswa berhasil diimport.
                @if (count(session('import_selesai')['dilewati']) > 0)
                    <div class="small mt-1">
                        {{ count(session('import_selesai')['dilewati']) }} dilewati karena NPM/NIK sudah terdaftar:
                        {{ implode(', ', session('import_selesai')['dilewati']) }}
                    </div>
                @endif
                <div class="small mt-1">
                    <a href="{{ route('mahasiswa.index') }}" class="alert-link">Lihat Data Mahasiswa</a>
                </div>
            </div>
        </div>
    @endif

    @if (!$pratinjau)
        {{-- Langkah 1: unggah file --}}
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bx bx-upload me-1"></i>Unggah File</h6>
            </div>
            <div class="card-body">
                <form action="{{ route($modul . '.preview') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3" style="max-width: 500px">
                        <label for="file" class="form-label">File Excel/CSV <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv" required
                            class="form-control @error('file') is-invalid @enderror">
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            <div class="form-text">Maksimal {{ number_format($maks_baris) }} baris dan 5 MB per file.</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-search-alt me-1"></i>Periksa File
                    </button>
                </form>

                <hr>

                <h6 class="mb-2">Ketentuan Kolom</h6>
                <ul class="small mb-3">
                    <li><strong>Wajib:</strong> <code>nama_mahasiswa</code>, <code>npm</code>, <code>tahun_angkatan</code>
                        (tahun + semester, contoh <code>20261</code>), <code>program_studi</code>, <code>kelas</code>.</li>
                    <li><code>program_studi</code>, <code>kelas</code>, dan <code>jenis_pendaftaran</code> boleh diisi kode
                        atau nama sesuai tabel referensi di bawah. <code>jenis_pendaftaran</code> yang dikosongkan dianggap
                        <em>Peserta didik baru</em>.</li>
                    <li><code>jenis_kelamin</code>: <code>L</code>/<code>P</code> atau Laki-laki/Perempuan.
                        <code>tanggal_lahir</code>: format <code>dd/mm/yyyy</code> atau tanggal Excel.</li>
                    <li>NPM atau NIK yang sudah terdaftar, maupun yang ganda di dalam file, akan ditandai bermasalah dan
                        tidak disimpan.</li>
                </ul>

                <a class="small" data-bs-toggle="collapse" href="#referensi" role="button" aria-expanded="false"
                    aria-controls="referensi">
                    <i class="bx bx-list-ul me-1"></i>Lihat tabel referensi kode
                </a>
                <div class="collapse mt-3" id="referensi">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr><th>Kode Prodi</th><th>Program Studi</th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($prodi as $p)
                                        <tr><td><code>{{ $p->kode_program_studi }}</code></td><td>{{ $p->nama_program_studi_idn }}</td></tr>
                                    @empty
                                        <tr><td colspan="2" class="text-center text-muted">Belum ada data program studi</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-3 col-sm-6">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr><th>Kode</th><th>Kelas</th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($kelas as $k)
                                        <tr><td><code>{{ $k->id }}</code></td><td>{{ $k->nama_program_perkuliahan }}</td></tr>
                                    @empty
                                        <tr><td colspan="2" class="text-center text-muted">Belum ada data kelas</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-3 col-sm-6">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr><th>Kode</th><th>Jenis Pendaftaran</th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($jenis_pendaftaran as $j)
                                        <tr><td><code>{{ $j->id }}</code></td><td>{{ $j->nama_jenis_pendaftaran }}</td></tr>
                                    @empty
                                        <tr><td colspan="2" class="text-center text-muted">Belum ada data jenis pendaftaran</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Langkah 2: pratinjau hasil pemeriksaan --}}
        @php
            $jumlahValid = count($pratinjau['valid']);
            $jumlahError = count($pratinjau['errors']);
        @endphp
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card border-primary border-start border-4 mb-md-0 mb-2">
                    <div class="card-body py-3">
                        <p class="text-muted mb-1 small">Total Baris</p>
                        <h3 class="mb-0">{{ $pratinjau['total'] }}</h3>
                        <small class="text-muted text-break">{{ $pratinjau['nama_file'] }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success border-start border-4 mb-md-0 mb-2">
                    <div class="card-body py-3">
                        <p class="text-muted mb-1 small">Siap Disimpan</p>
                        <h3 class="mb-0 text-success">{{ $jumlahValid }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-danger border-start border-4 mb-0">
                    <div class="card-body py-3">
                        <p class="text-muted mb-1 small">Bermasalah (Tidak Disimpan)</p>
                        <h3 class="mb-0 {{ $jumlahError ? 'text-danger' : '' }}">{{ $jumlahError }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center flex-wrap gap-2">
                <h6 class="mb-0"><i class="bx bx-show me-1"></i>Pratinjau Import</h6>
                <div class="ms-auto d-flex gap-2">
                    <form action="{{ route($modul . '.batal') }}" method="post">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Batal &amp; Unggah Ulang
                        </button>
                    </form>
                    @if ($jumlahValid > 0)
                        <form action="{{ route($modul . '.simpan') }}" method="post" id="form-simpan">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary" id="btn-simpan">
                                <i class="bx bx-save me-1"></i>Simpan {{ $jumlahValid }} Mahasiswa
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if ($jumlahError > 0)
                    <div class="alert alert-warning small mb-3">
                        <i class="bx bx-error me-1"></i>
                        {{ $jumlahError }} baris bermasalah tidak akan disimpan.
                        @if ($jumlahValid > 0)
                            Anda tetap bisa menyimpan {{ $jumlahValid }} baris yang valid, atau perbaiki file lalu unggah ulang.
                        @else
                            Perbaiki file lalu unggah ulang.
                        @endif
                    </div>
                @endif

                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $jumlahError ? '' : 'active' }}" data-bs-toggle="tab"
                            data-bs-target="#tab-valid" type="button" role="tab">
                            Siap Disimpan <span class="badge bg-success ms-1">{{ $jumlahValid }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $jumlahError ? 'active' : '' }}" data-bs-toggle="tab"
                            data-bs-target="#tab-error" type="button" role="tab">
                            Bermasalah <span class="badge bg-danger ms-1">{{ $jumlahError }}</span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade {{ $jumlahError ? '' : 'show active' }}" id="tab-valid" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center">Baris</th>
                                        <th>NPM</th>
                                        <th>Nama</th>
                                        <th>Angkatan</th>
                                        <th>Program Studi</th>
                                        <th>Kelas</th>
                                        <th>Jenis Pendaftaran</th>
                                        <th class="text-center">JK</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($pratinjau['valid'] as $v)
                                        <tr>
                                            <td class="text-center text-muted">{{ $v['baris'] }}</td>
                                            <td class="fw-semibold">{{ $v['data']['npm'] }}</td>
                                            <td>{{ $v['data']['nama_mahasiswa'] }}</td>
                                            <td>{{ $v['data']['tahun_angkatan'] }}</td>
                                            <td>{{ $v['nama_prodi'] }}</td>
                                            <td>{{ $v['nama_kelas'] }}</td>
                                            <td>{{ $v['nama_jenis'] }}</td>
                                            <td class="text-center">{{ $v['data']['jenis_kelamin'] ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                Tidak ada baris yang valid. Perbaiki file sesuai daftar di tab Bermasalah.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade {{ $jumlahError ? 'show active' : '' }}" id="tab-error" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center">Baris</th>
                                        <th>NPM</th>
                                        <th>Nama</th>
                                        <th>Masalah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($pratinjau['errors'] as $e)
                                        <tr>
                                            <td class="text-center text-muted">{{ $e['baris'] }}</td>
                                            <td>{{ $e['npm'] }}</td>
                                            <td>{{ $e['nama'] }}</td>
                                            <td>
                                                <ul class="mb-0 ps-3 small text-danger">
                                                    @foreach ($e['pesan'] as $pesan)
                                                        <li>{{ $pesan }}</li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="bx bx-check-circle text-success me-1"></i>Semua baris valid.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
@push('js')
    <script>
        // Konfirmasi sebelum simpan & cegah klik ganda
        $('#form-simpan').on('submit', function(e) {
            if (!confirm('Simpan {{ $pratinjau ? count($pratinjau['valid']) : 0 }} mahasiswa ke data master? Akun login akan dibuat otomatis.')) {
                e.preventDefault();
                return;
            }
            $('#btn-simpan').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...');
        });
    </script>
@endpush
