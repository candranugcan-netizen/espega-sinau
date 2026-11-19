@extends('layouts.app')

@section('title', 'Konfirmasi Pengumpulan Ujian - Espega Sinau')

@section('body')
<div class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="max-w-xl w-full bg-white rounded-3xl p-8 sm:p-10 border border-slate-200 shadow-xl text-center space-y-6">
        
        <!-- Success Icon Header -->
        <div class="w-20 h-20 rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-3xl mx-auto shadow-inner border border-emerald-100">
            <i class="fa-solid fa-circle-check"></i>
        </div>

        <!-- Exam Details -->
        <div class="space-y-1.5">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold uppercase tracking-wider">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Ujian Berhasil Dikumpulkan
            </span>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ $exam->nama_ujian }}</h1>
            <p class="text-xs text-slate-500 font-medium">
                {{ $exam->subject->nama_mapel ?? 'Mata Pelajaran' }} • Rombel Kelas {{ $exam->target_kelas }}
            </p>
        </div>

        <!-- Submission Status Box (Clean Solid Slate) -->
        <div class="bg-slate-900 text-white rounded-3xl p-6 border border-slate-800 text-left space-y-3 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status Pengumpulan</span>
                <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-400">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    Tersimpan di Server
                </span>
            </div>
            
            <div class="grid grid-cols-2 gap-4 text-xs pt-1">
                <div>
                    <span class="text-slate-400 block text-[11px]">Waktu Selesai:</span>
                    <span class="font-bold text-slate-200 font-mono">
                        {{ $session->selesai_ujian ? $session->selesai_ujian->translatedFormat('d M Y, H:i') : now()->translatedFormat('d M Y, H:i') }} WIB
                    </span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Nama Peserta:</span>
                    <span class="font-bold text-slate-200 truncate block" title="{{ $siswa->nama_lengkap }}">
                        {{ $siswa->nama_lengkap }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Summary Pengerjaan (Tanpa Nilai/Skor) -->
        <div class="grid grid-cols-2 gap-3 text-center">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Soal Diujikan</div>
                <div class="text-2xl font-extrabold text-slate-800 font-mono mt-1">{{ $totalSoal }} <span class="text-xs font-sans font-medium text-slate-400">Butir</span></div>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Soal Terjawab</div>
                <div class="text-2xl font-extrabold text-brand-600 font-mono mt-1">{{ $totalDijawab }} <span class="text-xs font-sans font-medium text-slate-400">Butir</span></div>
            </div>
        </div>

        <!-- School Official Notice Card -->
        <div class="p-4 rounded-2xl bg-blue-50/80 border border-blue-200 text-left flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-white border border-blue-200 text-brand-600 flex items-center justify-center shrink-0 mt-0.5">
                <i class="fa-solid fa-circle-info text-sm"></i>
            </div>
            <div class="space-y-1 text-xs">
                <div class="font-bold text-slate-900">Pengumuman Hasil Evaluasi Belajar</div>
                <p class="text-slate-600 leading-relaxed text-[11px]">
                    Sesuai kebijakan akademik SMP Negeri 3 Ngawen, nilai hasil ujian tidak ditampilkan langsung di layar demi ketertiban pelaksanaan asesmen. Rekapitulasi nilai akan diolah oleh bapak/ibu guru pengampu mata pelajaran dan diumumkan secara resmi melalui sekolah.
                </p>
            </div>
        </div>

        <!-- Back to Dashboard Button -->
        <div class="pt-2">
            <a href="{{ route('siswa.dashboard') }}" class="w-full py-3.5 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-sm transition-colors flex items-center justify-center gap-2">
                <i class="fa-solid fa-house"></i>
                <span>Kembali ke Beranda Siswa</span>
            </a>
        </div>

    </div>
</div>
@endsection
