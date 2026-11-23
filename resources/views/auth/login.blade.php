@extends('layouts.app')

@section('title', 'Login Portal - Espega Sinau (SMPN 3 Ngawen)')

@section('body')
<div class="min-h-[100svh] flex items-center justify-center p-3 sm:p-4 bg-slate-100">
    <div class="w-full max-w-4xl bg-white rounded-2xl sm:rounded-3xl shadow-md border border-slate-200 overflow-hidden flex flex-col md:flex-row">
        
        <!-- Sisi Kiri: Branding & Informasi Sekolah -->
        <div class="md:w-5/12 bg-brand-900 p-5 sm:p-8 md:p-10 text-white flex flex-col justify-between border-b md:border-b-0 md:border-r border-brand-800">
            <div>
            <a href="{{ route('landing') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-300 hover:text-white transition-colors mb-4 sm:mb-8">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Kembali ke Website Utama</span>
                </a>

                <div class="mb-4 sm:mb-6">
                    <img src="/assets/img/logo-espega.png" alt="Logo SMPN 3 Ngawen" class="w-12 h-12 sm:w-16 sm:h-16 object-contain rounded-xl">
                </div>

                <span class="inline-block px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-brand-800 text-amber-300 border border-brand-700 mb-2">
                    Portal Asesmen & Akademik
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Espega Sinau</h1>
                <p class="text-xs sm:text-sm text-slate-300 mt-2 leading-relaxed">
                    Sistem Ujian Digital & Pelayanan Terpadu SMP Negeri 3 Ngawen.
                </p>
            </div>

            <div class="hidden sm:block pt-8 border-t border-brand-800 space-y-2.5">
                <div class="flex items-center gap-2.5 text-xs text-slate-300">
                    <i class="fa-solid fa-shield-halved text-emerald-400"></i>
                    <span>CBT Terproteksi & Mandiri</span>
                </div>
                <div class="flex items-center gap-2.5 text-xs text-slate-300">
                    <i class="fa-solid fa-clock text-amber-400"></i>
                    <span>Real-time Timer & Auto-Submit</span>
                </div>
                <div class="text-[11px] text-slate-400 pt-2">
                    Religius • Berprestasi • Berbudaya
                </div>
            </div>
        </div>

        <!-- Sisi Kanan: Form Login -->
        <div class="md:w-7/12 p-5 sm:p-8 md:p-12 flex flex-col justify-center">
            <div class="mb-6 sm:mb-8">
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Selamat Datang</h2>
                <p class="text-sm text-slate-500 mt-1">Gunakan Username / NIS & Kode Token Akses Anda</p>
            </div>

            @if(session('error'))
            <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs sm:text-sm flex items-start gap-3">
                <i class="fa-solid fa-circle-exclamation text-base shrink-0 mt-0.5"></i>
                <div>{{ session('error') }}</div>
            </div>
            @endif

            @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs sm:text-sm flex items-start gap-3">
                <i class="fa-solid fa-circle-check text-base shrink-0 mt-0.5"></i>
                <div>{{ session('success') }}</div>
            </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Username / NIS Input -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Username / NIS</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-user text-sm"></i>
                        </div>
                        <input type="text" name="username" value="{{ old('username') }}" required autocomplete="username"
                               placeholder="Contoh: 3157 (Siswa) atau admin"
                               class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all">
                    </div>
                </div>

                <!-- Token / Password Input -->
                <div>
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Kode Akses / Password</label>
                        <span class="text-[11px] text-slate-400">Token 6 digit (Siswa)</span>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-key text-sm"></i>
                        </div>
                        <input type="password" name="token" id="tokenInput" required
                               placeholder="Masukkan kode token atau password"
                               class="w-full pl-10 pr-10 py-3 rounded-xl border border-slate-200 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all">
                        <button type="button" id="togglePasswordBtn" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                            <i class="fa-solid fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span>Ingat sesi masuk di perangkat ini</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-md shadow-blue-600/25 transition-all flex items-center justify-center gap-2 hover:-translate-y-0.5">
                    <span>Masuk ke Akun</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </form>

            <!-- Petunjuk Login Ringkas -->
            <div class="mt-6 sm:mt-8 pt-5 sm:pt-6 border-t border-slate-100 text-xs text-slate-500 space-y-1">
                <div class="font-semibold text-slate-700"><i class="fa-solid fa-info-circle text-brand-600 mr-1"></i> Bantuan Akses Masuk:</div>
                <div>• <strong>Siswa:</strong> Gunakan NIS sebagai Username dan Kode Token Kartu Ujian.</div>
                <div>• <strong>Guru / Admin:</strong> Gunakan username terdaftar dan password/token Anda.</div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const tokenInput = document.getElementById('tokenInput');
    const toggleIcon = document.getElementById('togglePasswordIcon');

    toggleBtn.addEventListener('click', function () {
        if (tokenInput.type === 'password') {
            tokenInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            tokenInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    });
</script>
@endpush
@endsection

