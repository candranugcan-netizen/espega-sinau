@extends('layouts.admin')

@section('page_title', 'Pusat Kontrol Administrator')

@section('content')
<div class="space-y-8">

    <!-- Welcome Banner -->
    <div class="bg-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-lg flex flex-col sm:flex-row items-center justify-between gap-6 border border-slate-800">
        <div class="space-y-2">
            <span class="inline-block px-3 py-1 rounded-full bg-brand-500/20 text-brand-300 border border-brand-400/30 text-xs font-bold uppercase tracking-wider">Sistem Informasi Akademik & CBT</span>
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Pusat Kendali Utama ESPEGA SINAU</h2>
            <p class="text-sm text-slate-300 max-w-xl">
                Kelola jadwal ujian sekolah, butir soal, sesi peserta, pemantauan integritas secara langsung, dan data civitas akademik SMPN 3 Ngawen.
            </p>
            @if(isset($activeYear) && $activeYear)
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-brand-500/20 border border-brand-400/30 text-xs text-brand-200 font-bold mt-1">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Tahun Ajaran Berjalan: <span class="text-white font-extrabold">{{ $activeYear->full_label }}</span></span>
                </div>
            @endif
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('admin.academic_years.index') }}" class="px-5 py-3 rounded-xl bg-amber-500 text-slate-950 font-bold text-sm shadow hover:bg-amber-600 transition-colors">
                <i class="fa-solid fa-calendar-days mr-1"></i> Tahun Ajaran
            </a>
            <a href="{{ route('admin.exams.index') }}" class="px-5 py-3 rounded-xl bg-white text-brand-900 font-bold text-sm shadow hover:bg-blue-50 transition-colors">
                <i class="fa-solid fa-plus mr-1"></i> Buat Ujian
            </a>
            <a href="{{ route('admin.sessions.index') }}" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm border border-slate-700 transition-colors">
                <i class="fa-solid fa-desktop mr-1"></i> Monitoring
            </a>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-brand-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-user-graduate"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900 font-mono">{{ number_format($totalSiswaAktif ?? $totalSiswa) }}</div>
                <div class="text-xs font-semibold text-slate-500">Siswa Aktif</div>
            </div>
        </div>

        <a href="{{ route('admin.alumni.index') }}" class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4 hover:border-brand-300 transition-colors group">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-award"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900 font-mono">{{ number_format($totalAlumni ?? 0) }}</div>
                <div class="text-xs font-semibold text-slate-500 group-hover:text-brand-600">Alumni / Lulusan</div>
            </div>
        </a>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-chalkboard-user"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900 font-mono">{{ number_format($totalGuru) }}</div>
                <div class="text-xs font-semibold text-slate-500">Guru Pengampu</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-file-signature"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900 font-mono">{{ number_format($totalUjian) }}</div>
                <div class="text-xs font-semibold text-slate-500">Jadwal Ujian</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold {{ $totalTerblokir > 0 ? 'text-rose-600' : 'text-slate-900' }} font-mono">{{ number_format($totalTerblokir) }}</div>
                <div class="text-xs font-semibold text-slate-500">Siswa Terblokir</div>
            </div>
        </div>
    </div>

    <!-- Dua Kolom: Ujian Terkini & Live Sesi Siswa -->
    <div class="grid lg:grid-cols-12 gap-8">
        
        <!-- Kolom Kiri: Jadwal Ujian Terbaru -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-calendar-check text-brand-600"></i>
                    <h3 class="font-bold text-slate-900">Jadwal Ujian Terdaftar</h3>
                </div>
                <a href="{{ route('admin.exams.index') }}" class="text-xs font-bold text-brand-600 hover:text-brand-700">Lihat Semua</a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($ujianAktif as $ujian)
                <div class="py-3.5 flex items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-900 text-sm">{{ $ujian->nama_ujian }}</span>
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-blue-50 text-brand-700 border border-blue-100">
                                Kelas {{ $ujian->target_kelas }}
                            </span>
                        </div>
                        <div class="text-xs text-slate-500 flex items-center gap-3">
                            <span><i class="fa-solid fa-book mr-1 text-slate-400"></i> {{ $ujian->subject->nama_mapel ?? 'Semua Mapel' }}</span>
                            <span><i class="fa-solid fa-clock mr-1 text-slate-400"></i> {{ $ujian->durasi }} Menit</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-mono font-bold bg-slate-100 text-slate-700 px-2.5 py-1 rounded border border-slate-200">
                            Token: <span class="text-brand-600 font-extrabold">{{ $ujian->token_ujian }}</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="py-8 text-center text-sm text-slate-400">
                    Belum ada jadwal ujian yang terdaftar.
                </div>
                @endforelse
            </div>
        </div>

        <!-- Kolom Kanan: Sesi Terbaru & Integritas Siswa -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-cyan-600"></i>
                    <h3 class="font-bold text-slate-900">Aktivitas Ujian Siswa</h3>
                </div>
                <a href="{{ route('admin.sessions.index') }}" class="text-xs font-bold text-cyan-600 hover:text-cyan-700">Pantau Live</a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($sesiTerbaru as $sesi)
                <div class="py-3 flex items-center justify-between gap-3">
                    <div class="space-y-0.5">
                        <div class="font-bold text-slate-800 text-xs sm:text-sm truncate max-w-[180px]">
                            {{ $sesi->user->nama_lengkap ?? 'Siswa' }}
                        </div>
                        <div class="text-[11px] text-slate-400">
                            Kelas {{ $sesi->user->kelas ?? '-' }} • {{ $sesi->exam->subject->nama_mapel ?? 'Ujian' }}
                        </div>
                    </div>
                    <div class="text-right">
                        @if($sesi->status_siswa === 'selesai')
                            <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Skor: {{ $sesi->skor }}
                            </span>
                        @elseif($sesi->status_siswa === 'terblokir')
                            <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 animate-pulse">
                                Terblokir
                            </span>
                        @else
                            <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                Mengerjakan
                            </span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="py-8 text-center text-sm text-slate-400">
                    Belum ada aktivitas sesi siswa.
                </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection

