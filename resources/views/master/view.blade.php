@extends('layouts.app')
@section('content')
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4">
        @can($modul . '.fakultas')
            <div class="col">
                <div class="card radius-10 border-start border-0 border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Jumlah Fakultas</p>
                                <h4 class="my-1 text-info">{{ $fakultas }}</h4>
                                <p class="mb-0 font-13">Sinkronisasi terakhir</p>
                            </div>
                            <a href="{{ route($modul . '.fakultas') }}"
                                class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i
                                    class='bx bx-sync'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
        @can($modul . '.prodi')
            <div class="col">
                <div class="card radius-10 border-start border-0 border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Jumlah Program Studi</p>
                                <h4 class="my-1 text-info">{{ $prodi }}</h4>
                                <p class="mb-0 font-13">Sinkronisasi terakhir</p>
                            </div>
                            <a href="{{ route($modul . '.prodi') }}"
                                class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i
                                    class='bx bx-sync'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
        @can($modul . '.jenis-matakuliah')
            <div class="col">
                <div class="card radius-10 border-start border-0 border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Jenis Mata Kuliah</p>
                                <h4 class="my-1 text-info">{{ $jenis_matakuliah }}</h4>
                                <p class="mb-0 font-13">Sinkronisasi terakhir</p>
                            </div>
                            <a href="{{ route($modul . '.jenis-matakuliah') }}"
                                class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i
                                    class='bx bx-sync'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
        @can($modul . '.kurikulum')
            <div class="col">
                <div class="card radius-10 border-start border-0 border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Kurikulum</p>
                                <h4 class="my-1 text-info">{{ $kurikulum }}</h4>
                                <p class="mb-0 font-13">Sinkronisasi terakhir</p>
                            </div>
                            <a href="{{ route($modul . '.kurikulum') }}"
                                class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i
                                    class='bx bx-sync'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
        @can($modul . '.kurikulum-prodi')
            <div class="col">
                <div class="card radius-10 border-start border-0 border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Kurikulum Program Studi</p>
                                <h4 class="my-1 text-info">{{ $kurikulum_prodi }}</h4>
                                <p class="mb-0 font-13">Sinkronisasi terakhir</p>
                            </div>
                            <a href="{{ route($modul . '.kurikulum-prodi') }}"
                                class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i
                                    class='bx bx-sync'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
        @can($modul . '.kurikulum-mata-kuliah')
            <div class="col">
                <div class="card radius-10 border-start border-0 border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Kurikulum Mata Kuliah</p>
                                <h4 class="my-1 text-info">{{ $kurikulum_makul }}</h4>
                                <p class="mb-0 font-13">Sinkronisasi terakhir</p>
                            </div>
                            <a href="{{ route($modul . '.kurikulum-mata-kuliah') }}"
                                class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i
                                    class='bx bx-sync'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
        @can($modul . '.skala-nilai')
            <div class="col">
                <div class="card radius-10 border-start border-0 border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Skala Nilai</p>
                                <h4 class="my-1 text-info">{{ $skala_nilai }}</h4>
                                <p class="mb-0 font-13">Sinkronisasi terakhir</p>
                            </div>
                            <a href="{{ route($modul . '.skala-nilai') }}"
                                class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i
                                    class='bx bx-sync'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
        @can($modul . '.krs')
            <div class="col">
                <div class="card radius-10 border-start border-0 border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Kartu Rencana Studi</p>
                                <h4 class="my-1 text-info">{{ $krs }}</h4>
                                <p class="mb-0 font-13">Sinkronisasi terakhir</p>
                            </div>
                            <a href="{{ route($modul . '.krs') }}"
                                class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i
                                    class='bx bx-sync'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
        @can($modul . '.tahun-akademik')
            <div class="col">
                <div class="card radius-10 border-start border-0 border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tahun Akademik</p>
                                <h4 class="my-1 text-info">{{ $tahun_akademik }}</h4>
                                <p class="mb-0 font-13">Sinkronisasi terakhir</p>
                            </div>
                            <a href="{{ route($modul . '.tahun-akademik') }}"
                                class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i
                                    class='bx bx-sync'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
        @can($modul . '.jadwal-perkuliahan')
            <div class="col">
                <div class="card radius-10 border-start border-0 border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Jadwal Perkuliahan</p>
                                <h4 class="my-1 text-info">{{ $jadwal_perkuliahan }}</h4>
                                <p class="mb-0 font-13">Sinkronisasi terakhir</p>
                            </div>
                            <a href="{{ route($modul . '.jadwal-perkuliahan') }}"
                                class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i
                                    class='bx bx-sync'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
        @can($modul . '.penerima-beasiswa')
            <div class="col">
                <div class="card radius-10 border-start border-0 border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Penerima Beasiswa</p>
                                <h4 class="my-1 text-info">{{ $penerima_beasiswa }}</h4>
                                <p class="mb-0 font-13">Sinkronisasi terakhir</p>
                            </div>
                            <a href="{{ route($modul . '.penerima-beasiswa') }}"
                                class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i
                                    class='bx bx-sync'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
        @can($modul . '.jadwal-pertemuan')
            <div class="col">
                <div class="card radius-10 border-start border-0 border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Jadwal Pertemuan</p>
                                <h4 class="my-1 text-info">{{ $jadwal_pertemuan }}</h4>
                                <p class="mb-0 font-13">Sinkronisasi terakhir</p>
                            </div>
                            <a href="{{ route($modul . '.jadwal-pertemuan') }}"
                                class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i
                                    class='bx bx-sync'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
        @can($modul . '.jadwal-pertemuan-absensi')
            <div class="col">
                <div class="card radius-10 border-start border-0 border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Jadwal Pertemuan Absensi</p>
                                <h4 class="my-1 text-info">{{ $jadwal_pertemuan_absensi }}</h4>
                                <p class="mb-0 font-13">Sinkronisasi terakhir</p>
                            </div>
                            <a href="{{ route($modul . '.jadwal-pertemuan-absensi') }}"
                                class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i
                                    class='bx bx-sync'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    </div>
@endsection
