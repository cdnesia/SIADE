<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    private $modul = 'users';
    public function __construct()
    {

        view()->share('modul', $this->modul);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $d['users'] = User::with(['permissions', 'roles'])->get();
        return view($this->modul . '.view', $d);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $d['users'] = User::with(['permissions', 'roles'])->get();
        $d['roles'] = Role::all();
        $d['data'] = null;
        return view($this->modul . '.form', $d);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|unique:users,email',
            'password' => 'required|min:6',
            'roles'    => 'required|array'
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->username,
            'password' => Hash::make($request->password),
        ]);

        $user->syncRoles($request->roles);

        return redirect()
            ->route($this->modul . '.index')
            ->with('success', 'User berhasil dibuat');
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
        $id = Crypt::decrypt($id);

        $d['data']  = User::with('roles')->findOrFail($id);
        $d['roles'] = Role::all();

        return view($this->modul . '.form', $d);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $id = Crypt::decrypt($id);
        $user = User::findOrFail($id);
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|unique:users,email,' . $id,
            'password' => 'nullable|min:6',
            'roles'    => 'required|array'
        ]);

        $data = [
            'name'  => $request->name,
            'email' => $request->username,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $user->syncRoles($request->roles);

        return redirect()
            ->route($this->modul . '.index')
            ->with('success', 'User berhasil diupdate');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $id = Crypt::decrypt($id);
            $user = User::findOrFail($id);
            if (Auth::id() === $user->id) {
                return redirect()
                    ->back()
                    ->with('error', 'Anda tidak bisa menghapus akun sendiri.');
            }

            $user->delete();

            return redirect()
                ->route($this->modul . '.index')
                ->with('success', 'User berhasil dihapus.');
        } catch (DecryptException $e) {

            return redirect()
                ->route($this->modul . '.index')
                ->with('error', 'ID tidak valid.');
        }
    }
}
