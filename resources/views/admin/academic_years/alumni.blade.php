@extends('layouts.admin')

@section('page_title', 'Buku Induk Alumni & Lulusan')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.academic_years.index') }}" class="text-xs font-bold text-slate-400 hover:text-brand-600 flex items-center gap-1 transition">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Tahun Ajaran</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-xs font-bold text-slate-600">Buku Induk Alumni</span>
            </div>
            <h2 class="text-xl font-bold text-slate-900 mt-1">Buku Induk Siswa Lulusan (Alumni)</h2>
            <p class="text-xs text-slate-500 mt-0.5">Seluruh arsip data peserta didik yang telah lulus dari SMP Negeri 3 Ngawen beserta riwayat perjalanan kelasnya.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.users.siswa', ['status_siswa' => 'aktif']) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition">
                <i class="fa-solid fa-user-graduate text-xs text-brand-600"></i>
                <span>Lihat Siswa Aktif</span>
            </a>
            <a href="{{ route('admin.academic_years.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-extrabold text-xs transition shadow-sm">
                <i class="fa-solid fa-calendar-days text-xs"></i>
                <span>Kelola Tahun Ajaran</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.alumni.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama alumni, NISN, atau NIS..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <select name="tahun_lulus_id" onchange="this.form.submit()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">Semua Tahun Kelulusan</option>
                    @foreach($academicYears as $ay)
                        <option value="{{ $ay->id }}" {{ request('tahun_lulus_id') == $ay->id ? 'selected' : '' }}>
                            Lulusan TA {{ $ay->full_label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold">Filter Data</button>
                @if(request()->hasAny(['q', 'tahun_lulus_id']))
                    <a href="{{ route('admin.alumni.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-500 text-xs font-bold">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Alumni Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-700">Daftar Alumni Terdaftar: <b>{{ $alumni->total() }}</b> Orang</span>
            <span class="text-[11px] text-slate-400">SMP Negeri 3 Ngawen</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">No</th>
                        <th class="py-3.5 px-4">Nama Lengkap Alumni</th>
                        <th class="py-3.5 px-4">NISN / NIS</th>
                        <th class="py-3.5 px-4 text-center">L/P</th>
                        <th class="py-3.5 px-4 text-center">Tahun Lulus</th>
                        <th class="py-3.5 px-4">Rekam Jejak Kelas</th>
                        <th class="py-3.5 px-4">Kontak / Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($alumni as $idx => $alm)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-4 text-center text-slate-400 font-mono">{{ $alumni->firstItem() + $idx }}</td>
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-slate-900 text-sm">{{ $alm->nama_lengkap }}</div>
                            <div class="text-[11px] text-slate-400">Username: {{ $alm->username }}</div>
                        </td>
                        <td class="py-3.5 px-4 font-mono text-slate-600">
                            <div>NISN: <b>{{ $alm->nisn ?: '-' }}</b></div>
                            <div class="text-[11px] text-slate-400">NIS: {{ $alm->nis ?: $alm->username }}</div>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($alm->jenis_kelamin === 'L')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">L</span>
                            @elseif($alm->jenis_kelamin === 'P')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-pink-50 text-pink-700 border border-pink-200">P</span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($alm->tahunLulus)
                                <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    {{ $alm->tahunLulus->nama }}
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                    Alumni
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            @if($alm->classHistories && $alm->classHistories->isNotEmpty())
                                <div class="flex flex-wrap gap-1 max-w-sm">
                                    @foreach($alm->classHistories as $ch)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200" title="{{ $ch->catatan ?: ($ch->academicYear?->nama ?? '') }}">
                                            {{ $ch->kelas }} ({{ $ch->academicYear?->nama ?? 'Arsip' }})
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-slate-400 italic text-[11px]">Tercatat sebagai Alumni</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-slate-600 text-[11px]">
                            <div>{{ $alm->telepon ? 'Telp: ' . $alm->telepon : '-' }}</div>
                            <div class="text-slate-400 truncate max-w-xs" title="{{ $alm->alamat }}">{{ $alm->alamat ?: '' }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            Belum ada arsip data alumni yang cocok dengan pencarian.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($alumni->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $alumni->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

