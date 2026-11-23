@extends('layouts.guru')

@section('page_title', 'Hasil & Nilai Siswa')

@section('content')
<div class="space-y-4 sm:space-y-6">

    <!-- Filter Ujian & Export -->
    <div class="bg-white rounded-xl sm:rounded-2xl p-3 sm:p-4 border border-slate-200 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-3 sm:gap-4">
        <form method="GET" action="{{ route('guru.results.index') }}" class="flex flex-wrap items-center gap-3 w-full lg:w-auto flex-1">
            <div class="w-full sm:w-auto">
                <label class="text-[11px] font-bold text-slate-500 uppercase block mb-1">Pilih Ujian Mapel Anda:</label>
                <select name="exam_id" onchange="this.form.submit()" class="min-h-11 w-full sm:max-w-md px-3.5 py-2 rounded-xl border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-cyan-500 focus:outline-none bg-white">
                    @forelse($exams as $e)
                        <option value="{{ $e->id }}" {{ $selectedExam?->id == $e->id ? 'selected' : '' }}>
                            [{{ $e->subject->nama_mapel ?? 'Mapel' }}] {{ $e->nama_ujian }} (Kelas {{ $e->target_kelas }})
                        </option>
                    @empty
                        <option value="">Belum ada jadwal ujian untuk mapel Anda</option>
                    @endforelse
                </select>
            </div>

            @if(!empty($classesList) && count($classesList) > 1)
            <div class="w-full sm:w-auto">
                <label class="text-[11px] font-bold text-slate-500 uppercase block mb-1">Filter Kelas:</label>
                <select name="kelas" onchange="this.form.submit()" class="min-h-11 w-full sm:w-40 px-3.5 py-2 rounded-xl border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    <option value="">Semua Kelas</option>
                    @foreach($classesList as $k)
                        <option value="{{ $k }}" {{ $selectedKelas == $k ? 'selected' : '' }}>Kelas {{ $k }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($selectedKelas)
            <div class="self-end pb-0.5">
                <a href="{{ route('guru.results.index', ['exam_id' => $selectedExamId]) }}" class="inline-flex min-h-11 items-center px-3 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-500 hover:bg-slate-50">Reset Kelas</a>
            </div>
            @endif
        </form>

        <div class="grid grid-cols-2 items-center gap-2 w-full lg:w-auto">
            @if($selectedExam)
            <!-- Download Excel Guru -->
            <a href="{{ route('guru.results.export_excel', ['exam_id' => $selectedExam->id, 'kelas' => $selectedKelas]) }}" class="inline-flex min-h-11 justify-center px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition-colors items-center gap-1.5">
                <i class="fa-solid fa-file-excel"></i>
                <span>Download Excel</span>
            </a>
            @endif

            <button type="button" onclick="window.print()" class="inline-flex min-h-11 justify-center px-3.5 py-2 rounded-xl bg-slate-900 text-white font-bold text-xs hover:bg-slate-800 transition-colors items-center gap-1.5">
                <i class="fa-solid fa-print"></i>
                <span>Cetak Rekap</span>
            </button>
        </div>
    </div>

    @if($selectedExam)
    <!-- Ringkasan Statistik -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm text-center">
            <div class="text-xs font-semibold text-slate-500 uppercase">Rata-Rata Nilai</div>
            <div class="text-2xl font-extrabold text-cyan-600 font-mono mt-1">{{ $avgScore }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm text-center">
            <div class="text-xs font-semibold text-slate-500 uppercase">Nilai Tertinggi</div>
            <div class="text-2xl font-extrabold text-emerald-600 font-mono mt-1">{{ $maxScore }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm text-center">
            <div class="text-xs font-semibold text-slate-500 uppercase">Nilai Terendah</div>
            <div class="text-2xl font-extrabold text-rose-600 font-mono mt-1">{{ $minScore }}</div>
        </div>
    </div>

    <!-- Tabel Rekap Nilai -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="min-w-0">
                <h3 class="break-words font-extrabold text-slate-900 text-sm">Daftar Nilai: {{ $selectedExam->nama_ujian }}</h3>
                <p class="text-[11px] text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1 font-semibold text-cyan-800 bg-cyan-50 px-2 py-0.5 rounded border border-cyan-200">
                        <i class="fa-solid fa-book-bookmark text-[10px]"></i>
                        {{ $selectedExam->subject->nama_mapel ?? '-' }}
                    </span>
                    <span>•</span>
                    <span class="font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">Rombel: Kelas {{ $selectedExam->target_kelas }}</span>
                </p>
            </div>
            <span class="shrink-0 text-xs font-mono font-bold text-slate-500">{{ $results->count() }} Peserta Didik</span>
        </div>

        <div class="divide-y divide-slate-100 lg:hidden">
            @forelse($results as $index => $res)
                <article class="space-y-3 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-500">{{ $index + 1 }}</span>
                            <div class="min-w-0">
                                <h4 class="break-words text-sm font-bold text-slate-900">{{ $res->user->nama_lengkap ?? 'Siswa' }}</h4>
                                <p class="mt-0.5 break-all font-mono text-xs text-slate-500">NIS: {{ $res->user->username ?? '-' }}</p>
                            </div>
                        </div>
                        @if($res->status_siswa === 'selesai')
                            <span class="shrink-0 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-700">Selesai</span>
                        @else
                            <span class="shrink-0 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[10px] font-bold text-amber-700">{{ ucfirst($res->status_siswa) }}</span>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 gap-3 border-t border-slate-100 pt-3 text-xs">
                        <div class="min-w-0">
                            <span class="block text-[10px] font-bold uppercase text-slate-400">Waktu Submit</span>
                            <span class="mt-1 block break-words text-slate-600">{{ $res->selesai_ujian ? $res->selesai_ujian->format('d/m/Y H:i') : '-' }} WIB</span>
                        </div>
                        <div class="text-right">
                            <span class="block text-[10px] font-bold uppercase text-slate-400">Skor Akhir</span>
                            <span class="mt-1 block font-mono text-lg font-extrabold {{ $res->skor >= 75 ? 'text-emerald-600' : 'text-slate-800' }}">{{ $res->skor }}</span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="px-4 py-10 text-center text-sm text-slate-400">Belum ada siswa yang menyelesaikan ujian ini.</div>
            @endforelse
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">Nama Lengkap Siswa</th>
                        <th class="py-3 px-4 font-mono">NIS</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Waktu Submit</th>
                        <th class="py-3 px-4 text-center">Skor Akhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($results as $index => $res)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 text-center text-slate-400 font-mono">{{ $index + 1 }}</td>
                        <td class="py-3 px-4 font-bold text-slate-900">{{ $res->user->nama_lengkap ?? 'Siswa' }}</td>
                        <td class="py-3 px-4 font-mono text-slate-600">{{ $res->user->username ?? '-' }}</td>
                        <td class="py-3 px-4 text-center">
                            @if($res->status_siswa === 'selesai')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Selesai</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">{{ ucfirst($res->status_siswa) }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center text-slate-500">
                            {{ $res->selesai_ujian ? $res->selesai_ujian->format('d/m/Y H:i') : '-' }} WIB
                        </td>
                        <td class="py-3 px-4 text-center font-mono font-extrabold text-sm {{ $res->skor >= 75 ? 'text-emerald-600' : 'text-slate-800' }}">
                            {{ $res->skor }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">Belum ada siswa yang menyelesaikan ujian ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
