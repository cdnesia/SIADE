@php
    $menus = [
        [
            'title' => 'Dashboard',
            'icon' => 'bx bx-home-smile',
            'route' => 'dashboard',
        ],
        [
            'title' => 'Data Mahasiswa',
            'icon' => 'bx bx-user',
            'route' => 'mahasiswa.index',
        ],
        [
            'title' => 'Beasiswa',
            'icon' => 'bx bx-book-open',
            'children' => [
                ['title' => 'Lembaga Beasiswa', 'route' => 'lembaga-beasiswa.index', 'icon' => 'bx bx-radio-circle'],
                ['title' => 'Penerima Beasiswa', 'route' => 'penerima-beasiswa.index', 'icon' => 'bx bx-radio-circle'],
                ['title' => 'Laporan Penerima Beasiswa', 'route' => 'laporan-penerima-beasiswa.index', 'icon' => 'bx bx-radio-circle'],
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
                ['title' => 'Sync Data', 'route' => 'master.sync.index', 'icon' => 'bx bx-radio-circle'],
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
        [
            'title' => 'Laporan',
            'icon' => 'bx bx-file',
            'children' => [
                ['title' => 'Mahasiswa', 'route' => 'laporan.mahasiswa', 'icon' => 'bx bx-radio-circle'],
                ['title' => 'Keuangan', 'route' => 'laporan.keuangan', 'icon' => 'bx bx-radio-circle'],
            ],
        ],
    ];
@endphp

<ul class="metismenu" id="menu">
    @foreach ($menus as $menu)
        @php
            $hasChildren = isset($menu['children']);
            $allowedChildren = $hasChildren
                ? collect($menu['children'])->filter(fn($child) => auth()->user()->can($child['route']))
                : collect();

            $parentActive = false;
            if (isset($menu['route']) && auth()->user()->can($menu['route'])) {
                $parts = explode('.', $menu['route']);
                array_pop($parts);
                $prefix = implode('.', $parts) . '.*';
                $parentActive = Route::is($prefix);
            } elseif ($hasChildren && $allowedChildren->isNotEmpty()) {
                $childPrefixes = $allowedChildren->pluck('route')->map(function ($r) {
                    $parts = explode('.', $r);
                    array_pop($parts);
                    return implode('.', $parts) . '.*';
                });
                foreach ($childPrefixes as $prefix) {
                    if (Route::is($prefix)) {
                        $parentActive = true;
                        break;
                    }
                }
            }
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
                        @php
                            $parts = explode('.', $child['route']);
                            array_pop($parts);
                            $prefix = implode('.', $parts) . '.*';
                            $childActive = Route::is($prefix);
                        @endphp
                        <li class="{{ $childActive ? 'mm-active' : '' }}">
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
