@extends('layouts.app')

@section('title', 'SMP Negeri 3 Ngawen - Religius, Berprestasi, Berbudaya')

@section('body')
<!-- Top Utility Bar (Sleek, Clean, Informative) -->
<div class="bg-slate-900 text-slate-300 text-xs py-2 border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 flex items-center justify-between gap-3 sm:gap-4">
        <!-- Left: Portal Badge & Title -->
        <div class="flex items-center gap-2.5 truncate">
            <span class="inline-flex items-center gap-1.5 bg-brand-600/20 text-brand-300 text-[10px] font-extrabold uppercase px-2 py-0.5 rounded border border-brand-500/30 shrink-0">
                <i class="fa-solid fa-graduation-cap text-amber-400"></i> Portal Resmi
            </span>
            <span class="text-[11px] sm:text-xs text-slate-300 truncate font-medium">
                Website & Sistem Asesmen SMP Negeri 3 Ngawen (ESPEGA)
            </span>
        </div>

        <!-- Right: Schedule, Location, NPSN -->
        <div class="hidden md:flex items-center gap-4 text-[11px] text-slate-400 shrink-0">
            <span class="flex items-center gap-1.5">
                <i class="fa-regular fa-clock text-brand-400"></i>
                <span>Senin - Jumat: 07.00 - 15.30 WIB</span>
            </span>
            <span class="w-1 h-1 rounded-full bg-slate-700"></span>
            <span class="flex items-center gap-1.5">
                <i class="fa-solid fa-location-dot text-brand-400"></i>
                <span>Gunungkidul, D.I. Yogyakarta</span>
            </span>
            <span class="w-1 h-1 rounded-full bg-slate-700"></span>
            <span class="font-mono text-[10px] bg-slate-800 text-slate-300 font-semibold px-2 py-0.5 rounded border border-slate-700">
                NPSN: 20402027
            </span>
        </div>
    </div>
</div>

<!-- Main Navigation Header (Clean, Sticky with Glassmorphism) -->
<header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200/90 shadow-sm transition-all duration-200">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
        <div class="flex min-h-16 items-center justify-between gap-2 py-2 sm:h-20 sm:py-0">
            <!-- Brand Logo & School Title -->
            <a href="{{ route('landing') }}" class="group flex min-w-0 flex-1 items-center gap-2.5 sm:flex-none sm:gap-3.5">
                <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-white border border-slate-200 shadow-sm p-1 flex items-center justify-center group-hover:border-brand-300 transition-colors shrink-0">
                    <img src="/assets/img/logo-espega.png" alt="Logo SMPN 3 Ngawen" class="w-full h-full object-contain">
                </div>
                <div class="min-w-0">
                    <span class="block max-w-[135px] break-words text-xs font-black tracking-tight text-slate-900 group-hover:text-brand-600 transition-colors leading-tight sm:max-w-none sm:text-lg">
                        SMP NEGERI 3 NGAWEN
                    </span>
                    <span class="hidden text-[10px] sm:flex sm:text-[11px] font-bold text-slate-500 uppercase tracking-wider items-center gap-1.5 mt-0.5">
                        <span class="text-brand-600">Belajar</span>
                        <span class="w-1 h-1 rounded-full bg-amber-500"></span>
                        <span class="text-slate-700">Berproses</span>
                        <span class="w-1 h-1 rounded-full bg-amber-500"></span>
                        <span class="text-emerald-700">Berprogres</span>
                    </span>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden lg:flex items-center gap-0.5 xl:gap-1.5">
                <a href="#beranda" class="px-2.5 xl:px-3 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-brand-600 hover:bg-slate-50 transition-colors whitespace-nowrap">
                    Beranda
                </a>
                <a href="#visi-misi" class="px-2.5 xl:px-3 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-brand-600 hover:bg-slate-50 transition-colors whitespace-nowrap">
                    Visi & Misi
                </a>
                <a href="#profil" class="px-2.5 xl:px-3 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-brand-600 hover:bg-slate-50 transition-colors whitespace-nowrap">
                    Profil
                </a>
                <a href="#program" class="px-2.5 xl:px-3 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-brand-600 hover:bg-slate-50 transition-colors whitespace-nowrap">
                    Program
                </a>
                <a href="#berita" class="px-2.5 xl:px-3 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-brand-600 hover:bg-slate-50 transition-colors whitespace-nowrap">
                    Warta & Berita
                </a>
                <a href="#kontak" class="px-2.5 xl:px-3 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-brand-600 hover:bg-slate-50 transition-colors whitespace-nowrap">
                    Layanan & Kontak
                </a>
            </nav>

            <!-- Actions (Login / User Dashboard / Mobile Toggle) -->
            <div class="flex items-center gap-2 sm:gap-2.5 shrink-0">
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="hidden sm:inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-extrabold text-xs shadow-sm hover:shadow transition-all shrink-0 whitespace-nowrap">
                            <i class="fa-solid fa-gauge-high text-xs"></i>
                            <span>Panel Admin</span>
                        </a>
                    @elseif(auth()->user()->isGuru())
                        <a href="{{ route('guru.dashboard') }}" class="hidden sm:inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-extrabold text-xs shadow-sm hover:shadow transition-all shrink-0 whitespace-nowrap">
                            <i class="fa-solid fa-chalkboard-user text-xs"></i>
                            <span>Panel Guru</span>
                        </a>
                    @else
                        <a href="{{ route('siswa.dashboard') }}" class="hidden sm:inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-extrabold text-xs shadow-sm hover:shadow transition-all shrink-0 whitespace-nowrap">
                            <i class="fa-solid fa-user-graduate text-xs"></i>
                            <span>Ruang Ujian CBT</span>
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" aria-label="Masuk CBT" class="hidden sm:inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3.5 sm:px-4 lg:px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-extrabold text-xs shadow-sm hover:shadow transition-all shrink-0 whitespace-nowrap">
                        <i class="fa-solid fa-right-to-bracket text-xs"></i>
                        <span class="whitespace-nowrap">Masuk CBT</span>
                    </a>
                @endauth

                <!-- Mobile Hamburger Button -->
                <button type="button" onclick="toggleMobileNav()" id="mobileMenuBtn" class="lg:hidden p-2 sm:p-2.5 rounded-xl text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-200/80 transition-colors shrink-0" aria-label="Buka Menu Navigasi">
                    <i class="fa-solid fa-bars text-base" id="mobileMenuIcon"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Dropdown Menu -->
    <div id="mobileNavMenu" class="hidden lg:hidden border-t border-slate-200 bg-white/98 backdrop-blur-lg px-4 py-3 space-y-1 shadow-xl transition-all">
        <a href="#beranda" onclick="toggleMobileNav()" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-700 hover:text-brand-600 hover:bg-brand-50/70 transition-colors">
            <i class="fa-solid fa-house w-5 text-center text-slate-400"></i>
            <span>Beranda</span>
        </a>
        <a href="#visi-misi" onclick="toggleMobileNav()" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-700 hover:text-brand-600 hover:bg-brand-50/70 transition-colors">
            <i class="fa-solid fa-compass w-5 text-center text-slate-400"></i>
            <span>Visi & Misi</span>
        </a>
        <a href="#profil" onclick="toggleMobileNav()" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-700 hover:text-brand-600 hover:bg-brand-50/70 transition-colors">
            <i class="fa-solid fa-school w-5 text-center text-slate-400"></i>
            <span>Profil Sekolah</span>
        </a>
        <a href="#program" onclick="toggleMobileNav()" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-700 hover:text-brand-600 hover:bg-brand-50/70 transition-colors">
            <i class="fa-solid fa-award w-5 text-center text-slate-400"></i>
            <span>Program Unggulan</span>
        </a>
        <a href="#berita" onclick="toggleMobileNav()" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-700 hover:text-brand-600 hover:bg-brand-50/70 transition-colors">
            <i class="fa-solid fa-newspaper w-5 text-center text-slate-400"></i>
            <span>Warta & Berita</span>
        </a>
        <a href="#kontak" onclick="toggleMobileNav()" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-700 hover:text-brand-600 hover:bg-brand-50/70 transition-colors">
            <i class="fa-solid fa-headset w-5 text-center text-slate-400"></i>
            <span>Layanan & Cek Laporan</span>
        </a>

        <!-- Quick Action Button in Mobile Menu -->
        <div class="pt-3 mt-2 border-t border-slate-100 sm:hidden">
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-brand-600 text-white font-extrabold text-xs shadow-sm">
                        <i class="fa-solid fa-gauge-high"></i>
                        <span>Panel Admin</span>
                    </a>
                @elseif(auth()->user()->isGuru())
                    <a href="{{ route('guru.dashboard') }}" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-brand-600 text-white font-extrabold text-xs shadow-sm">
                        <i class="fa-solid fa-chalkboard-user"></i>
                        <span>Panel Guru</span>
                    </a>
                @else
                    <a href="{{ route('siswa.dashboard') }}" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-brand-600 text-white font-extrabold text-xs shadow-sm">
                        <i class="fa-solid fa-user-graduate"></i>
                        <span>Portal Ujian CBT</span>
                    </a>
                @endif
            @else
                <a href="{{ route('login') }}" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-brand-600 text-white font-extrabold text-xs shadow-sm whitespace-nowrap">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span class="whitespace-nowrap">Masuk Portal CBT / Sinau</span>
                </a>
            @endauth
        </div>
    </div>
</header>

<script>
    function toggleMobileNav() {
        const menu = document.getElementById('mobileNavMenu');
        const icon = document.getElementById('mobileMenuIcon');
        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-xmark');
        } else {
            menu.classList.add('hidden');
            icon.classList.remove('fa-xmark');
            icon.classList.add('fa-bars');
        }
    }
</script>

<!-- Hero Section -->
<section id="beranda" class="bg-slate-50 border-b border-slate-200 pt-8 pb-10 sm:pt-12 sm:pb-16 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-12 gap-8 lg:gap-10 items-center">
            
            <!-- Left Hero Content -->
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <div class="inline-flex max-w-full items-center gap-2 px-3.5 py-1.5 rounded-lg bg-white border border-slate-200 text-brand-700 text-xs font-bold uppercase tracking-wider leading-relaxed shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                    <span class="min-w-0 break-words">Portal Edukasi Digital & CBT SMPN 3 NGAWEN</span>
                </div>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-slate-900 leading-[1.2]">
                    Mewujudkan Sumber Daya Manusia yang <span class="text-brand-600">Religius,</span> <span class="text-slate-800">Berprestasi,</span> dan <span class="text-amber-700">Berbudaya.</span>
                </h1>

                <p class="text-sm sm:text-base text-slate-600 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    SMP Negeri 3 Ngawen berkomitmen memberikan layanan pendidikan berkualitas yang menumbuhkan keimanan, memfasilitasi bakat dan minat secara adaptif, menjunjung tinggi nilai budaya lokal, serta ramah terhadap keberagaman dan inklusi.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3 pt-2">
                    <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-sm transition-colors whitespace-nowrap shrink-0">
                        <i class="fa-solid fa-laptop-file"></i>
                        <span class="whitespace-nowrap">Masuk Espega Sinau (CBT)</span>
                    </a>
                    <a href="#visi-misi" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 font-bold text-sm border border-slate-300 shadow-sm transition-colors">
                        <i class="fa-solid fa-book-bookmark text-brand-600"></i>
                        <span>Lihat Visi & Misi</span>
                    </a>
                </div>

                <!-- Fast Badges -->
                <div class="pt-6 border-t border-slate-200 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs font-semibold text-slate-600">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-certificate text-emerald-600 text-base"></i>
                        <span>Akreditasi B</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-hand-holding-heart text-amber-600 text-base"></i>
                        <span>Pendidikan Inklusi & Gotong Royong</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-brand-600 text-base"></i>
                        <span>CBT Berintegritas & Mandiri</span>
                    </div>
                </div>
            </div>

            <!-- Right Hero Card -->
            <div class="lg:col-span-5">
                <div class="bg-white rounded-xl sm:rounded-2xl p-4 sm:p-8 shadow-sm border border-slate-200 space-y-5 sm:space-y-6">
                    <div class="flex flex-col items-start gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-3">
                            <img src="/assets/img/logo-espega.png" alt="Logo Sekolah" class="w-12 h-12 object-contain shrink-0">
                            <div class="min-w-0">
                                <h3 class="break-words font-extrabold text-slate-900 text-sm">SMP NEGERI 3 NGAWEN</h3>
                                <p class="break-words text-xs text-slate-500 font-mono">NPSN: 20402027 • Kab. Gunungkidul</p>
                            </div>
                        </div>
                        <span class="max-w-full break-words px-2.5 py-1 rounded text-xs font-bold bg-blue-50 text-brand-700 border border-blue-200">
                            Gunungkidul, D.I. Yogyakarta
                        </span>
                    </div>

                    <!-- 3 Nilai Utama Sekolah -->
                    <div class="space-y-3">
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-white border border-slate-200 text-brand-600 font-bold flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-mosque text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 uppercase">1. Religius</h4>
                                <p class="text-xs text-slate-600 mt-0.5">Berakhlak mulia, berbudi luhur, dan rajin beribadah dalam kehidupan sehari-hari.</p>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-white border border-slate-200 text-amber-600 font-bold flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-trophy text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 uppercase">2. Berprestasi</h4>
                                <p class="text-xs text-slate-600 mt-0.5">Mandiri, bernalar kritis, kreatif, dan kompetitif dalam bidang akademik maupun non-akademik.</p>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-white border border-slate-200 text-emerald-600 font-bold flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-earth-asia text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 uppercase">3. Berbudaya</h4>
                                <p class="text-xs text-slate-600 mt-0.5">Menjaga budaya lokal dalam kebhinekaan global serta menjunjung tinggi nilai gotong-royong.</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <a href="{{ route('login') }}" class="w-full py-3 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs flex items-center justify-center gap-2 transition-colors whitespace-nowrap">
                            <span class="whitespace-nowrap">Akses Portal Espega Sinau</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Stats & Data Rombel Section -->
<section id="akademik" class="py-6 sm:py-8 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-6 text-center">
            <div class="rounded-lg bg-slate-50 p-3 sm:p-4">
                <div class="text-2xl sm:text-3xl font-extrabold text-brand-600 font-mono">{{ $totalSiswa > 0 ? $totalSiswa : '320+' }}</div>
                <div class="text-xs font-bold text-slate-500 mt-1 uppercase tracking-wider">Peserta Didik</div>
            </div>
            <div class="rounded-lg bg-slate-50 p-3 sm:p-4">
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-mono">{{ $totalRombel > 0 ? $totalRombel : '6' }}</div>
                <div class="text-xs font-bold text-slate-500 mt-1 uppercase tracking-wider">Rombel Belajar</div>
            </div>
            <div class="rounded-lg bg-slate-50 p-3 sm:p-4">
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-mono">{{ $totalGuru > 0 ? $totalGuru : '25+' }}</div>
                <div class="text-xs font-bold text-slate-500 mt-1 uppercase tracking-wider">Tenaga Pendidik</div>
            </div>
        
            <div class="rounded-lg bg-slate-50 p-3 sm:p-4">
                <div class="text-2xl sm:text-3xl font-extrabold text-emerald-600 font-mono">B</div>
                <div class="text-xs font-bold text-slate-500 mt-1 uppercase tracking-wider">Akreditasi BAN-S/M</div>
            </div>
        </div>
    </div>
</section>

<!-- Section Visi & 3 Pilar Karakter -->
<section id="visi-misi" class="py-10 sm:py-16 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 sm:space-y-12">
        <div class="text-center max-w-3xl mx-auto space-y-2">
            <span class="inline-block px-3 py-1 rounded-md bg-blue-50 text-brand-700 text-xs font-bold uppercase tracking-wider border border-blue-200">
                Landasan Filosofis Sekolah
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Visi & Misi Satuan Pendidikan</h2>
            <p class="text-xs sm:text-sm text-slate-600">Panduan dan arah langkah seluruh warga SMP Negeri 3 Ngawen dalam mencetak lulusan yang unggul dan berintegritas.</p>
        </div>

        <!-- Banner Visi Sekolah -->
        <div class="bg-brand-900 rounded-xl sm:rounded-2xl p-5 sm:p-10 text-white border border-brand-800 shadow-sm text-center space-y-4">
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-brand-800 text-amber-300 text-xs font-bold uppercase tracking-widest border border-brand-700">
                <i class="fa-solid fa-compass"></i> Visi SMP Negeri 3 Ngawen
            </div>
            <blockquote class="text-xl sm:text-2xl lg:text-3xl font-extrabold leading-snug tracking-tight max-w-4xl mx-auto">
                "TERWUJUDNYA SUMBER DAYA MANUSIA YANG RELIGIUS, BERPRESTASI DAN BERBUDAYA"
            </blockquote>
            <p class="text-xs sm:text-sm text-slate-200 max-w-2xl mx-auto leading-relaxed">
                Menjadi satuan pendidikan yang mengakar pada keimanan ketakwaan, mendorong pencapaian kompetensi terbaik pelajar, serta menjaga martabat budaya bangsa.
            </p>
        </div>

        <!-- Tiga Pilar Utama dari Visi -->
        <div class="grid sm:grid-cols-3 gap-4 sm:gap-6">
            <div class="bg-slate-50 rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-slate-200 space-y-3">
                <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-brand-600 flex items-center justify-center font-bold text-lg">
                    <i class="fa-solid fa-hands-praying"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900">1. Nilai Religius</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Membentuk insan yang beriman, bertakwa, berakhlak mulia, rajin beribadah, jujur, serta menumbuhkan sikap saling menghargai antar sesama.
                </p>
            </div>

            <div class="bg-slate-50 rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-slate-200 space-y-3">
                <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-amber-600 flex items-center justify-center font-bold text-lg">
                    <i class="fa-solid fa-medal"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900">2. Berprestasi Unggul</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Mengasah potensi kecerdasan intelektual, kemandirian, bernalar kritis, dan inovatif agar mampu bersaing secara sehat di berbagai kompetisi.
                </p>
            </div>

            <div class="bg-slate-50 rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-slate-200 space-y-3">
                <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-emerald-600 flex items-center justify-center font-bold text-lg">
                    <i class="fa-solid fa-landmark"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900">3. Berbudaya Luhur</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Melestarikan kearifan lokal, menerapkan nilai gotong-royong, cinta lingkungan, serta adaptif dan bijak dalam menghadapi era kebhinekaan global.
                </p>
            </div>
        </div>

        <!-- Misi Satuan Pendidikan (7 Butir Lengkap) -->
        <div class="space-y-6 pt-4">
            <div class="flex flex-col items-start gap-3 border-b border-slate-200 pb-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">7 Misi Satuan Pendidikan SMPN 3 Ngawen</h3>
                    <p class="text-xs text-slate-500">Langkah operasional dalam mewujudkan visi sekolah secara nyata dan terukur.</p>
                </div>
                <span class="text-xs font-bold text-brand-600 bg-blue-50 px-2.5 py-1 rounded border border-blue-200 font-mono">
                    7 Misi Resmi
                </span>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <!-- Misi 1 -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200 hover:border-brand-400 transition-colors flex gap-4 items-start shadow-sm">
                    <span class="w-8 h-8 rounded-lg bg-brand-600 text-white font-bold flex items-center justify-center shrink-0 text-xs font-mono">01</span>
                    <div class="space-y-1">
                        <h4 class="font-bold text-slate-900 text-sm">Profil Pelajar Berakhlak Mulia & Rajin Beribadah</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Menciptakan profil pelajar yang berakhlak mulia dan rajin beribadah melalui pembiasaan doa bersama, tadarus, dan ibadah rutin harian.
                        </p>
                    </div>
                </div>

                <!-- Misi 2 -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200 hover:border-brand-400 transition-colors flex gap-4 items-start shadow-sm">
                    <span class="w-8 h-8 rounded-lg bg-brand-600 text-white font-bold flex items-center justify-center shrink-0 text-xs font-mono">02</span>
                    <div class="space-y-1">
                        <h4 class="font-bold text-slate-900 text-sm">Pembelajaran Menarik, Menyenangkan & Berkarakter</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Menciptakan pembelajaran yang menarik, menyenangkan, dan berkarakter yang mampu memfasilitasi pelajar sesuai bakat dan minatnya.
                        </p>
                    </div>
                </div>

                <!-- Misi 3 -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200 hover:border-brand-400 transition-colors flex gap-4 items-start shadow-sm">
                    <span class="w-8 h-8 rounded-lg bg-brand-600 text-white font-bold flex items-center justify-center shrink-0 text-xs font-mono">03</span>
                    <div class="space-y-1">
                        <h4 class="font-bold text-slate-900 text-sm">Manajemen Satuan Pendidikan Adaptif & Menjamin Mutu</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Meningkatkan manajemen satuan pendidikan yang adaptif, berkarakter dan menjamin mutu pendidikan secara berkesinambungan.
                        </p>
                    </div>
                </div>

                <!-- Misi 4 -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200 hover:border-brand-400 transition-colors flex gap-4 items-start shadow-sm">
                    <span class="w-8 h-8 rounded-lg bg-brand-600 text-white font-bold flex items-center justify-center shrink-0 text-xs font-mono">04</span>
                    <div class="space-y-1">
                        <h4 class="font-bold text-slate-900 text-sm">Perkembangan Budaya Lokal dalam Kebhinekaan Global</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Menciptakan lingkungan sekolah sebagai tempat perkembangan budaya lokal dalam kebhinekaan global yang harmonis dan toleran.
                        </p>
                    </div>
                </div>

                <!-- Misi 5 -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200 hover:border-brand-400 transition-colors flex gap-4 items-start shadow-sm">
                    <span class="w-8 h-8 rounded-lg bg-brand-600 text-white font-bold flex items-center justify-center shrink-0 text-xs font-mono">05</span>
                    <div class="space-y-1">
                        <h4 class="font-bold text-slate-900 text-sm">Pelajar Mandiri, Bernalar Kritis & Kreatif Inovatif</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Menciptakan profil pelajar yang mandiri, bernalar kritis dan kreatif sehingga mampu mengreasi ide dan keterampilan yang inovatif.
                        </p>
                    </div>
                </div>

                <!-- Misi 6 -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200 hover:border-brand-400 transition-colors flex gap-4 items-start shadow-sm">
                    <span class="w-8 h-8 rounded-lg bg-brand-600 text-white font-bold flex items-center justify-center shrink-0 text-xs font-mono">06</span>
                    <div class="space-y-1">
                        <h4 class="font-bold text-slate-900 text-sm">Hak Belajar Inklusif & Gotong-Royong</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Menjamin hak belajar setiap anak tanpa terkecuali termasuk anak yang berkebutuhan khusus (inklusi) dalam proses pembelajaran yang menjunjung tinggi nilai gotong-royong.
                        </p>
                    </div>
                </div>

                <!-- Misi 7 -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200 hover:border-brand-400 transition-colors flex gap-4 items-start shadow-sm md:col-span-2">
                    <span class="w-8 h-8 rounded-lg bg-brand-600 text-white font-bold flex items-center justify-center shrink-0 text-xs font-mono">07</span>
                    <div class="space-y-1">
                        <h4 class="font-bold text-slate-900 text-sm">Partisipasi Aktif Orang Tua & Masyarakat yang Kompetitif</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Menciptakan partisipasi aktif orang tua dan masyarakat dalam keberagaman yang mewadahi kreativitas pelajar yang berjiwa kompetitif.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Profil Sekolah & Lingkungan Belajar -->
<section id="profil" class="py-10 sm:py-16 bg-slate-50 border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-12 gap-8 lg:gap-10 items-center">
            
            <div class="lg:col-span-5">
                <div class="bg-white rounded-xl sm:rounded-2xl p-4 sm:p-8 border border-slate-200 shadow-sm space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-brand-600 flex items-center justify-center text-xl font-bold">
                        <i class="fa-solid fa-quote-left"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 leading-snug">
                        Mendidik Karakter, Mengembangkan Potensi, Mengabdi untuk Negeri
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        "Pendidikan di SMP Negeri 3 Ngawen berfokus pada pendampingan holistik setiap peserta didik. Kami membina budi pekerti, menumbuhkan nalar kritis, dan memastikan setiap siswa merasa dihargai dan difasilitasi sesuai keunikan bakatnya."
                    </p>
                    <div class="pt-4 border-t border-slate-100 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 font-bold">
                            <i class="fa-solid fa-user-tie"></i>
                        </div>
                        <div>
                            <div class="font-bold text-xs text-slate-900">Kepala Sekolah SMPN 3 Ngawen</div>
                            <div class="text-[11px] text-slate-500">SMP Negeri 3 Ngawen, Kab. Gunungkidul</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-7 space-y-5">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-white text-brand-700 text-xs font-bold uppercase border border-slate-200 shadow-sm">
                    Tentang SMPN 3 Ngawen
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Sekolah Ramah Anak, Berbudaya, dan Terintegrasi Teknologi Digital.
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Terletak strategis di Kecamatan Ngawen, Kabupaten Gunungkidul, SMP Negeri 3 Ngawen senantiasa berupaya menyediakan fasilitas dan iklim belajar yang aman, nyaman, dan inklusif bagi seluruh peserta didik dari berbagai latar belakang.
                </p>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Dengan dukungan platform asesmen mandiri <strong>Espega Sinau</strong>, kami membiasakan siswa dengan keterampilan digital secara sehat, mandiri, dan berintegritas tinggi tanpa mengabaikan keluhuran budaya lokal Gunungkidul.
                </p>

                <div class="grid sm:grid-cols-2 gap-4 pt-2">
                    <div class="min-w-0 p-3 sm:p-4 rounded-xl bg-white border border-slate-200 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-leaf text-base"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-slate-800 text-xs">Lingkungan Sehat & Asri</div>
                            <div class="text-[11px] text-slate-500">Penataan taman dan pembiasaan budaya bersih.</div>
                        </div>
                    </div>
                    <div class="min-w-0 p-3 sm:p-4 rounded-xl bg-white border border-slate-200 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 text-brand-600 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-network-wired text-base"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-slate-800 text-xs">Laboratorium Komputer CBT</div>
                            <div class="text-[11px] text-slate-500">Fasilitas ujian online mandiri terpercaya.</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Program Unggulan Section -->
<section id="program" class="py-10 sm:py-16 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 sm:space-y-12">
        <div class="text-center max-w-3xl mx-auto space-y-2">
            <span class="inline-block px-3 py-1 rounded-md bg-blue-50 text-brand-700 text-xs font-bold uppercase tracking-wider border border-blue-200">
                Layanan & Pengembangan Diri
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Program Unggulan ESPEGA</h2>
            <p class="text-xs sm:text-sm text-slate-600">Inovasi pembelajaran dan wadah minat bakat untuk memfasilitasi pelajar yang berjiwa kompetitif.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-4 sm:gap-6">
            <!-- Program 1: Espega Sinau CBT -->
            <div class="bg-slate-50 rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-slate-200 flex flex-col justify-between space-y-5 hover:border-brand-400 transition-colors">
                <div class="space-y-3">
                    <div class="w-11 h-11 rounded-xl bg-white border border-slate-200 text-brand-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-laptop-code"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Platform CBT Espega Sinau</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Sistem asesmen digital terpadu dengan perlindungan anti-kecurangan, bank soal mandiri guru, token verifikasi, dan penilaian otomatis yang transparan.
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-200 flex items-center justify-between text-xs font-semibold text-brand-700">
                    <span>Teknologi Edukasi Mandiri</span>
                    <i class="fa-solid fa-arrow-right text-slate-400"></i>
                </div>
            </div>

            <!-- Program 2: Literasi & Karakter -->
            <div class="bg-slate-50 rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-slate-200 flex flex-col justify-between space-y-5 hover:border-brand-400 transition-colors">
                <div class="space-y-3">
                    <div class="w-11 h-11 rounded-xl bg-white border border-slate-200 text-emerald-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-book-open-reader"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Gerakan Literasi & Budaya Baca</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Pembiasaan membaca buku non-pelajaran 15 menit sebelum kegiatan belajar, sudut baca kelas, serta pembinaan bernalar kritis dan apresiasi karya siswa.
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-200 flex items-center justify-between text-xs font-semibold text-emerald-700">
                    <span>Penguatan Karakter & Literasi</span>
                    <i class="fa-solid fa-arrow-right text-slate-400"></i>
                </div>
            </div>

            <!-- Program 3: Ekstrakurikuler & Inklusi -->
            <div class="bg-slate-50 rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-slate-200 flex flex-col justify-between space-y-5 hover:border-brand-400 transition-colors">
                <div class="space-y-3">
                    <div class="w-11 h-11 rounded-xl bg-white border border-slate-200 text-amber-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-users-viewfinder"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Minat Bakat, Seni & Olahraga</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Wadah aktualisasi diri meliputi Pramuka, PMR, Seni Tari & Karawitan Tradisional, Olahraga Prestasi (Voli, Atletik), serta kepedulian sosial inklusi.
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-200 flex items-center justify-between text-xs font-semibold text-amber-700">
                    <span>Pengembangan Kesiswaan</span>
                    <i class="fa-solid fa-arrow-right text-slate-400"></i>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Berita & Informasi Sekolah -->
<section id="berita" class="py-10 sm:py-16 bg-slate-50 border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <span class="inline-block px-3 py-1 rounded-md bg-white text-brand-700 text-xs font-bold uppercase tracking-wider border border-slate-200 shadow-sm">
                    Kabar & Jurnal Sekolah
                </span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-2">Warta Sekolah & Buku Harian Guru</h2>
                <p class="text-xs sm:text-sm text-slate-600 mt-1 max-w-2xl">
                    Informasi agenda sekolah, prestasi peserta didik, serta catatan refleksi pembelajaran harian guru terhubung dengan tahun ajaran aktif.
                </p>
            </div>
            @if(isset($activeYear) && $activeYear)
            <div class="hidden sm:inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-700 font-bold shadow-sm shrink-0">
                <i class="fa-solid fa-calendar-check text-brand-600"></i>
                <span>Tahun Ajaran: {{ $activeYear->full_label }}</span>
            </div>
            @endif
        </div>

        <!-- Filter Bar di Website (Tahun Ajaran & Bulan) -->
        <div class="bg-white rounded-xl sm:rounded-2xl p-3 sm:p-4 border border-slate-200 shadow-sm">
            <form method="GET" action="{{ route('landing') }}#berita" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                <!-- Filter Tahun Ajaran -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tahun Ajaran</label>
                    <select name="academic_year_id" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none font-medium">
                        <option value="">Semua Tahun Ajaran</option>
                        @foreach($academicYears ?? [] as $ay)
                            <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>TA: {{ $ay->full_label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Bulan -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Bulan Pelaksanaan</label>
                    <select name="bulan" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none font-medium">
                        <option value="">Semua Bulan</option>
                        @foreach($bulanList ?? [] as $num => $namaBulan)
                            <option value="{{ $num }}" {{ request('bulan') == $num ? 'selected' : '' }}>{{ $namaBulan }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Kategori -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kategori Warta</label>
                    <select name="kategori" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none font-medium">
                        <option value="">Semua Kategori</option>
                        <option value="berita" {{ request('kategori') == 'berita' ? 'selected' : '' }}>Berita Sekolah</option>
                        <option value="buku_harian" {{ request('kategori') == 'buku_harian' ? 'selected' : '' }}>Buku Harian Guru</option>
                        <option value="prestasi" {{ request('kategori') == 'prestasi' ? 'selected' : '' }}>Prestasi Siswa</option>
                        <option value="pengumuman" {{ request('kategori') == 'pengumuman' ? 'selected' : '' }}>Pengumuman</option>
                    </select>
                </div>

                <!-- Cari Judul -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Pencarian</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kata kunci..." class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="min-h-11 w-full py-2 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition">
                        Terapkan Filter
                    </button>
                    @if(request()->hasAny(['academic_year_id', 'bulan', 'kategori', 'q']))
                        <a href="{{ route('landing') }}#berita" class="py-2 px-3 rounded-xl border border-slate-200 text-slate-500 hover:text-slate-800 text-xs font-bold transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- News Cards Grid -->
        <div class="grid md:grid-cols-3 gap-6">
            @forelse($beritaList as $item)
            <article class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col justify-between hover:border-brand-300 transition-colors shadow-sm group">
                <div>
                    <!-- Thumbnail Foto -->
                    @if($item->gambar)
                    <div class="h-44 sm:h-48 overflow-hidden bg-slate-100 border-b border-slate-100 relative">
                        <img src="{{ asset($item->gambar) }}" alt="{{ $item->judul }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @if($item->academicYear)
                        <span class="absolute bottom-2 left-2 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-900/80 text-white backdrop-blur-sm">
                            TA: {{ $item->academicYear->nama }}
                        </span>
                        @endif
                    </div>
                    @else
                    <div class="h-36 sm:h-40 bg-slate-100 flex items-center justify-center text-slate-400 border-b border-slate-100 relative">
                        <i class="fa-solid fa-newspaper text-3xl"></i>
                        @if($item->academicYear)
                        <span class="absolute bottom-2 left-2 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-900/80 text-white backdrop-blur-sm">
                            TA: {{ $item->academicYear->nama }}
                        </span>
                        @endif
                    </div>
                    @endif

                    <div class="p-4 sm:p-6 space-y-2.5 sm:space-y-3">
                        <div class="flex items-center justify-between text-[11px] text-slate-500">
                            @if($item->kategori === 'buku_harian')
                                <span class="px-2 py-0.5 rounded bg-amber-50 font-bold text-amber-800 border border-amber-200">
                                    Buku Harian Guru
                                </span>
                            @elseif($item->kategori === 'prestasi')
                                <span class="px-2 py-0.5 rounded bg-emerald-50 font-bold text-emerald-800 border border-emerald-200">
                                    Prestasi Siswa
                                </span>
                            @elseif($item->kategori === 'pengumuman')
                                <span class="px-2 py-0.5 rounded bg-purple-50 font-bold text-purple-800 border border-purple-200">
                                    Pengumuman
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded bg-blue-50 font-bold text-brand-700 border border-blue-200">
                                    Berita Sekolah
                                </span>
                            @endif

                            <span class="shrink-0">{{ $item->tanggal ? $item->tanggal->format('d M Y') : '-' }}</span>
                        </div>

                        <h3 class="font-bold text-slate-900 text-sm sm:text-base leading-snug group-hover:text-brand-600 transition-colors line-clamp-2" title="{{ $item->judul }}">
                            {{ $item->judul }}
                        </h3>

                        <div class="text-[11px] text-slate-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-user-pen text-[10px]"></i>
                            <span class="truncate">{{ $item->user->nama_lengkap ?? 'Bapak/Ibu Guru' }}</span>
                        </div>

                        <p class="text-xs text-slate-600 leading-relaxed line-clamp-3">
                            {{ $item->ringkasan ?: Str::limit(strip_tags($item->konten), 130) }}
                        </p>
                    </div>
                </div>

                <div class="px-4 pb-4 sm:px-6 sm:pb-6 pt-2 border-t border-slate-100 flex items-center justify-between">
                    <script type="application/json" id="berita-data-{{ $item->id }}">
                    {!! json_encode([
                        'judul' => $item->judul,
                        'penulis' => $item->user->nama_lengkap ?? 'Bapak/Ibu Guru',
                        'tanggal' => $item->tanggal ? $item->tanggal->format('d F Y') : '',
                        'ta' => $item->academicYear?->full_label ?? '',
                        'foto' => $item->gambar ? asset($item->gambar) : '',
                        'konten' => $item->konten
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}
                    </script>
                    <button type="button" onclick="bacaBeritaById({{ $item->id }})" class="text-xs font-bold text-brand-600 hover:text-brand-800 flex items-center gap-1 transition">
                        <span>Baca Cepat</span>
                        <i class="fa-solid fa-expand text-[10px]"></i>
                    </button>
                    <a href="{{ route('berita.show', $item->slug) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1 transition">
                        <span>Halaman Penuh</span>
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                </div>
            </article>
            @empty
            <div class="md:col-span-3 py-16 text-center bg-white rounded-2xl border border-slate-200 p-8 space-y-3">
                <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl">
                    <i class="fa-solid fa-inbox"></i>
                </div>
                <h4 class="font-bold text-slate-800 text-sm">Tidak Ada Warta atau Catatan</h4>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    Belum ada publikasi berita atau buku harian guru yang sesuai dengan kriteria filter yang Anda pilih.
                </p>
                <div class="pt-2">
                    <a href="{{ route('landing') }}#berita" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold inline-block">
                        Lihat Semua Berita
                    </a>
                </div>
            </div>
            @endforelse
        </div>

        @if(isset($beritaList) && $beritaList->hasPages())
        <div class="pt-4 flex justify-center">
            {{ $beritaList->fragment('berita')->links() }}
        </div>
        @endif

    </div>
</section>

<!-- Modal Baca Cepat Berita (Popup) -->
<div id="bacaBeritaModal" class="modal-overlay fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-3 sm:p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="modal-dialog bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-7 max-w-2xl w-full shadow-2xl space-y-4 sm:space-y-5 my-auto relative animate-fadeIn border border-slate-200">
        <div class="flex items-start justify-between pb-3 border-b border-slate-100 gap-3 sm:gap-4">
            <div class="min-w-0 flex-1">
                <span id="baca_meta" class="text-[10px] sm:text-[11px] font-bold text-brand-600 uppercase tracking-wider block"></span>
                <h3 id="baca_judul" class="font-extrabold text-slate-900 text-base sm:text-xl mt-0.5 sm:mt-1 leading-snug break-words"></h3>
            </div>
            <button type="button" onclick="closeModal('bacaBeritaModal')" class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center shrink-0 transition-colors" title="Tutup Modal">
                <i class="fa-solid fa-xmark text-base sm:text-lg"></i>
            </button>
        </div>

        <div id="baca_foto_container" class="hidden rounded-xl sm:rounded-2xl overflow-hidden border border-slate-200 max-h-48 sm:max-h-72">
            <img id="baca_foto" src="" alt="Foto Berita" class="w-full h-full object-cover">
        </div>

        <div id="baca_konten" class="text-xs sm:text-sm text-slate-700 leading-relaxed max-h-[55vh] sm:max-h-96 overflow-y-auto pr-1 sm:pr-2 space-y-3 font-sans whitespace-pre-line break-words"></div>

        <div class="pt-3 sm:pt-4 border-t border-slate-100 flex items-center justify-between gap-2">
            <div class="text-[10px] sm:text-[11px] text-slate-400 truncate">
                SMP Negeri 3 Ngawen &bull; Espega Sinau
            </div>
            <button type="button" onclick="closeModal('bacaBeritaModal')" class="px-4 sm:px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition shrink-0">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    function bacaBeritaById(id) {
        const el = document.getElementById('berita-data-' + id);
        if (!el) return;
        try {
            const data = JSON.parse(el.textContent);
            openBacaModal(data.judul, data.penulis, data.tanggal, data.ta, data.foto, data.konten);
        } catch (err) {
            console.error('Gagal membaca data berita:', err);
        }
    }

    function openBacaModal(judul, penulis, tanggal, ta, foto, konten) {
        document.getElementById('baca_judul').textContent = judul || '';
        
        let metaParts = [];
        if (tanggal) metaParts.push(tanggal);
        if (penulis) metaParts.push('Oleh: ' + penulis);
        if (ta) metaParts.push(ta);
        document.getElementById('baca_meta').textContent = metaParts.join(' • ');

        document.getElementById('baca_konten').innerHTML = konten || '';
        
        const fotoCont = document.getElementById('baca_foto_container');
        const fotoImg = document.getElementById('baca_foto');
        if (foto && foto.trim() !== '') {
            fotoImg.src = foto;
            fotoCont.classList.remove('hidden');
        } else {
            fotoCont.classList.add('hidden');
        }

        if (typeof window.openModal === 'function') {
            window.openModal('bacaBeritaModal');
        } else {
            const modal = document.getElementById('bacaBeritaModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }
    }
</script>

<!-- Call to Action Banner (Clean Solid) -->
<section class="py-10 sm:py-14 bg-brand-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4 sm:space-y-5">
        <div class="w-12 h-12 rounded-xl bg-brand-800 text-amber-300 flex items-center justify-center mx-auto text-xl border border-brand-700">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Siap Melaksanakan Asesmen Digital?</h2>
        <p class="text-slate-200 max-w-xl mx-auto text-xs sm:text-sm leading-relaxed">
            Gunakan Nomor Induk Siswa (NIS) dan Token Ujian yang diberikan oleh pengawas sekolah untuk mengakses ruang ujian secara mandiri dan jujur.
        </p>
        <div class="pt-2">
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-sm shadow-sm transition-colors whitespace-nowrap shrink-0">
                <i class="fa-solid fa-key"></i>
                <span class="whitespace-nowrap">Masuk ke Ruang Ujian Siswa</span>
            </a>
        </div>
    </div>
</section>

<!-- Kontak & Alamat Sekolah -->
<section id="kontak" class="py-10 sm:py-16 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-12 gap-8 lg:gap-10">
            
            <div class="lg:col-span-5 space-y-6">
                <div>
                    <span class="inline-block px-3 py-1 rounded-md bg-blue-50 text-brand-700 text-xs font-bold uppercase tracking-wider border border-blue-200">
                        Pusat Layanan
                    </span>
                    <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight mt-2">Hubungi SMPN 3 Ngawen</h2>
                    <p class="text-slate-600 text-xs sm:text-sm mt-1">Kami siap melayani kebutuhan informasi wali murid, peserta didik, dan masyarakat luas.</p>
                </div>

                <div class="space-y-3">
                    <div class="flex min-w-0 items-start gap-3 p-3.5 sm:gap-3.5 sm:p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="w-9 h-9 rounded-lg bg-white border border-slate-200 text-brand-600 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-map-location-dot text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[11px] font-bold text-slate-500 uppercase">Alamat Satuan Pendidikan</div>
                            <div class="text-xs font-bold text-slate-900 mt-0.5">Jl. Raya Ngawen, Kec. Ngawen, Kabupaten Gunungkidul, Daerah Istimewa Yogyakarta</div>
                        </div>
                    </div>

                    <div class="flex min-w-0 items-start gap-3 p-3.5 sm:gap-3.5 sm:p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="w-9 h-9 rounded-lg bg-white border border-slate-200 text-brand-600 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-envelope text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[11px] font-bold text-slate-500 uppercase">Email Resmi</div>
                            <div class="break-all text-xs font-bold text-slate-900 mt-0.5">smpnegeri3ngawen@gmail.com</div>
                        </div>
                    </div>

                    <div class="flex min-w-0 items-start gap-3 p-3.5 sm:gap-3.5 sm:p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="w-9 h-9 rounded-lg bg-white border border-slate-200 text-brand-600 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-phone text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[11px] font-bold text-slate-500 uppercase">Jam Pelayanan Kantor</div>
                            <div class="text-xs font-bold text-slate-900 mt-0.5">Senin - Jumat : 07.00 - 15.30 WIB</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pusat Layanan Permohonan Informasi & Pelacakan Tiket -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-xl sm:rounded-2xl p-4 sm:p-8 border border-slate-200 shadow-sm space-y-5">
                    
                    <!-- Tab Switcher -->
                    <div class="flex flex-col gap-1.5 p-1.5 rounded-xl bg-slate-100 border border-slate-200/80 sm:flex-row">
                        <button type="button" id="tabBtnKirim" onclick="switchPermohonanTab('kirim')" class="flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-white px-3 py-2.5 text-xs font-extrabold text-brand-700 shadow-sm transition-all sm:flex-1 sm:px-4">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                            <span>Kirim Permohonan Baru</span>
                        </button>
                        <button type="button" id="tabBtnLacak" onclick="switchPermohonanTab('lacak')" class="flex min-h-11 w-full items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-xs font-bold text-slate-600 transition-all hover:text-slate-900 sm:flex-1 sm:px-4">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            <span>Cek Status Laporan</span>
                        </button>
                    </div>

                    <!-- Panel 1: Form Kirim Permohonan -->
                    <div id="panelKirimPermohonan" class="space-y-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Formulir Permohonan Informasi / Pengaduan</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Sampaikan pertanyaan atau pengaduan Anda. Anda akan memperoleh nomor laporan unik untuk memantau proses tindak lanjut.</p>
                        </div>

                        <form id="formKirimPermohonan" onsubmit="submitPermohonan(event)">
                            @csrf
                            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                                    <input type="text" name="nama_lengkap" id="input_nama_lengkap" required placeholder="Nama pemohon" class="min-h-11 w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">No. WhatsApp / Email <span class="text-rose-500">*</span></label>
                                    <input type="text" name="kontak" id="input_kontak" required placeholder="08xxxxxxxxxx atau email" class="min-h-11 w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Keperluan / Subjek <span class="text-rose-500">*</span></label>
                                <input type="text" name="subjek" id="input_subjek" required placeholder="Contoh: Informasi PPDB, Mutasi Siswa, Legalisir Ijazah" class="min-h-11 w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
                            </div>
                            <div class="mb-5">
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Isi Permohonan / Uraian Pesan <span class="text-rose-500">*</span></label>
                                <textarea name="pesan" id="input_pesan" rows="4" required placeholder="Tuliskan secara lengkap dan jelas permohonan informasi atau saran pengaduan yang ingin Anda sampaikan..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition leading-relaxed"></textarea>
                            </div>
                            <button type="submit" id="btnSubmitPermohonan" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-brand-600 px-6 py-2.5 text-xs font-extrabold text-white shadow-sm transition-colors hover:bg-brand-700">
                                <i class="fa-solid fa-paper-plane"></i>
                                <span>Kirim Permohonan</span>
                            </button>
                        </form>
                    </div>

                    <!-- Panel 2: Cek Status Laporan -->
                    <div id="panelLacakPermohonan" class="hidden space-y-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Lacak Status & Tanggapan Laporan</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Masukkan nomor laporan (contoh: <code class="font-mono text-brand-600 bg-brand-50 px-1 py-0.5 rounded">PI-20261113-XXXXX</code>) yang Anda peroleh saat mengirimkan permohonan.</p>
                        </div>

                        <form id="formCekLaporan" onsubmit="cekStatusLaporan(event)" class="flex flex-col gap-2 sm:flex-row">
                            @csrf
                            <div class="relative flex-1">
                                <i class="fa-solid fa-ticket absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="text" name="nomor_laporan" id="input_cek_nomor" required placeholder="Masukkan Nomor Laporan..." class="min-h-11 w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
                            </div>
                            <button type="submit" id="btnCekLaporan" class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-slate-900 px-6 py-2.5 text-xs font-extrabold text-white shadow-sm transition-colors hover:bg-slate-800">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span>Cek Status</span>
                            </button>
                        </form>

                        <!-- Box Hasil Pengecekan -->
                        <div id="lacakHasilContainer" class="hidden pt-2">
                            <!-- Card Informasi Laporan -->
                            <div class="p-4 sm:p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-4">
                                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3">
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Nomor Laporan</span>
                                        <div class="font-mono text-sm font-extrabold text-brand-700" id="hasil_nomor_laporan"></div>
                                    </div>
                                    <div>
                                        <span id="hasil_badge_status" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border"></span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                    <div>
                                        <span class="text-slate-400 text-[10px] uppercase font-bold block">Pemohon</span>
                                        <span class="font-bold text-slate-800" id="hasil_nama_pemohon"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 text-[10px] uppercase font-bold block">Waktu Pengajuan</span>
                                        <span class="font-medium text-slate-600" id="hasil_tanggal_kirim"></span>
                                    </div>
                                </div>

                                <div>
                                    <span class="text-slate-400 text-[10px] uppercase font-bold block mb-1">Keperluan / Subjek</span>
                                    <div class="text-xs font-bold text-slate-900 p-2.5 rounded-lg bg-white border border-slate-200" id="hasil_subjek"></div>
                                </div>

                                <div>
                                    <span class="text-slate-400 text-[10px] uppercase font-bold block mb-1">Uraian Pesan</span>
                                    <div class="text-xs text-slate-700 p-3 rounded-lg bg-white border border-slate-200 whitespace-pre-line leading-relaxed" id="hasil_pesan"></div>
                                </div>

                                <!-- Box Tanggapan Sekolah -->
                                <div id="hasil_box_tanggapan" class="p-3.5 rounded-xl border space-y-1.5">
                                    <div class="flex items-center gap-1.5 text-xs font-bold" id="hasil_tanggapan_header">
                                        <i class="fa-solid fa-reply"></i>
                                        <span>Tanggapan Resmi Pihak Sekolah</span>
                                    </div>
                                    <div class="text-xs leading-relaxed" id="hasil_tanggapan_text"></div>
                                    <div class="text-[10px] text-slate-400 pt-1" id="hasil_tanggapan_petugas"></div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<!-- Script Khusus Permohonan Informasi & Pelacakan -->
<script>
    function switchPermohonanTab(tab) {
        const btnKirim = document.getElementById('tabBtnKirim');
        const btnLacak = document.getElementById('tabBtnLacak');
        const panelKirim = document.getElementById('panelKirimPermohonan');
        const panelLacak = document.getElementById('panelLacakPermohonan');

        if (tab === 'kirim') {
            btnKirim.className = 'flex-1 py-2.5 px-4 rounded-lg text-xs font-extrabold transition-all flex items-center justify-center gap-2 bg-white text-brand-700 shadow-sm';
            btnLacak.className = 'flex-1 py-2.5 px-4 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-2 text-slate-600 hover:text-slate-900';
            panelKirim.classList.remove('hidden');
            panelLacak.classList.add('hidden');
        } else {
            btnLacak.className = 'flex-1 py-2.5 px-4 rounded-lg text-xs font-extrabold transition-all flex items-center justify-center gap-2 bg-white text-brand-700 shadow-sm';
            btnKirim.className = 'flex-1 py-2.5 px-4 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-2 text-slate-600 hover:text-slate-900';
            panelLacak.classList.remove('hidden');
            panelKirim.classList.add('hidden');
        }
    }

    async function submitPermohonan(e) {
        e.preventDefault();
        const form = document.getElementById('formKirimPermohonan');
        const btn = document.getElementById('btnSubmitPermohonan');
        const originalBtnHtml = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Mengirim...</span>';

        const formData = new FormData(form);

        try {
            const res = await fetch("{{ route('permohonan.store') }}", {
                method: "POST",
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            });

            const data = await res.json();

            if (res.ok && data.status === 'success') {
                const nomorLaporan = data.nomor_laporan;
                form.reset();

                Swal.fire({
                    icon: 'success',
                    title: 'Permohonan Berhasil Dikirim!',
                    html: `
                        <p class="text-xs text-slate-600 mb-3">Terima kasih, permohonan informasi Anda telah tercatat dalam sistem kami.</p>
                        <div class="p-3 bg-brand-50 border border-brand-200 rounded-xl text-center mb-3">
                            <span class="text-[10px] uppercase font-bold text-slate-500 block">Nomor Laporan Anda</span>
                            <span class="font-mono text-base font-extrabold text-brand-700 tracking-wider select-all" id="swalNomorLaporan">${nomorLaporan}</span>
                        </div>
                        <p class="text-[11px] text-amber-700 bg-amber-50 p-2.5 rounded-lg border border-amber-200 text-left">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                            <strong>Simpan nomor ini!</strong> Anda dapat memeriksa tindak lanjut dan respon sekolah pada tab <strong>Cek Status Laporan</strong> dengan nomor tiket ini.
                        </p>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-magnifying-glass mr-1"></i> Cek Status Sekarang',
                    cancelButtonText: 'Tutup',
                    confirmButtonColor: '#0c83ce',
                    cancelButtonColor: '#64748b'
                }).then((result) => {
                    if (result.isConfirmed) {
                        switchPermohonanTab('lacak');
                        document.getElementById('input_cek_nomor').value = nomorLaporan;
                        document.getElementById('btnCekLaporan').click();
                    }
                });
            } else {
                let errMsg = data.message || 'Terjadi kesalahan saat menyimpan permohonan.';
                if (data.errors) {
                    errMsg = Object.values(data.errors).flat().join('<br>');
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Mengirim',
                    html: errMsg,
                    confirmButtonColor: '#0c83ce'
                });
            }
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Koneksi Terputus',
                text: 'Tidak dapat terhubung ke server. Silakan periksa koneksi internet Anda.',
                confirmButtonColor: '#0c83ce'
            });
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalBtnHtml;
        }
    }

    async function cekStatusLaporan(e) {
        e.preventDefault();
        const inputNomor = document.getElementById('input_cek_nomor');
        const btn = document.getElementById('btnCekLaporan');
        const container = document.getElementById('lacakHasilContainer');
        const originalBtnHtml = btn.innerHTML;

        const nomor = inputNomor.value.trim();
        if (!nomor) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Mencari...</span>';

        try {
            const res = await fetch("{{ route('permohonan.cek') }}", {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ nomor_laporan: nomor })
            });

            const json = await res.json();

            if (res.ok && json.status === 'success') {
                const d = json.data;
                document.getElementById('hasil_nomor_laporan').textContent = d.nomor_laporan;

                const badge = document.getElementById('hasil_badge_status');
                badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border ' + (d.status_badge_class || 'bg-slate-50 text-slate-700 border-slate-200');
                badge.textContent = d.status_label;

                document.getElementById('hasil_nama_pemohon').textContent = d.nama_lengkap;
                document.getElementById('hasil_tanggal_kirim').textContent = d.tanggal_kirim;
                document.getElementById('hasil_subjek').textContent = d.subjek;
                document.getElementById('hasil_pesan').textContent = d.pesan;

                const boxTanggapan = document.getElementById('hasil_box_tanggapan');
                const headerTanggapan = document.getElementById('hasil_tanggapan_header');
                const textTanggapan = document.getElementById('hasil_tanggapan_text');
                const petugasTanggapan = document.getElementById('hasil_tanggapan_petugas');

                if (d.tanggapan) {
                    boxTanggapan.className = 'p-3.5 rounded-xl border bg-emerald-50 border-emerald-200 text-emerald-950 space-y-1.5';
                    headerTanggapan.className = 'flex items-center gap-1.5 text-xs font-bold text-emerald-800';
                    headerTanggapan.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-600"></i><span>Jawaban / Tindak Lanjut Resmi Sekolah</span>';
                    textTanggapan.className = 'text-xs leading-relaxed text-slate-800 whitespace-pre-line';
                    textTanggapan.textContent = d.tanggapan;
                    petugasTanggapan.textContent = 'Diverifikasi & dijawab oleh: ' + (d.petugas || 'Pihak Sekolah') + (d.tanggal_tanggapan ? ' pada ' + d.tanggal_tanggapan : '');
                } else if (d.status === 'proses') {
                    boxTanggapan.className = 'p-3.5 rounded-xl border bg-sky-50 border-sky-200 text-sky-950 space-y-1.5';
                    headerTanggapan.className = 'flex items-center gap-1.5 text-xs font-bold text-sky-800';
                    headerTanggapan.innerHTML = '<i class="fa-solid fa-arrows-rotate fa-spin text-sky-600"></i><span>Laporan Sedang Diproses</span>';
                    textTanggapan.className = 'text-xs leading-relaxed text-sky-900';
                    textTanggapan.textContent = 'Laporan Anda telah diterima dan saat ini sedang dalam proses koordinasi oleh bidang terkait. Mohon menunggu jawaban selanjutnya.';
                    petugasTanggapan.textContent = 'Status pemrosesan aktif';
                } else {
                    boxTanggapan.className = 'p-3.5 rounded-xl border bg-amber-50 border-amber-200 text-amber-950 space-y-1.5';
                    headerTanggapan.className = 'flex items-center gap-1.5 text-xs font-bold text-amber-800';
                    headerTanggapan.innerHTML = '<i class="fa-solid fa-hourglass-half text-amber-600"></i><span>Menunggu Respon Petugas</span>';
                    textTanggapan.className = 'text-xs leading-relaxed text-amber-900';
                    textTanggapan.textContent = 'Laporan Anda telah berhasil masuk ke antrean layanan informasi kami. Petugas tata usaha / humas SMPN 3 Ngawen akan segera menindaklanjuti permohonan ini.';
                    petugasTanggapan.textContent = 'Waktu respon kerja: Senin - Jumat (07.00 - 15.30 WIB)';
                }

                container.classList.remove('hidden');
                container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                container.classList.add('hidden');
                Swal.fire({
                    icon: 'warning',
                    title: 'Laporan Tidak Ditemukan',
                    text: json.message || 'Nomor laporan tidak ditemukan. Pastikan nomor tiket yang Anda masukkan sudah benar.',
                    confirmButtonColor: '#0c83ce'
                });
            }
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Memeriksa',
                text: 'Terjadi kendala saat memeriksa laporan. Silakan coba sesaat lagi.',
                confirmButtonColor: '#0c83ce'
            });
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalBtnHtml;
        }
    }
</script>

<!-- Footer -->
<footer class="bg-slate-900 text-slate-400 py-8 sm:py-12 text-xs border-t border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <div class="md:col-span-2 space-y-3">
                <div class="flex items-center gap-3 text-white font-bold text-base">
                    <img src="/assets/img/logo-espega.png" alt="Logo SMPN 3 Ngawen" class="w-8 h-8 object-contain shrink-0">
                    <span>SMP NEGERI 3 NGAWEN</span>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed max-w-sm">
                    Mewujudkan sumber daya manusia yang religius, berprestasi, dan berbudaya dalam suasana pembelajaran yang ramah anak, inklusif, dan adaptif.
                </p>
                <div class="text-[11px] text-slate-500">
                    &copy; {{ date('Y') }} SMP Negeri 3 Ngawen. Platform Espega Sinau. All rights reserved.
                </div>
            </div>

            <div>
                <h4 class="text-white font-bold text-xs uppercase tracking-wider mb-3">Tautan Cepat</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="#visi-misi" class="hover:text-white transition-colors">Visi & Misi Sekolah</a></li>
                    <li><a href="#profil" class="hover:text-white transition-colors">Profil Sekolah</a></li>
                    <li><a href="#akademik" class="hover:text-white transition-colors">Rombel & Kurikulum Mapel</a></li>
                    <li><a href="#program" class="hover:text-white transition-colors">Program Unggulan</a></li>
                    <li><a href="#berita" class="hover:text-white transition-colors">Warta & Berita</a></li>
                    <li><a href="#kontak" class="hover:text-white transition-colors">Permohonan Informasi & Cek Laporan</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-white transition-colors">Login CBT Espega Sinau</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-bold text-xs uppercase tracking-wider mb-3">Kanal Informasi</h4>
                <p class="text-xs text-slate-400 mb-3">Ikuti perkembangan dan dokumentasi kegiatan pembelajaran kami.</p>
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-slate-800 text-slate-300 flex items-center justify-center hover:bg-brand-600 hover:text-white transition-colors cursor-pointer">
                        <i class="fa-brands fa-youtube"></i>
                    </span>
                    <span class="w-8 h-8 rounded-lg bg-slate-800 text-slate-300 flex items-center justify-center hover:bg-brand-600 hover:text-white transition-colors cursor-pointer">
                        <i class="fa-brands fa-instagram"></i>
                    </span>
                    <span class="w-8 h-8 rounded-lg bg-slate-800 text-slate-300 flex items-center justify-center hover:bg-brand-600 hover:text-white transition-colors cursor-pointer">
                        <i class="fa-brands fa-facebook-f"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</footer>
@endsection
