@extends('layouts.admin')

@section('page_title', 'Laporan Hasil Nilai Ujian')

@section('content')
<div class="space-y-6">

    <!-- Filter & Export Controls -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex flex-col lg:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-center gap-3 w-full lg:w-auto flex-1">
            <!-- Filter Berdasarkan Mata Pelajaran -->
            <div class="w-full sm:w-auto">
                <label class="text-[11px] font-bold text-slate-500 uppercase block mb-1">Mata Pelajaran:</label>
                <select name="subject_id" onchange="this.form.submit()" class="w-full sm:w-52 px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-brand-500 focus:outline-none bg-white">
                    <option value="">Semua Mata Pelajaran</option>
                    @foreach($subjects as $sb)
                        <option value="{{ $sb->id }}" {{ $selectedSubjectId == $sb->id ? 'selected' : '' }}>
                            {{ $sb->nama_mapel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Pilih Jadwal Ujian (Dengan Tampilan Nama Mapel Jelas) -->
            <div class="w-full sm:w-auto">
                <label class="text-[11px] font-bold text-slate-500 uppercase block mb-1">Pilih Jadwal Ujian:</label>
                <select name="exam_id" onchange="this.form.submit()" class="w-full sm:w-80 px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-brand-500 focus:outline-none bg-white">
                    <option value="">Semua Jadwal Ujian</option>
                    @foreach($exams as $e)
                        <option value="{{ $e->id }}" {{ $selectedExamId == $e->id ? 'selected' : '' }}>
                            [{{ $e->subject->nama_mapel ?? 'Mapel' }}] {{ $e->nama_ujian }} (Kelas {{ $e->target_kelas }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Rombel Kelas -->
            <div class="w-full sm:w-auto">
                <label class="text-[11px] font-bold text-slate-500 uppercase block mb-1">Filter Rombel Kelas:</label>
                <select name="kelas" onchange="this.form.submit()" class="w-full sm:w-40 px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-brand-500 focus:outline-none bg-white">
                    <option value="">Semua Kelas</option>
                    @foreach($classesList as $k)
                        <option value="{{ $k }}" {{ $selectedKelas == $k ? 'selected' : '' }}>Kelas {{ $k }}</option>
                    @endforeach
                </select>
            </div>

            @if($selectedSubjectId || $selectedExamId || $selectedKelas)
            <div class="self-end pb-0.5">
                <a href="{{ route('admin.reports.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-500 text-xs font-bold hover:bg-slate-50 transition-colors">Reset Filter</a>
            </div>
            @endif
        </form>

        <div class="flex items-center gap-2 w-full lg:w-auto justify-end">
            <!-- Unduh Excel Lengkap -->
            <a href="{{ route('admin.reports.export_excel', ['exam_id' => $selectedExamId, 'kelas' => $selectedKelas, 'subject_id' => $selectedSubjectId]) }}" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition-colors flex items-center gap-2">
                <i class="fa-solid fa-file-excel"></i>
                <span>Download Excel Lengkap</span>
            </a>

            <!-- Print View -->
            <button type="button" onclick="window.print()" class="px-4 py-2.5 rounded-xl bg-slate-900 text-white font-bold text-xs hover:bg-slate-800 transition-colors flex items-center gap-2">
                <i class="fa-solid fa-print"></i>
                <span>Cetak</span>
            </button>
        </div>
    </div>

    <!-- Ringkasan Statistik Ujian -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm text-center">
            <div class="text-xs font-semibold text-slate-500 uppercase">Rata-Rata Nilai</div>
            <div class="text-2xl font-extrabold text-brand-600 font-mono mt-1">{{ $avgScore }}</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm text-center">
            <div class="text-xs font-semibold text-slate-500 uppercase">Nilai Tertinggi</div>
            <div class="text-2xl font-extrabold text-emerald-600 font-mono mt-1">{{ $maxScore }}</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm text-center">
            <div class="text-xs font-semibold text-slate-500 uppercase">Nilai Terendah</div>
            <div class="text-2xl font-extrabold text-rose-600 font-mono mt-1">{{ $minScore }}</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm text-center">
            <div class="text-xs font-semibold text-slate-500 uppercase">Tuntas (>= 75)</div>
            <div class="text-2xl font-extrabold text-cyan-600 font-mono mt-1">{{ $tuntasCount }} Siswa</div>
        </div>
    </div>

    <!-- Tabel Rekap Nilai -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-extrabold text-slate-900 text-sm">
                    Rekapitulasi Hasil Ujian: {{ $selectedExam ? $selectedExam->nama_ujian : ($selectedSubject ? 'Mata Pelajaran ' . $selectedSubject->nama_mapel : 'Seluruh Jadwal Ujian') }}
                </h3>
                <p class="text-[11px] text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1 font-semibold text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200">
                        <i class="fa-solid fa-book-bookmark text-[10px]"></i>
                        {{ $selectedExam->subject->nama_mapel ?? ($selectedSubject ? $selectedSubject->nama_mapel : 'Semua Mata Pelajaran') }}
                    </span>
                    @if($selectedKelas)
                        <span>•</span>
                        <span class="font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">Rombel: Kelas {{ $selectedKelas }}</span>
                    @endif
                </p>
            </div>
            <span class="text-xs font-mono font-bold text-slate-500">{{ $results->count() }} Data Sesi</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">Peringkat</th>
                        <th class="py-3 px-4">Nama Lengkap Siswa</th>
                        <th class="py-3 px-4 font-mono">NIS</th>
                        <th class="py-3 px-4 text-center">Kelas</th>
                        <th class="py-3 px-4">Jadwal Ujian & Mata Pelajaran</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Waktu Selesai</th>
                        <th class="py-3 px-4 text-center">Skor Akhir</th>
                        <th class="py-3 px-4 text-center">Predikat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($results as $index => $res)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 text-center font-mono font-bold {{ $index < 3 ? 'text-amber-600' : 'text-slate-400' }}">
                            @if($index === 0) 🥇 1
                            @elseif($index === 1) 🥈 2
                            @elseif($index === 2) 🥉 3
                            @else {{ $index + 1 }}
                            @endif
                        </td>
                        <td class="py-3 px-4 font-bold text-slate-900">{{ $res->user->nama_lengkap ?? 'Siswa' }}</td>
                        <td class="py-3 px-4 font-mono text-slate-600">{{ $res->user->username ?? '-' }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700">
                                {{ $res->user->kelas ?? '-' }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="text-slate-900 font-bold text-xs">{{ $res->exam->nama_ujian ?? '-' }}</div>
                            <div class="inline-flex items-center gap-1 text-[11px] font-semibold text-brand-700 bg-brand-50/80 px-2 py-0.5 rounded border border-brand-200/60 mt-0.5">
                                <i class="fa-solid fa-book-open text-[10px]"></i>
                                <span>{{ $res->exam->subject->nama_mapel ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($res->status_siswa === 'selesai')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Selesai</span>
                            @elseif($res->status_siswa === 'terblokir')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Pelanggaran</span>
                            @elseif($res->status_siswa === 'pengerjaan')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Sedang Ujian</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500">Belum</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center text-slate-500">
                            {{ $res->selesai_ujian ? $res->selesai_ujian->format('d/m H:i') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-center font-mono font-extrabold text-sm {{ $res->skor >= 75 ? 'text-emerald-600' : 'text-slate-800' }}">
                            {{ $res->skor }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($res->skor >= 85)
                                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">Amat Baik (A)</span>
                            @elseif($res->skor >= 75)
                                <span class="px-2 py-0.5 rounded bg-blue-100 text-brand-800 font-bold text-[10px]">Baik (B)</span>
                            @elseif($res->skor >= 60)
                                <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-bold text-[10px]">Cukup (C)</span>
                            @else
                                <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-bold text-[10px]">Perlu Remidi (D)</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-slate-400">Belum ada data nilai hasil ujian yang sesuai dengan filter.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
