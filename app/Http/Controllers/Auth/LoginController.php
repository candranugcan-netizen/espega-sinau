<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'token' => 'required|string',
        ]);

        $username = trim($request->input('username'));
        $tokenInput = trim($request->input('token'));

        // Cari pengguna berdasarkan username
        $user = User::with('role')->where('username', $username)->first();

        if (!$user) {
            return back()->withInput()->with('error', 'Username / NIS tidak terdaftar dalam sistem.');
        }

        // Cek kecocokan token atau password (memastikan seluruh password ter-hash dalam database)
        $isMatched = false;

        // 1. Verifikasi kecocokan via Hash Bcrypt terhadap kolom password
        if (!empty($user->password) && Hash::check($tokenInput, $user->password)) {
            $isMatched = true;
        }
        // 2. Verifikasi dengan token siswa / legacy token (auto-rehash password ke DB)
        elseif (!empty($user->token) && $user->token === $tokenInput) {
            $isMatched = true;
            $user->update(['password' => $tokenInput]); // otomatis di-hash oleh mutator User
        }
        // 3. Fallback jika masih ada string plaintext di kolom password (langsung auto-hash ke DB)
        elseif (!empty($user->password) && $user->password === $tokenInput) {
            $isMatched = true;
            $user->update(['password' => $tokenInput]); // otomatis di-hash oleh mutator User
        }

        if (!$isMatched) {
            return back()->withInput()->with('error', 'Token / Password yang dimasukkan salah!');
        }

        // Cek status blokir akun
        if ($user->isBlocked()) {
            return back()->with('error', 'Akun Anda sedang terblokir karena terindikasi pelanggaran. Silakan hubungi Administrator atau Proktor.');
        }

        // Login ke session Laravel
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return $this->redirectBasedOnRole($user);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }

    protected function redirectBasedOnRole(User $user)
    {
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isGuru()) {
            return redirect()->route('guru.dashboard');
        } else {
            return redirect()->route('siswa.dashboard');
        }
    }
}

