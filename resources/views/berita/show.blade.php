@extends('layouts.app')

@section('title', $berita->judul . ' - SMP Negeri 3 Ngawen')

@section('body')
<div class="min-h-screen bg-slate-50 flex flex-col font-sans">

    <!-- Top Navigation Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-3.5 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between gap-2">
            <a href="{{ route('landing') }}" class="flex items-center gap-2 sm:gap-3 shrink-0 min-w-0">
                <img src="/assets/img/logo-espega.png" alt="Logo SMPN 3 Ngawen" class="w-9 h-9 sm:w-11 sm:h-11 object-contain shrink-0">
                <div class="min-w-0">
                    <span class="block text-sm sm:text-base font-extrabold text-slate-900 tracking-tight leading-tight truncate">SMP NEGERI 3 NGAWEN</span>
                    <span class="block text-[10px] sm:text-[11px] font-semibold text-brand-600 truncate">Espega Sinau • Portal Informasi</span>
                </div>
            </a>

            <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
                <a href="{{ route('landing') }}#berita" class="inline-flex items-center gap-1.5 px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-xl border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-bold text-xs transition whitespace-nowrap">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span class="hidden sm:inline">Kembali ke Beranda</span>
                    <span class="sm:hidden">Kembali</span>
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-1.5 sm:py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs transition shadow-sm whitespace-nowrap">
                    <i class="fa-solid fa-right-to-bracket text-xs"></i>
                    <span>Login CBT</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-3.5 sm:px-6 lg:px-8 py-5 sm:py-10">
        <div class="grid lg:grid-cols-12 gap-6 lg:gap-10">
            
            <!-- Artikel Utama -->
            <article class="lg:col-span-8 bg-white rounded-2xl sm:rounded-3xl border border-slate-200 shadow-sm p-4 sm:p-8 lg:p-10 space-y-5 sm:space-y-6">
                <!-- Meta Tags & Kategori -->
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    @if($berita->kategori === 'buku_harian')
                        <span class="px-2.5 sm:px-3 py-1 rounded-full text-[11px] sm:text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                            <i class="fa-solid fa-book-bookmark mr-1"></i> Buku Harian Guru
                        </span>
                    @elseif($berita->kategori === 'prestasi')
                        <span class="px-2.5 sm:px-3 py-1 rounded-full text-[11px] sm:text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <i class="fa-solid fa-trophy mr-1"></i> Prestasi Sekolah
                        </span>
                    @elseif($berita->kategori === 'pengumuman')
                        <span class="px-2.5 sm:px-3 py-1 rounded-full text-[11px] sm:text-xs font-bold bg-purple-50 text-purple-800 border border-purple-200">
                            <i class="fa-solid fa-bullhorn mr-1"></i> Pengumuman
                        </span>
                    @else
                        <span class="px-2.5 sm:px-3 py-1 rounded-full text-[11px] sm:text-xs font-bold bg-blue-50 text-brand-800 border border-blue-200">
                            <i class="fa-solid fa-newspaper mr-1"></i> Berita Sekolah
                        </span>
                    @endif

                    @if($berita->academicYear)
                        <span class="px-2.5 sm:px-3 py-1 rounded-full text-[11px] sm:text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            TA: {{ $berita->academicYear->full_label }}
                        </span>
                    @endif
                </div>

                <!-- Judul Utama -->
                <h1 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight leading-snug break-words">
                    {{ $berita->judul }}
                </h1>

                <!-- Info Penulis, Tanggal, Views -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 py-3 sm:py-4 border-y border-slate-100 text-xs text-slate-500">
                    <div class="flex items-center gap-2.5 sm:gap-3">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-brand-600 shrink-0">
                            <i class="fa-solid fa-user-pen text-xs sm:text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-slate-900 truncate">{{ $berita->user->nama_lengkap ?? 'Bapak/Ibu Guru' }}</div>
                            <div class="text-[10px] sm:text-[11px] text-slate-400 truncate">{{ $berita->user && $berita->user->isAdmin() ? 'Super Administrator' : 'Guru Pengampu SMPN 3 Ngawen' }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 sm:gap-4 font-medium text-[11px] sm:text-xs text-slate-500 pt-1 sm:pt-0 border-t sm:border-t-0 border-slate-100 sm:border-transparent">
                        <span><i class="fa-solid fa-calendar-day mr-1 text-slate-400"></i> {{ $berita->tanggal ? $berita->tanggal->isoFormat('D MMMM Y') : '-' }}</span>
                        <span><i class="fa-solid fa-eye mr-1 text-slate-400"></i> {{ $berita->dibaca }}x dibaca</span>
                    </div>
                </div>

                <!-- Foto Utama (Jika Ada) -->
                @if($berita->gambar)
                <div class="rounded-xl sm:rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 max-h-60 sm:max-h-[460px]">
                    <img src="{{ asset($berita->gambar) }}" alt="{{ $berita->judul }}" class="w-full h-full object-cover">
                </div>
                @endif

                <!-- Konten Tulisan -->
                <div class="prose prose-slate max-w-none text-slate-700 text-sm sm:text-base leading-relaxed space-y-3 sm:space-y-4 break-words font-sans">
                    {!! nl2br(e($berita->konten)) !!}
                </div>

                <!-- Footer Artikel / Tombol Bagikan -->
                <div class="pt-4 sm:pt-6 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <a href="{{ route('landing') }}#berita" class="inline-flex items-center gap-2 text-xs font-bold text-brand-600 hover:text-brand-800 transition">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Kembali ke Warta Sekolah</span>
                    </a>
                    <div class="text-[11px] sm:text-xs text-slate-400 italic">
                        SMP Negeri 3 Ngawen • Gunungkidul
                    </div>
                </div>
            </article>

            <!-- Sidebar Informasi Lainnya -->
            <aside class="lg:col-span-4 space-y-5 sm:space-y-6">
                <!-- Info Sekolah Card -->
                <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 border border-slate-200 shadow-sm space-y-3 sm:space-y-4">
                    <div class="flex items-center gap-3">
                        <img src="/assets/img/logo-espega.png" alt="Logo" class="w-9 h-9 sm:w-10 sm:h-10 object-contain shrink-0">
                        <div>
                            <div class="font-extrabold text-slate-900 text-sm">SMP NEGERI 3 NGAWEN</div>
                            <div class="text-[10px] sm:text-[11px] text-brand-600 font-semibold">Berprestasi & Berbudaya</div>
                        </div>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Mendukung ekosistem pendidikan yang adaptif, berkarakter, dan menjamin mutu pembelajaran bagi seluruh anak tanpa terkecuali.
                    </p>
                </div>

                <!-- Warta & Catatan Lainnya -->
                @if(isset($beritaLainnya) && $beritaLainnya->isNotEmpty())
                <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 border border-slate-200 shadow-sm space-y-3 sm:space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="font-extrabold text-slate-900 text-sm">Warta Sekolah Lainnya</h3>
                        <a href="{{ route('landing') }}#berita" class="text-[11px] font-bold text-brand-600 hover:underline">Semua</a>
                    </div>
                    <div class="space-y-3 sm:space-y-4">
                        @foreach($beritaLainnya as $bl)
                        <a href="{{ route('berita.show', $bl->slug) }}" class="flex items-start gap-3 group">
                            @if($bl->gambar)
                                <img src="{{ asset($bl->gambar) }}" alt="{{ $bl->judul }}" class="w-14 h-12 sm:w-16 sm:h-14 object-cover rounded-lg sm:rounded-xl border border-slate-200 shrink-0">
                            @else
                                <div class="w-14 h-12 sm:w-16 sm:h-14 rounded-lg sm:rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center shrink-0 border border-slate-200">
                                    <i class="fa-solid fa-newspaper text-xs"></i>
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-bold text-slate-900 group-hover:text-brand-600 transition line-clamp-2 leading-snug">
                                    {{ $bl->judul }}
                                </h4>
                                <div class="text-[10px] text-slate-400 mt-1">
                                    {{ $bl->tanggal ? $bl->tanggal->format('d/m/Y') : '' }} • {{ $bl->academicYear?->nama ?? '' }}
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </aside>

        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 text-xs py-8 border-t border-slate-800 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-2">
            <p class="text-white font-bold">SMP Negeri 3 Ngawen &bull; Espega Sinau</p>
            <p>&copy; {{ date('Y') }} Hak Cipta Dilindungi Undang-Undang.</p>
        </div>
    </footer>

</div>
@endsection

