@extends('layouts.app')

@push('styles')
<style>
    /* Hilangkan scrollbar visual di sidebar agar tetap bersih & elegan, namun tetap bisa di-scroll */
    .sidebar-scroll {
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE dan Edge */
    }
    .sidebar-scroll::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Opera */
    }
</style>
@endpush

@section('body')
<div class="flex h-screen overflow-hidden bg-slate-100">

    <!-- Sidebar Navigation -->
    <aside id="sidebar" class="w-64 bg-slate-900 text-slate-300 flex flex-col shrink-0 transition-all duration-300 z-40 fixed md:static inset-y-0 -left-64 md:left-0 shadow-xl md:shadow-none">
        <!-- Brand Header -->
        <div class="h-20 flex items-center gap-3 px-6 bg-slate-950 border-b border-slate-800/80 shrink-0">
            <img src="/assets/img/logo-espega.png" alt="Logo SMPN 3 Ngawen" class="w-10 h-10 object-contain shrink-0">
            <div>
                <span class="block text-base font-extrabold text-white tracking-tight leading-tight">ESPEGA SINAU</span>
                <span class="block text-[10px] font-bold text-brand-400 tracking-widest uppercase">Admin Panel</span>
            </div>
        </div>

        <!-- Navigation Links (Comfortable Text & Spacing, Scrollbar Hidden) -->
        <nav class="flex-1 overflow-y-auto px-4 py-5 space-y-1.5 text-sm font-medium sidebar-scroll">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider px-3 mb-2">Utama</div>
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-brand-600 text-white font-bold shadow-sm' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-gauge-high w-5 text-center"></i>
                <span>Dashboard</span>
            </a>

            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider px-3 mt-5 mb-2">Ujian & Sesi</div>
            <a href="{{ route('admin.exams.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.exams.*') ? 'bg-brand-600 text-white font-bold shadow-sm' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-file-signature w-5 text-center"></i>
                <span>Kelola Ujian</span>
            </a>
            <a href="{{ route('admin.sessions.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.sessions.*') ? 'bg-brand-600 text-white font-bold shadow-sm' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>
                <span>Monitoring Sesi</span>
            </a>
            <a href="{{ route('admin.bank_soal.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.bank_soal.*') ? 'bg-brand-600 text-white font-bold shadow-sm' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-book-open w-5 text-center"></i>
                <span>Bank Soal Pusat</span>
            </a>

            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider px-3 mt-5 mb-2">SIAKAD & Akademik</div>
            <a href="{{ route('admin.academic_years.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.academic_years.*') ? 'bg-brand-600 text-white font-bold shadow-sm' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-calendar-days w-5 text-center"></i>
                <span>Tahun Ajaran</span>
            </a>
            <a href="{{ route('admin.rombels.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.rombels.*') ? 'bg-brand-600 text-white font-bold shadow-sm' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-chalkboard w-5 text-center"></i>
                <span>Daftar Rombel / Kelas</span>
            </a>
            <a href="{{ route('admin.subjects.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.subjects.*') ? 'bg-brand-600 text-white font-bold shadow-sm' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-book-bookmark w-5 text-center"></i>
                <span>Mata Pelajaran</span>
            </a>
            <a href="{{ route('admin.alumni.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.alumni.*') ? 'bg-brand-600 text-white font-bold shadow-sm' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-graduation-cap w-5 text-center"></i>
                <span>Buku Induk Alumni</span>
            </a>

            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider px-3 mt-5 mb-2">Layanan & Publik</div>
            <a href="{{ route('admin.berita.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.berita.*') ? 'bg-brand-600 text-white font-bold shadow-sm' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-newspaper w-5 text-center"></i>
                <span>Berita & Buku Harian</span>
            </a>
            <a href="{{ route('admin.permohonan.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.permohonan.*') ? 'bg-brand-600 text-white font-bold shadow-sm' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <div class="flex items-center gap-3 min-w-0">
                    <i class="fa-solid fa-headset w-5 text-center"></i>
                    <span class="truncate">Permohonan Informasi</span>
                </div>
                @php
                    $pendingPermohonanCount = \Illuminate\Support\Facades\Schema::hasTable('permohonan_informasi') ? \App\Models\PermohonanInformasi::where('status', 'pending')->count() : 0;
                @endphp
                @if($pendingPermohonanCount > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-slate-950 shrink-0">{{ $pendingPermohonanCount }}</span>
                @endif
            </a>

            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider px-3 mt-5 mb-2">Master Data & Pengguna</div>
            <a href="{{ route('admin.users.siswa') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.users.siswa') ? 'bg-brand-600 text-white font-bold shadow-sm' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-user-graduate w-5 text-center"></i>
                <span>Data Siswa</span>
            </a>
            <a href="{{ route('admin.student_administration.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.student_administration.*') ? 'bg-brand-600 text-white font-bold shadow-sm' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-folder-open w-5 text-center"></i>
                <span>Administrasi Siswa</span>
            </a>
            <a href="{{ route('admin.users.guru') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.users.guru') ? 'bg-brand-600 text-white font-bold shadow-sm' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-chalkboard-user w-5 text-center"></i>
                <span>Data Guru</span>
            </a>

            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider px-3 mt-5 mb-2">Laporan</div>
            <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.reports.*') ? 'bg-brand-600 text-white font-bold shadow-sm' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-chart-column w-5 text-center"></i>
                <span>Laporan Nilai</span>
            </a>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-4 bg-slate-950 border-t border-slate-800/80 flex items-center justify-between gap-2.5 shrink-0">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <div class="w-9 h-9 rounded-xl bg-slate-800 text-brand-400 flex items-center justify-center font-bold text-sm shrink-0">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold text-white truncate" title="{{ auth()->user()->nama }}">{{ auth()->user()->nama }}</div>
                    <div class="text-[10px] text-slate-400 truncate">Super Administrator</div>
                </div>
            </div>
            <a href="{{ route('logout') }}" class="w-8 h-8 shrink-0 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-900 flex items-center justify-center transition-colors" title="Keluar">
                <i class="fa-solid fa-power-off text-sm"></i>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Topbar Header -->
        <header class="h-20 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-4">
                <button type="button" onclick="toggleSidebar()" class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <h1 class="text-lg font-bold text-slate-900">@yield('page_title', 'Admin Panel')</h1>
            </div>

            <div class="flex items-center gap-4">
                @if(isset($activeAcademicYear) && $activeAcademicYear)
                    <a href="{{ route('admin.academic_years.index') }}" class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-brand-50 border border-brand-200 text-xs font-semibold text-brand-700 hover:bg-brand-100 transition-colors" title="Tahun Ajaran Aktif">
                        <i class="fa-solid fa-calendar-check text-brand-600"></i>
                        <span>TA: {{ $activeAcademicYear->full_label }}</span>
                    </a>
                @endif
                <a href="{{ route('landing') }}" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Lihat Web Sekolah</span>
                </a>
                <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>
                <div class="flex items-center gap-3 text-xs text-slate-600">
                    <span class="inline-flex items-center gap-1.5 text-emerald-600 font-bold">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Sistem Online
                    </span>
                </div>
            </div>
        </header>

        <!-- Main Body Scrollable -->
        <main class="flex-1 overflow-y-auto p-6 md:p-8">
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

