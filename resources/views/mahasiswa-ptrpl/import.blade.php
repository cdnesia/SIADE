@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">Import Nilai Transfer / Konversi ke KRS</h6>
            <div class="ms-auto">
                <a href="{{ route('mahasiswa-ptrpl.index') }}" class="btn btn-sm btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="bx bx-info-circle me-1"></i>
                Gunakan fitur ini untuk mengimport banyak nilai transfer/konversi mahasiswa Pindahan/RPL sekaligus.
                Setiap baris akan disimpan sebagai data KRS (kontrak + nilai) mahasiswa. Kolom yang wajib diisi:
                <code>npm</code>, <code>kode_tahun_akademik</code>, <code>kode_mata_kuliah</code>,
                <code>nilai_angka</code>.
                <br>
                <a href="{{ route('mahasiswa-ptrpl.import.template') }}" class="alert-link">
                    <i class="bx bx-download me-1"></i>Download Template Excel
                </a>
            </div>

            @if (session('import_errors') && count(session('import_errors')) > 0)
                <div class="alert alert-warning">
                    <strong><i class="bx bx-error me-1"></i>Baris yang dilewati:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach (session('import_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('mahasiswa-ptrpl.import.store') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="mb-3" style="max-width: 500px">
                    <label for="file" class="form-label">File Excel/CSV</label>
                    <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-upload me-1"></i> Import
                </button>
            </form>
        </div>
    </div>
@endsection
