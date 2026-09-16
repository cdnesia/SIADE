@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">Import Tahun Akademik Penerima Beasiswa</h6>
            <div class="ms-auto">
                <a href="{{ route($modul . '.index') }}" class="btn btn-sm btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="bx bx-info-circle me-1"></i>
                Gunakan fitur ini untuk menambahkan tahun akademik ke data penerima beasiswa yang sudah ada secara massal.
                Kolom yang wajib diisi: <code>npm</code>, <code>tahun_akademik</code>.
                NPM harus sudah terdaftar sebagai penerima beasiswa &mdash; tahun akademik pada file akan
                <strong>ditambahkan</strong> ke tahun akademik yang sudah tersimpan untuk NPM tersebut, bukan menggantikannya.
                <br>
                <a href="{{ route($modul . '.import.template') }}" class="alert-link">
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

            <form action="{{ route($modul . '.import.store') }}" method="post" enctype="multipart/form-data">
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
