@extends('layouts.app')

@section('body')
<div class="flex h-[100dvh] overflow-hidden bg-slate-100">

    <!-- Sidebar Navigation -->
    <aside id="sidebar" class="w-64 bg-slate-900 text-slate-300 flex flex-col shrink-0 transition-all duration-300 z-40 fixed md:static inset-y-0 -left-64 md:left-0">
        <!-- Brand Header -->
        <div class="h-20 flex shrink-0 items-center gap-3 px-5 bg-slate-950 border-b border-slate-800/80">
            <img src="/assets/img/logo-espega.png" alt="Logo SMPN 3 Ngawen" class="w-10 h-10 object-contain rounded-xl shrink-0">
            <div>
                <span class="block text-base font-extrabold text-white tracking-tight">ESPEGA SINAU</span>
                <span class="block text-[10px] font-bold text-cyan-400 tracking-widest uppercase">Panel Guru</span>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="min-h-0 flex-1 overflow-y-auto px-3 py-4 sm:px-4 sm:py-6 space-y-1.5 text-sm font-medium">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider px-3 mb-2">Utama</div>
            <a href="{{ route('guru.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('guru.dashboard') ? 'bg-cyan-600 text-white font-bold' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-gauge-high w-5 text-center"></i>
                <span>Dashboard Guru</span>
            </a>
            <a href="{{ route('guru.administration.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('guru.administration.*') ? 'bg-cyan-600 text-white font-bold' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-folder-open w-5 text-center"></i>
                <span>Administrasi Guru</span>
            </a>

            <a href="{{ route('guru.berita.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('guru.berita.*') ? 'bg-cyan-600 text-white font-bold' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-book-bookmark w-5 text-center"></i>
                <span>Buku Harian Guru</span>
            </a>
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider px-3 mt-4 sm:mt-6 mb-2">Asesmen dan Ujian</div>
            <a href="{{ route('guru.bank_soal.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('guru.bank_soal.*') || request()->routeIs('guru.questions.*') ? 'bg-cyan-600 text-white font-bold' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-layer-group w-5 text-center"></i>
                <span>Bank Soal Saya</span>
            </a>

            <a href="{{ route('guru.results.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('guru.results.*') ? 'bg-cyan-600 text-white font-bold' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-square-poll-vertical w-5 text-center"></i>
                <span>Hasil & Nilai Siswa</span>
            </a>

        </nav>

        <!-- Sidebar Footer -->
        <div class="shrink-0 p-3 sm:p-4 bg-slate-950 border-t border-slate-800/80 flex items-center justify-between gap-2.5">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <div class="w-9 h-9 rounded-xl bg-slate-800 text-cyan-400 flex items-center justify-center font-bold text-sm shrink-0">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold text-white truncate" title="{{ auth()->user()->nama }}">{{ auth()->user()->nama }}</div>
                    <div class="text-[10px] text-slate-400 truncate">Guru Pengampu</div>
                </div>
            </div>
            <a href="{{ route('logout') }}" class="w-8 h-8 shrink-0 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-900 flex items-center justify-center transition-colors" title="Keluar">
                <i class="fa-solid fa-power-off text-sm"></i>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="min-w-0 flex-1 flex flex-col overflow-hidden">
        
        <!-- Topbar Header -->
        <header class="min-h-16 sm:h-20 bg-white border-b border-slate-200 px-3 sm:px-6 flex items-center justify-between gap-3 shrink-0">
            <div class="flex min-w-0 items-center gap-2 sm:gap-4">
                <button type="button" onclick="toggleSidebar()" class="md:hidden min-h-10 min-w-10 shrink-0 rounded-lg p-2 text-slate-600 hover:bg-slate-100" aria-label="Buka navigasi">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <h1 class="min-w-0 truncate text-sm font-bold text-slate-900 sm:text-lg">@yield('page_title', 'Panel Guru')</h1>
            </div>

            <div class="flex shrink-0 items-center gap-2 sm:gap-4">
                @if(isset($activeAcademicYear) && $activeAcademicYear)
                    <div class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-brand-50 border border-brand-200 text-xs font-semibold text-brand-700" title="Tahun Ajaran Aktif">
                        <i class="fa-solid fa-calendar-check text-brand-600"></i>
                        <span>TA: {{ $activeAcademicYear->full_label }}</span>
                    </div>
                @endif
                <a href="{{ route('landing') }}" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Web Sekolah</span>
                </a>
                <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>
                <div class="hidden items-center gap-2 text-xs text-slate-600 sm:flex">
                    <span class="max-w-[180px] truncate font-bold text-slate-800 sm:max-w-xs" title="{{ auth()->user()->nama }}">{{ auth()->user()->nama }}</span>
                </div>
            </div>
        </header>

        <!-- Main Body Scrollable -->
        <main class="min-h-0 min-w-0 flex-1 overflow-y-auto p-3 sm:p-4 md:p-8">
            @yield('content')
        </main>

    </div>
</div>

<script>
    function toggleSidebar() {
        const sb = document.getElementById('sidebar');
        sb.classList.toggle('-left-64');
        sb.classList.toggle('left-0');
    }
</script>
@endsection

