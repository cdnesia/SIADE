<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    private $modul = 'roles';
    public function __construct()
    {

        view()->share('modul', $this->modul);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $d['roles'] = Role::with('permissions')->get();
        return view($this->modul . '.view', $d);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $d['permissions'] = Permission::pluck('name');

        $d['data'] = null;
        return view($this->modul . '.form', $d);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:roles,name',
            'permissions' => 'required|array'
        ]);

        $role = Role::create([
            'name'       => $request->name,
            'guard_name' => 'web'
        ]);

        $role->syncPermissions($request->permissions);

        return redirect()
            ->route($this->modul . '.index')
            ->with('success', 'Role berhasil dibuat');
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
        try {
            $id = Crypt::decrypt($id);
            $d['data'] = Role::with('permissions')->findOrFail($id);
            $d['permissions'] = Permission::pluck('name');
            return view($this->modul . '.form', $d);
        } catch (DecryptException $e) {
            return redirect()
                ->route($this->modul . '.index')
                ->with('error', 'ID tidak valid.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {

            $id = Crypt::decrypt($id);
            $role = Role::findOrFail($id);
            $request->validate([
                'name'        => 'required|string|max:255|unique:roles,name,' . $id,
                'permissions' => 'required|array'
            ]);

            $role->update([
                'name' => $request->name
            ]);

            $role->syncPermissions($request->permissions);

            return redirect()
                ->route($this->modul . '.index')
                ->with('success', 'Role berhasil diupdate');
        } catch (DecryptException $e) {

            return redirect()
                ->route($this->modul . '.index')
                ->with('error', 'ID tidak valid.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {

            $id = Crypt::decrypt($id);

            $role = Role::findOrFail($id);

            if ($role->name === 'superadmin') {
                return back()->with('error', 'Role superadmin tidak bisa dihapus.');
            }

            $role->delete();

            return redirect()
                ->route($this->modul . '.index')
                ->with('success', 'Role berhasil dihapus.');
        } catch (DecryptException $e) {

            return redirect()
                ->route($this->modul . '.index')
                ->with('error', 'ID tidak valid.');
        }
    }
}
