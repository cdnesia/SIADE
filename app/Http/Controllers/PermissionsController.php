<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsController extends Controller
{
    private $modul = 'permissions';
    public function __construct()
    {

        view()->share('modul', $this->modul);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $d['permissions'] = Permission::orderBy('name')->get();
        return view($this->modul . '.view', $d);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $routes = collect(Route::getRoutes())
            ->pluck('action.as')
            ->filter()
            ->reject(fn($name) => in_array($name, ['login', 'logout', 'storage.local']))
            ->values();

        $existingPermissions = Permission::pluck('name')->toArray();
        $d['route'] = $routes->reject(fn($name) => in_array($name, $existingPermissions))
            ->values();
        $d['data'] = null;
        return view($this->modul . '.form', $d);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'permissions' => 'required|array'
        ]);

        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        foreach ($request->permissions as $routeName) {
            $permission = Permission::firstOrCreate([
                'name' => $routeName,
                'guard_name' => 'web'
            ]);

            if (!$superadmin->hasPermissionTo($permission)) {
                $superadmin->givePermissionTo($permission);
            }
        }

        return redirect()
            ->route($this->modul . '.index')
            ->with('success', 'Permission berhasil disimpan');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $id = Crypt::decrypt($id);
            $permission = Permission::findOrFail($id);
            $permission->delete();

            return redirect()
                ->route($this->modul . '.index')
                ->with('success', 'Permission berhasil dihapus');
        } catch (DecryptException $e) {
            return redirect()
                ->route($this->modul . '.index')
                ->with('error', 'ID tidak valid');
        }
    }
}
