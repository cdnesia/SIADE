@extends('layouts.app')
@section('content')
    @php
        $kurikulumId = Crypt::encrypt($kurikulum->id);
        $tidakDinilai = \App\Models\KurikulumMataKuliahPertemuan::TIDAK_DINILAI;
    @endphp
    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2 py-3">
            <div>
                <h6 class="mb-0">Bobot Nilai per Pertemuan</h6>
                <small class="text-muted">
                    {{ $data->kode_mata_kuliah }} &mdash; {{ $data->nama_mata_kuliah_idn }}
                    &middot; {{ $data->sks_mata_kuliah }} SKS &middot; Semester {{ $data->semester }}
                    &middot; Kurikulum {{ $kurikulum->nama_kurikulum }}
                </small>
            </div>
            <div class="ms-auto">
                <a href="{{ route($modul . '.index', [$kurikulumId, 'prodi' => $data->kode_program_studi]) }}"
                    class="btn btn-sm btn-outline-secondary"><i class='bx bx-arrow-back'></i> Kembali</a>
            </div>
        </div>

        <form method="POST" action="{{ route($modul . '.bobot.update', [$kurikulumId, Crypt::encrypt($data->id)]) }}"
            id="form-bobot">
            @csrf
            @method('PUT')

            <div class="card-body">
                <div class="alert alert-info small mb-3">
                    <i class='bx bx-info-circle'></i>
                    Setting ini berlaku untuk semua jadwal/kelas mata kuliah ini
                    (<strong>{{ $jumlah_jadwal }}</strong> jadwal). Total bobot harus <strong>100%</strong>.
                    Kosongkan semua bobot (0%) untuk menghapus setting.
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger small">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $pesan)
                                <li>{{ $pesan }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="border rounded-3 p-3 mb-3 @error('is_penilaian_obe') border-danger @enderror">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_penilaian_obe"
                            name="is_penilaian_obe" value="1"
                            {{ old('is_penilaian_obe', $data->is_penilaian_obe) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="is_penilaian_obe">
                            Gunakan Penilaian OBE untuk mata kuliah ini
                        </label>
                    </div>
                    <div class="small text-muted mt-1">
                        Jika dicentang, nilai akhir di SIKAP (template, import, dan rekap nilai dosen) dihitung dari
                        bobot per pertemuan di bawah. Jika tidak dicentang, tetap memakai komponen lama:
                        Sikap 15%, Kuis 10%, UTS 10%, KU 20%, KH 20%, UAS 25%.
                    </div>
                    @error('is_penilaian_obe')
                        <div class="small text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-bagi-rata"
                        title="Bagi 100% secara merata ke semua pertemuan yang dinilai">
                        <i class='bx bx-git-merge'></i> Bagi Rata
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-template"
                        title="Isi contoh: Tugas tiap pertemuan, UTS pertemuan 8, UAS pertemuan terakhir">
                        <i class='bx bx-layout'></i> Isi Template Standar
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="btn-reset">
                        <i class='bx bx-reset'></i> Kosongkan
                    </button>
                </div>

                <div class="table-responsive border rounded-3">
                    <table class="table align-middle mb-0">
                        <thead class="small text-muted bg-light">
                            <tr>
                                <th style="width:90px">Pertemuan</th>
                                <th style="min-width:180px">Jenis Penilaian</th>
                                <th style="width:150px">Bobot (%)</th>
                                <th style="min-width:200px">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($no = 1; $no <= $jumlah_pertemuan; $no++)
                                @php
                                    $s = $setting->get($no);
                                    $jenisAktif = old("pertemuan.$no.jenis_penilaian", $s->jenis_penilaian ?? $tidakDinilai);
                                    $bobotAktif = old("pertemuan.$no.bobot", $s ? (float) $s->bobot : 0);
                                @endphp
                                <tr class="baris-pertemuan" data-no="{{ $no }}">
                                    <td class="fw-semibold">
                                        <label for="jenis-{{ $no }}" class="mb-0">Ke-{{ $no }}</label>
                                    </td>
                                    <td>
                                        <select name="pertemuan[{{ $no }}][jenis_penilaian]" id="jenis-{{ $no }}"
                                            class="form-select form-select-sm input-jenis @error("pertemuan.$no.jenis_penilaian") is-invalid @enderror">
                                            @foreach ($jenis as $kode => $label)
                                                <option value="{{ $kode }}" {{ $jenisAktif == $kode ? 'selected' : '' }}>
                                                    {{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="pertemuan[{{ $no }}][bobot]"
                                                class="form-control input-bobot @error("pertemuan.$no.bobot") is-invalid @enderror"
                                                min="0" max="100" step="0.01" value="{{ $bobotAktif }}"
                                                aria-label="Bobot pertemuan {{ $no }}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="pertemuan[{{ $no }}][keterangan]" maxlength="255"
                                            class="form-control form-control-sm"
                                            value="{{ old("pertemuan.$no.keterangan", $s->keterangan ?? '') }}"
                                            placeholder="Contoh: Tugas makalah bab 1" aria-label="Keterangan pertemuan {{ $no }}">
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white d-flex flex-wrap align-items-center gap-3 py-3 position-sticky bottom-0">
                <div>
                    <div class="small text-muted">Total Bobot</div>
                    <div class="fs-5 fw-bold"><span id="total-bobot">0</span>%
                        <span id="status-bobot" class="badge ms-1"></span>
                    </div>
                </div>
                <div id="rekap-jenis" class="small text-muted"></div>
                <div id="peringatan-obe" class="small text-danger d-none">
                    Penilaian OBE butuh total bobot tepat 100%.
                </div>
                <div class="ms-auto">
                    <button type="submit" class="btn btn-primary" id="btn-simpan">
                        <i class='bx bx-save'></i> Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
@push('js')
    <script>
        $(function() {
            const TIDAK_DINILAI = @json($tidakDinilai);
            const labelJenis = @json($jenis);
            const $baris = $('.baris-pertemuan');

            // Hitung ulang total & rekap per jenis setiap kali ada perubahan
            function hitung() {
                let total = 0;
                const rekap = {};
                $baris.each(function() {
                    const jenis = $(this).find('.input-jenis').val();
                    const bobot = parseFloat($(this).find('.input-bobot').val()) || 0;
                    total += bobot;
                    if (jenis !== TIDAK_DINILAI && bobot > 0) {
                        rekap[jenis] = (rekap[jenis] || 0) + bobot;
                    }
                    // Bobot dikunci 0 jika pertemuan tidak dinilai
                    $(this).find('.input-bobot').prop('readonly', jenis === TIDAK_DINILAI);
                });
                total = Math.round(total * 100) / 100;

                $('#total-bobot').text(total);
                const pas = total === 100, kosong = total === 0;
                $('#status-bobot')
                    .attr('class', 'badge ms-1 ' + (pas ? 'bg-success' : (kosong ? 'bg-secondary' : 'bg-warning text-dark')))
                    .text(pas ? 'Sesuai' : (kosong ? 'Kosong' : (total > 100 ? 'Lebih ' + (Math.round((total - 100) * 100) / 100) + '%' : 'Kurang ' + (Math.round((100 - total) * 100) / 100) + '%')));
                const obe = $('#is_penilaian_obe').is(':checked');
                $('#btn-simpan').prop('disabled', !(pas || (kosong && !obe)));
                $('#peringatan-obe').toggleClass('d-none', !(obe && !pas));

                $('#rekap-jenis').html(Object.entries(rekap)
                    .map(([k, v]) => `${labelJenis[k]}: <strong>${Math.round(v * 100) / 100}%</strong>`)
                    .join(' &middot; '));
            }

            // Bagi 100% merata ke pertemuan yang dinilai; sisa pembulatan masuk ke baris terakhir
            function bagiRata() {
                const $dinilai = $baris.filter((_, el) => $(el).find('.input-jenis').val() !== TIDAK_DINILAI);
                if (!$dinilai.length) {
                    Lobibox.notify('warning', { size: 'mini', rounded: true, position: 'top right', sound: false,
                        msg: 'Pilih jenis penilaian minimal pada satu pertemuan.' });
                    return;
                }
                const per = Math.floor(10000 / $dinilai.length) / 100;
                $dinilai.each(function(i) {
                    const nilai = i === $dinilai.length - 1 ? Math.round((100 - per * ($dinilai.length - 1)) * 100) / 100 : per;
                    $(this).find('.input-bobot').val(nilai);
                });
                hitung();
            }

            $('#is_penilaian_obe').on('change', hitung);

            $baris.on('change input', '.input-jenis, .input-bobot', function() {
                const $tr = $(this).closest('tr');
                if ($tr.find('.input-jenis').val() === TIDAK_DINILAI) {
                    $tr.find('.input-bobot').val(0);
                }
                hitung();
            });

            $('#btn-bagi-rata').on('click', bagiRata);

            $('#btn-template').on('click', function() {
                const akhir = $baris.length;
                const uts = Math.min(8, akhir - 1);
                // Tugas 30% dibagi rata, UTS 30%, UAS 40%
                const tugas = $baris.filter((_, el) => ![uts, akhir].includes($(el).data('no')));
                if (!tugas.length) {
                    Lobibox.notify('warning', { size: 'mini', rounded: true, position: 'top right', sound: false,
                        msg: 'Jumlah pertemuan terlalu sedikit untuk template standar.' });
                    return;
                }
                const perTugas = Math.floor(3000 / tugas.length) / 100;
                $baris.each(function() {
                    const no = $(this).data('no');
                    let jenis = 'TUGAS', bobot = perTugas;
                    if (no === uts) { jenis = 'UTS'; bobot = 30; }
                    if (no === akhir) { jenis = 'UAS'; bobot = 40; }
                    $(this).find('.input-jenis').val(jenis);
                    $(this).find('.input-bobot').val(bobot);
                });
                // Sisa pembulatan tugas ditambahkan ke tugas terakhir
                const sisa = Math.round((30 - perTugas * tugas.length) * 100) / 100;
                const $last = tugas.last().find('.input-bobot');
                $last.val(Math.round((parseFloat($last.val()) + sisa) * 100) / 100);
                hitung();
            });

            $('#btn-reset').on('click', function() {
                if (!confirm('Kosongkan semua setting bobot pertemuan?')) return;
                $baris.find('.input-jenis').val(TIDAK_DINILAI);
                $baris.find('.input-bobot').val(0);
                hitung();
            });

            hitung();
        });
    </script>
@endpush
