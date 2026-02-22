<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::find(1);
        if ($user) {
            $user->delete();
        }

        $user = User::create([
            'id' => 1,
            'name' => 'Administrator',
            'email' => 'superadmin@gmail.com',
            'password' => Hash::make('password'),
        ]);

        $routes = collect(Route::getRoutes())
            ->pluck('action.as')
            ->filter()
            ->reject(fn($name) => in_array($name, ['login', 'logout', 'storage']))
            ->values();

        foreach ($routes as $routeName) {
            Permission::firstOrCreate([
                'name' => $routeName,
                'guard_name' => 'web',
            ]);
        }

        $permissions = $routes;

        $role = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        if ($permissions->isNotEmpty()) {
            $role->syncPermissions($permissions);
        }

        $user->assignRole($role);

        $this->command->info('✅ Superadmin user, role, dan permission telah dibuat!');
    }
}
