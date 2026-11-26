<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission menu Perkuliahan › MK Dobel Mahasiswa. Diberikan otomatis ke role
 * yang sudah boleh menghapus KRS mahasiswa (mahasiswa.detail.krs.destroy).
 */
return new class extends Migration
{
    private const PERMISSION = ['mk-dobel.index', 'mk-dobel.update', 'mk-dobel.destroy'];

    public function up(): void
    {
        foreach (self::PERMISSION as $nama) {
            Permission::firstOrCreate(['name' => $nama, 'guard_name' => 'web']);
        }

        Role::whereHas('permissions', fn ($q) => $q->where('name', 'mahasiswa.detail.krs.destroy'))
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
