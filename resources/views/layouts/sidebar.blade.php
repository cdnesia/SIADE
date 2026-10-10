@php
    $menus = [
        [
            'title' => 'Dashboard',
            'icon' => 'bx bx-home-smile',
            'route' => 'dashboard',
        ],
        [
            'title' => 'Mahasiswa Baru',
            'icon' => 'bx bx-user-plus',
            'children' => [
                ['title' => 'Generate NPM', 'route' => 'mahasiswa-baru.index', 'icon' => 'bx bx-radio-circle'],
                ['title' => 'Pencarian Pendaftar', 'route' => 'mahasiswa-baru.pencarian', 'icon' => 'bx bx-radio-circle'],
                ['title' => 'Sinkron Mahasiswa', 'route' => 'mahasiswa.sync', 'icon' => 'bx bx-radio-circle'],
            ],
        ],
        [
            'title' => 'Data Mahasiswa',
            'icon' => 'bx bx-user',
            'children' => [
                ['title' => 'Peserta Didik Baru', 'route' => 'mahasiswa.index', 'icon' => 'bx bx-radio-circle'],
                ['title' => 'Pindahan/Transfer/RPL', 'route' => 'mahasiswa-ptrpl.index', 'icon' => 'bx bx-radio-circle'],
            ],
        ],
        [
            'title' => 'Perkuliahan',
            'icon' => 'bx bx-calendar',
            'children' => [
                ['title' => 'Jadwal Dosen Mengajar', 'route' => 'jadwal-dosen.index', 'icon' => 'bx bx-radio-circle'],
            ],
        ],
        [
            'title' => 'Beasiswa',
            'icon' => 'bx bx-book-open',
            'children' => [
                ['title' => 'Lembaga Beasiswa', 'route' => 'lembaga-beasiswa.index', 'icon' => 'bx bx-radio-circle'],
                ['title' => 'Penerima Beasiswa', 'route' => 'penerima-beasiswa.index', 'icon' => 'bx bx-radio-circle'],
                ['title' => 'Verifikasi Beasiswa', 'route' => 'verifikasi-beasiswa.index', 'icon' => 'bx bx-radio-circle'],
                ['title' => 'Laporan Penerima Beasiswa', 'route' => 'laporan-penerima-beasiswa.index', 'icon' => 'bx bx-radio-circle'],
            ],
        ],

        [
            'title' => 'KIP Kuliah',
            'icon' => 'bx bx-file',
            'children' => [
                ['title' => 'Cek IP', 'route' => 'kipk.index', 'icon' => 'bx bx-radio-circle'],
            ],
        ],
        [
            'title' => 'Master Data',
            'icon' => 'bx bx-sitemap',
            'children' => [
                ['title' => 'Program Studi', 'route' => 'prodi.index', 'icon' => 'bx bx-radio-circle'],
                ['title' => 'Fakultas', 'route' => 'fakultas.index', 'icon' => 'bx bx-radio-circle'],
                ['title' => 'Tahun Akademik', 'route' => 'tahun-akademik.index', 'icon' => 'bx bx-radio-circle'],
                ['title' => 'Kalender Akademik', 'route' => 'kalender-akademik.index', 'icon' => 'bx bx-radio-circle'],
                ['title' => 'Kegiatan Mahasiswa', 'route' => 'kegiatan-mahasiswa.index', 'icon' => 'bx bx-radio-circle'],
                ['title' => 'Kurikulum', 'route' => 'kurikulum.index', 'icon' => 'bx bx-radio-circle'],
            ],
        ],
        [
            'title' => 'Laporan',
            'icon' => 'bx bx-file',
            'children' => [
                ['title' => 'KKN', 'route' => 'laporan-kkn.index', 'icon' => 'bx bx-radio-circle'],
            ],
        ],
        [
            'title' => 'Manajemen',
            'icon' => 'bx bx-cog',
            'children' => [
                ['title' => 'Pengguna', 'route' => 'users.index', 'icon' => 'bx bx-radio-circle'],
                ['title' => 'Roles', 'route' => 'roles.index', 'icon' => 'bx bx-radio-circle'],
                ['title' => 'Permissions', 'route' => 'permissions.index', 'icon' => 'bx bx-radio-circle'],
            ],
        ],
    ];
@endphp

@php
    // Tentukan satu menu aktif: pola route yang cocok dan paling spesifik (terpanjang) yang menang.
    // "x.index" mencakup semua route "x.*", route lain mencakup dirinya sendiri dan turunannya.
    $polaMenu = function ($route) {
        $basis = str_ends_with($route, '.index') ? substr($route, 0, -strlen('.index')) : $route;
        return [$route => strlen($route) + 1, $basis . '.*' => strlen($basis)];
    };
    $routeMenuAktif = null;
    $skorAktif = -1;
    foreach ($menus as $m) {
        foreach (array_merge(isset($m['route']) ? [$m['route']] : [], array_column($m['children'] ?? [], 'route')) as $r) {
            foreach ($polaMenu($r) as $pola => $skor) {
                if ($skor > $skorAktif && Route::is($pola)) {
                    $routeMenuAktif = $r;
                    $skorAktif = $skor;
                }
            }
        }
    }
@endphp

<ul class="metismenu" id="menu">
    @foreach ($menus as $menu)
        @php
            $hasChildren = isset($menu['children']);
            $allowedChildren = $hasChildren
                ? collect($menu['children'])->filter(fn($child) => auth()->user()->can($child['route']))
                : collect();

            $parentActive = isset($menu['route'])
                ? $menu['route'] === $routeMenuAktif
                : $allowedChildren->contains('route', $routeMenuAktif);
        @endphp

        @if (isset($menu['route']) && auth()->user()->can($menu['route']))
            <li class="{{ $parentActive ? 'mm-active' : '' }}">
                <a href="{{ route($menu['route']) }}">
                    <div class="parent-icon"><i class="{{ $menu['icon'] }}"></i></div>
                    <div class="menu-title">{{ $menu['title'] }}</div>
                </a>
            </li>
        @elseif($hasChildren && $allowedChildren->isNotEmpty())
            <li class="{{ $parentActive ? 'mm-active' : '' }}">
                <a href="javascript:void(0)" class="has-arrow">
                    <div class="parent-icon"><i class="{{ $menu['icon'] }}"></i></div>
                    <div class="menu-title">{{ $menu['title'] }}</div>
                </a>
                <ul>
                    @foreach ($allowedChildren as $child)
                        <li class="{{ $child['route'] === $routeMenuAktif ? 'mm-active' : '' }}">
                            <a href="{{ route($child['route']) }}">
                                <i class="{{ $child['icon'] }}"></i>{{ $child['title'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </li>
        @endif
    @endforeach
</ul>
