@extends('layouts.admin')

@section('page_title', 'Detail Rombel ' . $rombel->nama_rombel)

@section('content')
<div class="space-y-6">

    <!-- Back Button & Breadcrumbs -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.rombels.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-brand-600 transition-colors">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Daftar Rombel</span>
        </a>
        <div class="text-xs text-slate-400">
            SIAKAD & Akademik / <span class="text-slate-700 font-semibold">Rombel {{ $rombel->nama_rombel }}</span>
        </div>
    </div>

    <!-- Rombel Header Profile Card -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-2xl bg-brand-50 border border-brand-200 text-brand-600 flex items-center justify-center font-extrabold text-2xl shrink-0">
                    {{ $rombel->nama_rombel }}
                </div>
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h2 class="text-xl font-bold text-slate-900">Rombel {{ $rombel->nama_rombel }}</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold 
                            {{ $rombel->tingkat == '7' ? 'bg-blue-100 text-blue-800' : ($rombel->tingkat == '8' ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800') }}">
                            Tingkat Kelas {{ $rombel->tingkat }}
                        </span>
                        @if($rombel->academicYear)
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                TA: {{ $rombel->academicYear->nama }} ({{ ucfirst($rombel->academicYear->semester) }})
                            </span>
                        @endif
                    </div>
                    
                    <div class="mt-2 flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-slate-600">
                        <div class="flex items-center gap-1.5">
                            <i class="fa-solid fa-user-tie text-slate-400"></i>
                            <span>Wali Kelas:</span>
                            <span class="font-bold text-slate-800">{{ $rombel->waliKelas ? $rombel->waliKelas->nama_lengkap : 'Belum ditentukan' }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <i class="fa-solid fa-door-open text-slate-400"></i>
                            <span>Ruang:</span>
                            <span class="font-bold text-slate-800">{{ $rombel->ruang ?? 'Ruang Default' }}</span>
                        </div>
                    </div>

                    @if($rombel->keterangan)
                        <p class="text-xs text-slate-500 mt-2 bg-slate-50 p-2.5 rounded-lg border border-slate-200 inline-block">
                            <i class="fa-solid fa-info-circle text-brand-500 mr-1"></i>{{ $rombel->keterangan }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3 shrink-0 bg-slate-50 p-4 rounded-xl border border-slate-200">
                <div class="text-center px-3 border-r border-slate-200">
                    <div class="text-[11px] font-bold text-slate-500 uppercase">Total Siswa</div>
                    <div class="text-xl font-extrabold text-brand-600">{{ $students->count() }}</div>
                </div>
                <div class="text-center px-3 border-r border-slate-200">
                    <div class="text-[11px] font-bold text-slate-500 uppercase">Laki-Laki</div>
                    <div class="text-xl font-extrabold text-blue-700">{{ $rombel->jumlah_laki }}</div>
                </div>
                <div class="text-center px-3 border-r border-slate-200">
                    <div class="text-[11px] font-bold text-slate-500 uppercase">Perempuan</div>
                    <div class="text-xl font-extrabold text-rose-700">{{ $rombel->jumlah_perempuan }}</div>
                </div>
                <div class="text-center px-3">
                    <div class="text-[11px] font-bold text-slate-500 uppercase">Ujian Aktif</div>
                    <div class="text-xl font-extrabold text-emerald-700">{{ $exams->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Container -->
    <div x-data="{ activeTab: 'siswa' }" class="space-y-4">
        <div class="flex border-b border-slate-200">
            <button @click="activeTab = 'siswa'" :class="activeTab === 'siswa' ? 'border-brand-600 text-brand-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 font-medium'" class="py-3 px-5 border-b-2 text-xs flex items-center gap-2 transition-colors">
                <i class="fa-solid fa-user-graduate"></i>
                <span>Daftar Siswa ({{ $students->count() }})</span>
            </button>
            <button @click="activeTab = 'ujian'" :class="activeTab === 'ujian' ? 'border-brand-600 text-brand-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 font-medium'" class="py-3 px-5 border-b-2 text-xs flex items-center gap-2 transition-colors">
                <i class="fa-solid fa-file-signature"></i>
                <span>Jadwal Ujian Terhubung ({{ $exams->count() }})</span>
            </button>
        </div>

        <!-- Tab 1: Daftar Siswa -->
        <div x-show="activeTab === 'siswa'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Anggota Siswa Kelas {{ $rombel->nama_rombel }}
                </div>
                <div class="text-xs text-slate-500">
                    Terhubung ke Akun Siswa & Ujian CBT
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-bold text-[11px]">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-4">Nama Lengkap</th>
                            <th class="py-3 px-4">NIS / NISN</th>
                            <th class="py-3 px-4">Username Login</th>
                            <th class="py-3 px-4 text-center">Jenis Kelamin</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($students as $idx => $student)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-4 text-center text-slate-400 font-semibold">{{ $idx + 1 }}</td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 text-sm">{{ $student->nama_lengkap }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $student->email ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-mono text-slate-800">{{ $student->nis ?? '-' }}</div>
                                    <div class="text-[10px] text-slate-400">NISN: {{ $student->nisn ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-block px-2 py-0.5 bg-slate-100 rounded font-mono text-slate-700 font-semibold">
                                        {{ $student->username }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if(strtoupper($student->jenis_kelamin) == 'L' || strtoupper($student->jenis_kelamin) == 'LAKI-LAKI')
                                        <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full font-bold text-[10px]">Laki-laki</span>
                                    @elseif(strtoupper($student->jenis_kelamin) == 'P' || strtoupper($student->jenis_kelamin) == 'PEREMPUAN')
                                        <span class="px-2 py-0.5 bg-rose-50 text-rose-700 rounded-full font-bold text-[10px]">Perempuan</span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($student->is_blocked)
                                        <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">Diblokir</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">Aktif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-users-slash text-2xl mb-1 text-slate-300"></i>
                                    <div>Belum ada siswa yang terdaftar di kelas {{ $rombel->nama_rombel }}.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 2: Jadwal Ujian Terhubung -->
        <div x-show="activeTab === 'ujian'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden" style="display: none;">
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Daftar Ujian yang Menargetkan Kelas {{ $rombel->nama_rombel }}
                </div>
                <div class="text-xs text-slate-500">
                    Sinkron otomatis berdasarkan Target Kelas Ujian
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-bold text-[11px]">
                        <tr>
                            <th class="py-3 px-4">Nama Ujian</th>
                            <th class="py-3 px-4">Mata Pelajaran</th>
                            <th class="py-3 px-4">Guru Pembuat</th>
                            <th class="py-3 px-4">Waktu Mulai</th>
                            <th class="py-3 px-4 text-center">Durasi</th>
                            <th class="py-3 px-4 text-center">Target Rilis</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($exams as $exam)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 text-sm">{{ $exam->nama_ujian }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">Token: {{ $exam->token }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-semibold text-slate-800">{{ $exam->subject->nama_mapel ?? '-' }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-slate-700">{{ $exam->guru->nama_lengkap ?? '-' }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <div>{{ \Carbon\Carbon::parse($exam->start_time)->translatedFormat('d M Y, H:i') }}</div>
                                    <div class="text-[10px] text-slate-400">s/d {{ \Carbon\Carbon::parse($exam->end_time)->translatedFormat('d M Y, H:i') }}</div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="font-bold text-slate-800">{{ $exam->duration }} Menit</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 bg-slate-100 rounded text-[11px] font-semibold text-slate-700">
                                        {{ $exam->target_kelas }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($exam->is_active)
                                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold text-[10px]">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 bg-slate-100 text-slate-500 rounded-full font-semibold text-[10px]">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-file-circle-xmark text-2xl mb-1 text-slate-300"></i>
                                    <div>Belum ada jadwal ujian yang dirilis untuk kelas {{ $rombel->nama_rombel }}.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

