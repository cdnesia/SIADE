@extends('layouts.app')
@section('content')
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0"><i class="bx bx-user-plus me-2"></i>Data Mahasiswa Baru</h5>
        <span class="ms-auto">
            <small class="text-muted">Tahun Filter: <strong>{{ $tahun_filter }}</strong></small>
        </span>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card border-primary border-start border-4">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Total Mahasiswa Baru (Sudah NIM)</p>
                            <h3 class="mb-0">{{ $jumlah_mahasiswa_baru }}</h3>
                        </div>
                        <div class="ms-3">
                            <i class="bx bx-group text-primary" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success border-start border-4">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">NPM Baru Diterbitkan</p>
                            <h3 class="mb-0">{{ $jumlah_npm_baru }}</h3>
                        </div>
                        <div class="ms-3">
                            <i class="bx bx-id-card text-success" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info border-start border-4">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Program Studi</p>
                            <h3 class="mb-0">{{ $by_prodi->count() }}</h3>
                        </div>
                        <div class="ms-3">
                            <i class="bx bx-buildings text-info" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Rekap Per Program Studi --}}
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0"><i class="bx bx-pie-chart-alt-2 me-1"></i>Rekap Mahasiswa Baru Per Program Studi</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Program Studi</th>
                            <th class="text-center">Jumlah Mahasiswa</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($by_prodi as $index => $prodi)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $prodi['nama_prodi'] }}</td>
                                <td class="text-center">
                                    <span class="badge bg-primary">{{ $prodi['jumlah'] }}</span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info btn-lihat-mhs"
                                        data-prodi="{{ $prodi['nama_prodi'] }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalDetailMhs">
                                        <i class="bx bx-search-alt me-0"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada data mahasiswa baru</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tabel NPM Baru --}}
    @if (count($npm_baru) > 0)
        <div class="card mt-3">
            <div class="card-header d-flex align-items-center">
                <h6 class="mb-0"><i class="bx bx-check-circle me-1"></i>NPM Baru Diterbitkan</h6>
                <span class="ms-auto">
                    <span class="badge bg-success">{{ $jumlah_npm_baru }} NPM</span>
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIM Baru</th>
                                <th>Nama Mahasiswa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($npm_baru as $index => $mhs)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <span class="fw-bold text-success">{{ $mhs['nim'] }}</span>
                                    </td>
                                    <td>{{ $mhs['nama'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Detail Mahasiswa Per Prodi --}}
    <div class="modal fade" id="modalDetailMhs" tabindex="-1" aria-labelledby="modalDetailMhsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetailMhsLabel">Detail Mahasiswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NPM Pendaftar</th>
                                    <th>Nama</th>
                                    <th>NIM</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyDetailMhs">
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script>
        // Store prodi data for modal
        var prodiData = @json($by_prodi);

        $('.btn-lihat-mhs').click(function() {
            var prodiName = $(this).data('prodi');
            var tbody = $('#tbodyDetailMhs');
            tbody.empty();

            $('#modalDetailMhsLabel').text('Detail Mahasiswa - ' + prodiName);

            var found = prodiData.find(function(p) {
                return p.nama_prodi === prodiName;
            });

            if (found && found.data.length > 0) {
                found.data.forEach(function(mhs, index) {
                    tbody.append(
                        '<tr>' +
                        '<td>' + (index + 1) + '</td>' +
                        '<td>' + (mhs.pmb || '-') + '</td>' +
                        '<td>' + (mhs.nama_daftar || '-') + '</td>' +
                        '<td>' + (mhs.nim || '<em class="text-muted">Belum NIM</em>') + '</td>' +
                        '</tr>'
                    );
                });
            } else {
                tbody.append('<tr><td colspan="4" class="text-center text-muted">Tidak ada data</td></tr>');
            }
        });
    </script>
@endpush
