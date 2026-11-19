@extends('layouts.guru')

@section('page_title', 'Dashboard Guru Pengampu')

@section('content')
<div class="space-y-8">

    <!-- Welcome Hero -->
    <div class="bg-gradient-to-r from-cyan-900 to-blue-800 rounded-3xl p-6 sm:p-8 text-white shadow-lg flex flex-col sm:flex-row items-center justify-between gap-6">
        <div class="space-y-2">
            <span class="inline-block px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-xs font-bold text-cyan-200">Panel Tenaga Pendidik</span>
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Halo, {{ $guru->nama }}</h2>
            <p class="text-sm text-cyan-100 max-w-xl">
                Kelola paket bank soal mandiri, rilis butir soal ke rombel kelas yang diampu, serta pantau pencapaian hasil belajar peserta didik.
            </p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('guru.bank_soal.index') }}" class="px-5 py-3 rounded-xl bg-white text-cyan-950 font-bold text-sm shadow hover:bg-cyan-50 transition-colors">
                <i class="fa-solid fa-plus mr-1"></i> Buat Bank Soal
            </a>
            <a href="{{ route('guru.results.index') }}" class="px-5 py-3 rounded-xl bg-cyan-700/60 hover:bg-cyan-700 text-white font-bold text-sm border border-cyan-400/30 transition-colors">
                <i class="fa-solid fa-square-poll-vertical mr-1"></i> Rekap Nilai
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900 font-mono">{{ $totalBank }}</div>
                <div class="text-xs font-semibold text-slate-500">Bank Soal Saya</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-cloud-arrow-up"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900 font-mono">{{ $totalBankRilis }}</div>
                <div class="text-xs font-semibold text-slate-500">Paket Dirilis</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-brand-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-list-check"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900 font-mono">{{ $totalSoal }}</div>
                <div class="text-xs font-semibold text-slate-500">Total Butir Soal</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900 font-mono">{{ $totalPeserta }}</div>
                <div class="text-xs font-semibold text-slate-500">Siswa Telah Ujian</div>
            </div>
        </div>
    </div>

    <!-- Mata Pelajaran Binaan Guru -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
        <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
            <i class="fa-solid fa-book-bookmark text-cyan-600"></i>
            <span>Mata Pelajaran Binaan Anda</span>
        </h3>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($subjects as $subj)
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 flex items-start justify-between">
                <div>
                    <h4 class="font-bold text-slate-900 text-sm">{{ $subj->nama_mapel }}</h4>
                    <p class="text-xs text-slate-500 mt-1">Sasaran: Kelas {{ $subj->kelas }}</p>
                </div>
                <span class="w-7 h-7 rounded-lg bg-cyan-100 text-cyan-700 font-bold flex items-center justify-center text-xs">
                    <i class="fa-solid fa-graduation-cap"></i>
                </span>
            </div>
            @empty
            <div class="col-span-3 text-center py-6 text-xs text-slate-400">
                Belum ada penugasan mata pelajaran khusus untuk akun Anda.
            </div>
            @endforelse
        </div>
    </div>

    <!-- Paket Soal Terbaru Guru -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <i class="fa-solid fa-layer-group text-cyan-600"></i>
                <span>Bank Soal Terbaru Milik Anda</span>
            </h3>
            <a href="{{ route('guru.bank_soal.index') }}" class="text-xs font-bold text-cyan-600 hover:text-cyan-700">Lihat Semua Paket</a>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($bankTerbaru as $b)
            <div class="py-3 flex items-center justify-between gap-4">
                <div>
                    <a href="{{ route('guru.questions.index', $b) }}" class="font-bold text-slate-900 text-sm hover:text-cyan-600">
                        {{ $b->nama_bank }}
                    </a>
                    <div class="text-xs text-slate-500 mt-0.5">
                        {{ $b->subject->nama_mapel ?? '-' }} • {{ $b->questions_count }} Butir Soal
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @if($b->status_rilis === 'rilis')
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Rilis: {{ $b->kelas_rilis ?: 'Semua' }}
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                            Draft
                        </span>
                    @endif
                    <a href="{{ route('guru.questions.index', $b) }}" class="px-3 py-1 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs">
                        Kelola Soal
                    </a>
                </div>
            </div>
            @empty
            <div class="py-8 text-center text-sm text-slate-400">
                Anda belum membuat paket bank soal. Klik tombol "Buat Bank Soal" di atas.
            </div>
            @endforelse
        </div>
    </div>

</div>
@endsection

