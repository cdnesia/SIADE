<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $credentials = [
            'email' => $request->username,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect('/dashboard');
        }

        $mahasiswa = DB::table('master_mahasiswa')->where('npm', $request->username)->first();

        if ($mahasiswa && $request->password == $mahasiswa->npm) {
            $user = User::where('email', $mahasiswa->npm)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $mahasiswa->nama_mahasiswa,
                    'email' => $mahasiswa->npm,
                    'password' => Hash::make($mahasiswa->npm),
                ]);
            }

            Auth::login($user);
            $request->session()->regenerate();

            return redirect('/dashboard');
        }

        return back()->withErrors([
            'username' => 'Username atau password salah',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
