<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission menu Wisuda › Cek Mahasiswa RPL. Diberikan otomatis ke role yang
 * sudah boleh membuka rekap wisuda per angkatan.
 */
return new class extends Migration
{
    private const PERMISSION = ['wisuda.rpl.index', 'wisuda.rpl.export'];

    public function up(): void
    {
        foreach (self::PERMISSION as $nama) {
            Permission::firstOrCreate(['name' => $nama, 'guard_name' => 'web']);
        }

        Role::whereHas('permissions', fn ($q) => $q->where('name', 'wisuda.rekap.index'))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo(self::PERMISSION));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', self::PERMISSION)->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
