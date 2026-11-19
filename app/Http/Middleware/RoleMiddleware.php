<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $user = Auth::user();

        if ($user->isBlocked()) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Akun Anda sedang terblokir. Hubungi Admin/Proktor.');
        }

        $userRole = strtolower($user->role ? $user->role->nama_role : ($user->isAdmin() ? 'admin' : ($user->isGuru() ? 'guru' : 'siswa')));

        if (!in_array($userRole, array_map('strtolower', $roles))) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki otoritas untuk membuka modul ini.');
        }

        return $next($request);
    }
}

