@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">Data Pengguna</h6>
            <div class="ms-auto">
                @can($modul . '.create')
                    <a href="{{ route($modul . '.create') }}" class="btn btn-sm btn-primary">Tambah Pengguna</a>
                @endcan

            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th width="30px">No</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Role</th>
                            <th>Permission</th>
                            @canany([$modul . '.edit', $modul . '.destroy'])
                                <th width="50px">Aksi</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->email }}</td>
                                <td>{{ $item->name }}</td>
                                <td>
                                    <div class="d-flex flex-wrap">
                                        @foreach ($item->roles as $role)
                                            <span class="badge bg-info me-1 mb-1">{{ $role->name }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap">
                                        <div class="d-flex flex-wrap">
                                            @foreach ($item->roles as $role)
                                                @foreach ($role->permissions as $permission)
                                                    <span class="badge bg-info me-1 mb-1">{{ $permission->name }}</span>
                                                @endforeach
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                                @canany([$modul . '.edit', $modul . '.destroy'])
                                    <td>
                                        @can($modul . '.edit')
                                            <a href="{{ route($modul . '.edit', Crypt::encrypt($item->id)) }}"
                                                class="btn btn-warning btn-sm"><i class='bx bx-message-square-edit me-0'></i></a>
                                        @endcan
                                        @can($modul . '.destroy')
                                            <form action="{{ route($modul . '.destroy', Crypt::encrypt($item->id)) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Yakin ingin menghapus data ini?')">
                                                    <i class='bx bx-message-square-x me-0'></i>
                                                </button>
                                            </form>
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
@endsection
