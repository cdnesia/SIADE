<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission menu Kurikulum › Mata Kuliah › Bobot Pertemuan. Diberikan otomatis
 * ke role yang sudah boleh mengedit mata kuliah kurikulum.
 */
return new class extends Migration
{
    private const PERMISSION = ['kurikulum.matakuliah.bobot', 'kurikulum.matakuliah.bobot.update'];

    public function up(): void
    {
        foreach (self::PERMISSION as $nama) {
            Permission::firstOrCreate(['name' => $nama, 'guard_name' => 'web']);
        }

        Role::whereHas('permissions', fn ($q) => $q->where('name', 'kurikulum.matakuliah.edit'))
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
