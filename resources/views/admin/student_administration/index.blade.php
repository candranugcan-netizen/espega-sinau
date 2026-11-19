@extends('layouts.admin')

@section('page_title', 'Administrasi Siswa')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Administrasi Siswa</h2>
            <p class="mt-1 text-xs text-slate-500">Data induk dan dokumen peserta didik.</p>
        </div>
        <a href="{{ route('admin.users.siswa') }}" class="inline-flex items-center gap-2 self-start rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
            <i class="fa-solid fa-arrow-left"></i> Data Siswa Ujian
        </a>
    </div>

    <form method="GET" action="{{ route('admin.student_administration.index') }}" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-[1fr_220px_auto]">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama, NIS, atau NISN" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
        <select name="kelas" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
            <option value="">Semua kelas</option>
            @foreach($classes as $class)
                <option value="{{ $class }}" @selected(request('kelas') === $class)>Kelas {{ $class }}</option>
            @endforeach
        </select>
        <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-700">
            <i class="fa-solid fa-magnifying-glass"></i> Cari
        </button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-[11px] uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-bold">Siswa</th>
                        <th class="px-4 py-3 font-bold">NIS / NISN</th>
                        <th class="px-4 py-3 font-bold">Kelas</th>
                        <th class="px-4 py-3 text-center font-bold">Dokumen</th>
                        <th class="px-4 py-3 text-right font-bold">Administrasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($students as $student)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-900">{{ $student->nama_lengkap }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $student->username }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                <div>{{ $student->username ?: '-' }}</div>
                                <div class="mt-0.5 text-slate-400">{{ $student->nisn ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-700">{{ $student->kelas ?: '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex min-w-8 items-center justify-center rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700">{{ $student->student_documents_count }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.student_administration.show', $student) }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-3 py-2 text-xs font-bold text-white hover:bg-brand-700">
                                    <i class="fa-solid fa-folder-open"></i> Buka
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">Data siswa tidak ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($students->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $students->links() }}</div>
        @endif
    </div>
</div>
@endsection