<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function login(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Cek kredensial dan login
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            // Jika login berhasil, redirect ke halaman utama atau dashboard
            return redirect()->route('products');
        } else {
            // Jika login gagal, redirect kembali ke halaman login dengan error
            return redirect()->back()->withErrors(['login' => 'Username atau password salah.'])->withInput();
        }
    }

    public function register(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Buat pengguna baru
        User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('register.form')->with('success', 'Pendaftaran berhasil. Silakan login.');
    }
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login.form');
    }
}
