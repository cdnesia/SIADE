@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column align-items-center text-center">
                        <img src="{{ asset('') }}assets/images/no-image.png" alt="Admin"
                            class="rounded" width="120">
                        <div class="mt-3">
                            <p class="text-secondary mb-1">{{ $mahasiswa['nama_mahasiswa'] }}</p>
                            <p class="text-muted font-size-sm mb-1">{{ $mahasiswa['npm'] }}</p>
                            <p class="text-muted font-size-sm">{{ $mahasiswa['nama_jenis_pendaftaran'] }}</p>
                            <a href="{{ route('mahasiswa.show', $mahasiswa['id']) }}?p=detail-mahasiswa"
                                class="btn btn-sm {{ $page == 'detail-mahasiswa' ? 'btn-primary' : 'btn-info' }}">Detail Mahasiswa</a>
                            @can($modul . '.detail.krs')
                                <a href="{{ route('mahasiswa.show', $mahasiswa['id']) }}?p=krs"
                                    class="btn btn-sm {{ $page == 'krs' ? 'btn-primary' : 'btn-info' }}"><i class="bx bx-book-open mr-1"></i>KRS</a>
                            @endcan
                            @can($modul . '.detail.khs')
                                <a href="{{ route('mahasiswa.show', $mahasiswa['id']) }}?p=khs"
                                    class="btn btn-sm {{ $page == 'khs' ? 'btn-primary' : 'btn-info' }}"><i class="bx bx-bar-chart-alt-2 mr-1"></i>KHS</a>
                            @endcan
                        </div>
                    </div>
                    <hr class="my-4" />
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                            <h6 class="mb-0">Fakultas</h6>
                            <span class="text-secondary">{{ $mahasiswa['nama_fakultas'] ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                            <h6 class="mb-0">Program Studi</h6>
                            <span class="text-secondary">{{ $mahasiswa['nama_program_studi'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                            <h6 class="mb-0">Kelas Perkuliahan</h6>
                            <span class="text-secondary">{{ $mahasiswa['nama_program_kuliah'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                            <h6 class="mb-0">Dosen PA</h6>
                            <span class="text-secondary">{{ $mahasiswa['nama_pa'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                            <h6 class="mb-0">NIDN PA</h6>
                            <span class="text-secondary">{{ $mahasiswa['nidn_pa'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                            <h6 class="mb-0">Status KIPK</h6>
                            <span class="badge {{ $isKipk ? 'bg-success' : 'bg-secondary' }}">
                                {{ $isKipk ? 'Penerima KIPK' : 'Bukan Penerima KIPK' }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        @if ($page == 'detail-mahasiswa')
            <div class="col-lg-8">
                <div class="d-flex align-items-center mb-3">
                    <h5 class="mb-0"><i class="bx bx-user-circle me-2"></i>Detail Mahasiswa</h5>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-user-circle me-1"></i>Informasi Mahasiswa</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td class="fw-bold" style="width: 160px">NPM</td>
                                        <td>: {{ $mahasiswa['npm'] }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Nama Lengkap</td>
                                        <td>: {{ $mahasiswa['nama_mahasiswa'] }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Fakultas</td>
                                        <td>: {{ $mahasiswa['nama_fakultas'] ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Program Studi</td>
                                        <td>: {{ $mahasiswa['nama_program_studi'] }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Kelas Perkuliahan</td>
                                        <td>: {{ $mahasiswa['nama_program_kuliah'] }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Tahun Angkatan</td>
                                        <td>: {{ $mahasiswa['tahun_angkatan'] }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td class="fw-bold" style="width: 160px">Jenis Pendaftaran</td>
                                        <td>: {{ $mahasiswa['nama_jenis_pendaftaran'] }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Dosen PA</td>
                                        <td>: {{ $mahasiswa['nama_pa'] }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">NIDN PA</td>
                                        <td>: {{ $mahasiswa['nidn_pa'] }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Status KIPK</td>
                                        <td>: <span class="badge {{ $isKipk ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $isKipk ? 'Penerima KIPK' : 'Bukan Penerima KIPK' }}
                                            </span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($isKipk && count($riwayatBeasiswa) > 0)
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bx bx-money me-1"></i>Riwayat Penerima Beasiswa</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Lembaga Beasiswa</th>
                                            <th>Tahun Akademik</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($riwayatBeasiswa as $index => $riwayat)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $riwayat['lembaga'] }}</td>
                                                <td>
                                                    @foreach ($riwayat['tahun_akademik'] as $ta)
                                                        <span class="badge bg-info me-1">{{ $ta }}</span>
                                                    @endforeach
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @elseif (!$isKipk)
                    <div class="card">
                        <div class="card-body text-center py-4">
                            <i class="bx bx-info-circle" style="font-size: 36px; color: #6c757d;"></i>
                            <p class="text-muted mt-2 mb-0">Mahasiswa ini bukan penerima KIPK / beasiswa</p>
                        </div>
                    </div>
                @endif
            </div>
        @elseif ($page == 'krs')
            <div class="col-lg-8">
                <div class="d-flex align-items-center mb-3">
                    <h5 class="mb-0"><i class="bx bx-book-open me-2"></i>Kartu Rencana Studi (KRS)</h5>
                </div>
                @foreach ($krs as $key => $value)
                    @php
                        $statusAkm = $akm[$key]->status_mahasiswa ?? null;
                        $statusLabel = match ($statusAkm) {
                            'A' => 'Aktif',
                            'C' => 'Cuti',
                            'DO' => 'Drop Out',
                            'N' => 'Non Aktif',
                            default => 'Belum Ada Data',
                        };
                        $statusBadge = match ($statusAkm) {
                            'A' => 'bg-success',
                            'C' => 'bg-warning text-dark',
                            'DO' => 'bg-danger',
                            'N' => 'bg-dark',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <div class="card">
                        <div class="card-header d-flex align-items-center flex-wrap gap-2">
                            <h6 class="mb-0">Tahun Akademik {{ $key }}</h6>
                            <h6 class="mb-0"> -Semester {{ $value['semester'] }}</h6>
                            <span class="badge akm-status-badge {{ $statusBadge }}" data-periode="{{ $key }}">{{ $statusLabel }}</span>
                            <div class="ms-auto d-flex align-items-center gap-2">
                                @can($modul . '.detail.akm.update')
                                    <form action="{{ route('mahasiswa.detail.akm.update', $encryptedNpm) }}"
                                        method="post" class="d-flex align-items-center gap-1 form-update-akm"
                                        data-periode="{{ $key }}">
                                        @csrf
                                        <input type="hidden" name="kode_tahun_akademik" value="{{ $key }}">
                                        <select name="status_mahasiswa" class="form-select form-select-sm"
                                            style="width: auto">
                                            @unless ($statusAkm)
                                                <option value="" selected disabled>- Pilih Status -</option>
                                            @endunless
                                            <option value="A" {{ $statusAkm == 'A' ? 'selected' : '' }}>Aktif
                                            </option>
                                            <option value="C" {{ $statusAkm == 'C' ? 'selected' : '' }}>Cuti
                                            </option>
                                            <option value="DO" {{ $statusAkm == 'DO' ? 'selected' : '' }}>Drop Out
                                            </option>
                                            <option value="N" {{ $statusAkm == 'N' ? 'selected' : '' }}>Non Aktif
                                            </option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-primary"
                                            title="Simpan Status">
                                            <span class="btn-label"><i class="bx bx-save me-0"></i></span>
                                            <span class="spinner-border spinner-border-sm d-none" role="status"
                                                aria-hidden="true"></span>
                                        </button>
                                    </form>
                                @endcan
                                <button onclick="window.print()" class="btn btn-sm btn-primary me-0"><i
                                        class="bx bx-printer mr-1"></i> Cetak</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered krsTable" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Mata Kuliah</th>
                                            <th>Nama Mata Kuliah</th>
                                            <th>Hari</th>
                                            <th>Ruang</th>
                                            <th>Jam Mulai</th>
                                            <th>Jam Selesai</th>
                                            <th>Dosen Pengampu</th>
                                            <th>Kelompok</th>
                                            @canany([$modul . '.detail.krs.destroy', $modul . '.detail.krs.edit'])
                                                <th style="width: 80px">Aksi</th>
                                            @endcanany
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($value['krs'] as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item['kode_mata_kuliah'] }}</td>
                                                <td>{{ $item['nama_mata_kuliah'] }}</td>
                                                <td>{{ $item['hari'] }}</td>
                                                <td>{{ $item['ruang_id'] }}</td>
                                                <td>{{ $item['jam_mulai'] }}</td>
                                                <td>{{ $item['jam_selesai'] }}</td>
                                                <td>{{ $item['dosen_id'] }}</td>
                                                <td>{{ $item['kelompok'] }}</td>
                                                @canany([$modul . '.detail.krs.destroy', $modul . '.detail.krs.edit'])
                                                    <td class="d-flex gap-1">
                                                        @can($modul . '.detail.krs.destroy')
                                                            <form action="{{ route($modul . '.detail.krs.destroy', $item['encrypted_id']) }}"
                                                                method="POST" class="form-delete-krs">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="btn btn-sm btn-danger" type="submit"><i
                                                                        class="bx bx-trash me-0"></i></button>
                                                            </form>
                                                        @endcan
                                                        @can($modul . '.detail.krs.edit')
                                                            <a href="#" class="btn btn-sm btn-warning"><i
                                                                    class="bx bx-pencil me-0"></i></a>
                                                        @endcan
                                                    </td>
                                                @endcanany
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif ($page == 'khs')
            <div class="col-lg-8">
                <div class="d-flex align-items-center mb-3">
                    <h5 class="mb-0"><i class="bx bx-bar-chart-alt-2 me-2"></i>Kartu Hasil Studi (KHS)</h5>
                </div>
                @foreach ($krs as $key => $value)
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h6 class="mb-0">Tahun Akademik {{ $key }}-Semester {{ $value['semester'] }}</h6>
                            <div class="ms-auto">
                                @can($modul . '.khs.cetak')
                                    <a href="{{ route('mahasiswa.khs.cetak', [$encryptedNpm, $key]) }}"
                                        target="_blank" class="btn btn-sm btn-success">
                                        <i class="bx bx-printer mr-1"></i> Cetak KHS
                                    </a>
                                @endcan
                                @can($modul . '.krs.create')
                                    <a href="#" class="btn btn-sm btn-info kontrakMK" data-tahun-akademik="{{ $key }}"
                                        data-npm="{{ Crypt::decrypt($encryptedNpm) }}" data-bs-toggle="modal"
                                        data-bs-target="#modalKontrakMK">
                                        <i class="bx bx-list-check mr-1"></i> Kontrak Mata Kuliah
                                    </a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th style="width: 30px">No</th>
                                            <th style="width: 100px">Mata Kuliah</th>
                                            <th>Nama Mata Kuliah</th>
                                            <th style="width: 100px">Nilai Angka</th>
                                            <th style="width: 100px">Nilai Huruf</th>
                                            @can($modul . '.khs.update-nilai')
                                                <th style="width: 200px">Perbaikan Nilai</th>
                                            @endcan
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($value['krs'] as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item['kode_mata_kuliah'] }}</td>
                                                <td>{{ $item['nama_mata_kuliah'] }}</td>
                                                <td class="nilai-angka">{{ $item['nilai_angka'] }}</td>
                                                <td class="nilai-huruf">{{ $item['nilai_huruf'] }}</td>
                                                @can($modul . '.khs.update-nilai')
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control nilai-update"
                                                                placeholder="Nilai Update">
                                                            <button class="btn btn-outline-success btn-update"
                                                                data-id="{{ $item['encrypted_id'] }}">
                                                                <i class="bx bx-check-circle me-0"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                @endcan
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            <strong>
                                <span>IPS : {{ $value['metadata']['ips'] }}</span>
                                <span class="ms-3">IPK : {{ $value['metadata']['ipk'] }}</span>
                            </strong>
                        </div>
                    </div>
                @endforeach

                <div class="modal fade" id="modalKontrakMK" tabindex="-1" aria-labelledby="modalKontrakMKLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalKontrakMKLabel">Kontrak Mata Kuliah</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <form action="{{ route('mahasiswa.krs.create', $encryptedNpm) }}" method="post">
                                @csrf
                                <div class="modal-body">
                                    <input type="hidden" name="kode_tahun_akademik" id="fkode_tahun_akademik"
                                        value="">
                                    <div class="row">
                                        @foreach ($matakuliah as $item)
                                            <div class="col-md-6">
                                                <div class="form-check form-check-success">
                                                    <input class="form-check-input" name="matakuliah[]" type="checkbox"
                                                        value="{{ $item['id'] }}"
                                                        id="mk{{ $item['id'] }}">
                                                    <label class="form-check-label" for="mk{{ $item['id'] }}">
                                                        {{ $item['kode_mata_kuliah'] . ' ' . $item['nama_mata_kuliah_idn'] . ' [' . $item['semester'] . ']' }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary btn-sm"
                                        data-bs-dismiss="modal">Tutup</button>
                                    <button type="submit" class="btn btn-primary btn-sm">Simpan Mata Kuliah</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bx bx-info-circle" style="font-size: 48px; color: #6c757d;"></i>
                        <p class="text-muted mt-3 mb-0">Pilih menu di samping untuk melihat detail mahasiswa</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
@push('css')
    <link href="{{ asset('') }}assets/plugins/datatable/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
@endpush
@push('js')
    <script src="{{ asset('') }}assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('') }}assets/plugins/datatable/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // KRS Delete
        $(document).on('submit', '.form-delete-krs', function(e) {
            e.preventDefault();
            let form = $(this);
            if (!confirm('Yakin ingin menghapus data KRS ini?')) return;
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        form.closest('tr').remove();
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    alert('Gagal menghapus data');
                }
            });
        });

        // KHS Update Nilai
        $('.btn-update').click(function() {
            let id = $(this).data('id');
            let row = $(this).closest('tr');
            let nilai = row.find('.nilai-update').val();
            let encryptedNpm = '{{ $encryptedNpm ?? '' }}';

            $.ajax({
                url: "/mahasiswa/khs/" + id + "/update-nilai",
                type: "POST",
                data: {
                    nilai: nilai,
                    mahasiswa: encryptedNpm
                },
                success: function(response) {
                    if (response.success === true) {
                        row.find('.nilai-angka').text(response.data.nilai_angka);
                        row.find('.nilai-huruf').text(response.data.nilai_huruf);
                        alert(response.message);
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    alert('Gagal update nilai');
                }
            });
        });

        // KHS Kontrak MK Modal
        $('.kontrakMK').click(function() {
            let tahunAkademik = $(this).data('tahun-akademik');
            $('#fkode_tahun_akademik').val(tahunAkademik);
        });

        // AKM Update Status (per semester)
        const akmStatusMeta = {
            A: {
                label: 'Aktif',
                badge: 'bg-success'
            },
            C: {
                label: 'Cuti',
                badge: 'bg-warning text-dark'
            },
            DO: {
                label: 'Drop Out',
                badge: 'bg-danger'
            },
            N: {
                label: 'Non Aktif',
                badge: 'bg-dark'
            },
        };

        $(document).on('submit', '.form-update-akm', function(e) {
            e.preventDefault();
            let form = $(this);
            let btn = form.find('button[type="submit"]');

            if (btn.prop('disabled')) return;

            let periode = form.data('periode');
            let badge = $('.akm-status-badge[data-periode="' + periode + '"]');

            btn.prop('disabled', true);
            btn.find('.btn-label').addClass('d-none');
            btn.find('.spinner-border').removeClass('d-none');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        let meta = akmStatusMeta[response.data.status_mahasiswa];
                        if (meta) {
                            badge.attr('class', 'badge akm-status-badge ' + meta.badge)
                                .attr('data-periode', periode)
                                .text(meta.label);
                        }
                        Lobibox.notify('success', {
                            pauseDelayOnHover: true,
                            size: 'mini',
                            rounded: true,
                            icon: 'bx bx-check-circle',
                            delayIndicator: false,
                            continueDelayOnInactiveTab: false,
                            position: 'top right',
                            msg: response.message,
                            sound: false,
                        });
                    } else {
                        Lobibox.notify('error', {
                            pauseDelayOnHover: true,
                            size: 'mini',
                            rounded: true,
                            icon: 'bx bx-x-circle',
                            delayIndicator: false,
                            continueDelayOnInactiveTab: false,
                            position: 'top right',
                            msg: response.message,
                            sound: false,
                        });
                    }
                },
                error: function(xhr) {
                    let msg = (xhr.responseJSON && xhr.responseJSON.message) ||
                        'Gagal memperbarui status mahasiswa';
                    Lobibox.notify('error', {
                        pauseDelayOnHover: true,
                        size: 'mini',
                        rounded: true,
                        icon: 'bx bx-x-circle',
                        delayIndicator: false,
                        continueDelayOnInactiveTab: false,
                        position: 'top right',
                        msg: msg,
                        sound: false,
                    });
                },
                complete: function() {
                    btn.prop('disabled', false);
                    btn.find('.btn-label').removeClass('d-none');
                    btn.find('.spinner-border').addClass('d-none');
                }
            });
        });
    </script>
@endpush
