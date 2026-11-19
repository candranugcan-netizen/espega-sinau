@extends('layouts.admin')

@section('page_title', 'Live Monitoring & Sesi Ujian Siswa')

@section('content')
<div class="space-y-6">

    <!-- Header Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase">Total Sesi</div>
                <div class="text-xl font-extrabold text-slate-900 font-mono">{{ $stats['total'] }}</div>
            </div>
            <i class="fa-solid fa-users text-blue-500 text-xl"></i>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase">Sedang Ujian</div>
                <div class="text-xl font-extrabold text-amber-600 font-mono">{{ $stats['pengerjaan'] }}</div>
            </div>
            <i class="fa-solid fa-hourglass-half text-amber-500 text-xl"></i>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase">Telah Selesai</div>
                <div class="text-xl font-extrabold text-emerald-600 font-mono">{{ $stats['selesai'] }}</div>
            </div>
            <i class="fa-solid fa-circle-check text-emerald-500 text-xl"></i>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase">Sesi Terblokir</div>
                <div class="text-xl font-extrabold text-rose-600 font-mono">{{ $stats['terblokir'] }}</div>
            </div>
            <i class="fa-solid fa-shield-halved text-rose-500 text-xl"></i>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between col-span-2 sm:col-span-1">
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase">Total Pelanggaran</div>
                <div class="text-xl font-extrabold text-rose-700 font-mono">{{ $stats['total_pelanggaran'] ?? 0 }}x</div>
            </div>
            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-xl"></i>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.sessions.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama siswa / NIS..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <select name="exam_id" onchange="this.form.submit()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">Semua Ujian</option>
                    @foreach($exams as $e)
                        <option value="{{ $e->id }}" {{ request('exam_id') == $e->id ? 'selected' : '' }}>{{ $e->nama_ujian }} ({{ $e->target_kelas }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="status" onchange="this.form.submit()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">Semua Status Sesi</option>
                    <option value="pengerjaan" {{ request('status') == 'pengerjaan' ? 'selected' : '' }}>Sedang Pengerjaan</option>
                    <option value="terblokir" {{ request('status') == 'terblokir' ? 'selected' : '' }}>Terblokir (Perlu Bantuan)</option>
                    <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai Ujian</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition-colors">
                    Terapkan
                </button>
                @if(request()->hasAny(['q', 'exam_id', 'status']))
                <a href="{{ route('admin.sessions.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-500 hover:text-slate-800 text-xs font-bold transition-colors">
                    Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Bulk Action Bar -->
    <form id="bulkSessionForm" action="{{ route('admin.sessions.bulk_action') }}" method="POST">
        @csrf
        <input type="hidden" name="action_type" id="bulkSessionActionType" value="">

        <div id="bulkSessionBar" class="hidden mb-4 p-3 bg-brand-50 border border-brand-200 rounded-2xl flex flex-wrap items-center justify-between gap-3 shadow-sm transition-all animate-fadeIn">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-600 text-white text-xs font-bold" id="selectedSessionCount">0</span>
                <span class="text-xs font-bold text-slate-800">Sesi terpilih untuk aksi massal:</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <!-- Submit Paksa Terpilih -->
                <button type="button" onclick="submitBulkSession('force_submit')" class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs transition flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-check-double"></i> Submit Paksa Terpilih
                </button>
                <!-- Buka Blokir Terpilih -->
                <button type="button" onclick="submitBulkSession('unblock')" class="px-3 py-1.5 rounded-xl bg-white border border-rose-300 text-rose-700 hover:bg-rose-50 font-bold text-xs transition flex items-center gap-1.5">
                    <i class="fa-solid fa-lock-open"></i> Buka Blokir
                </button>
                <!-- Reset Terpilih -->
                <button type="button" onclick="submitBulkSession('reset')" class="px-3 py-1.5 rounded-xl bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 font-bold text-xs transition flex items-center gap-1.5">
                    <i class="fa-solid fa-rotate-left"></i> Reset Sesi
                </button>
                <!-- Hapus Terpilih -->
                <button type="button" onclick="submitBulkSession('delete')" class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition flex items-center gap-1.5">
                    <i class="fa-solid fa-trash-can"></i> Hapus Sesi
                </button>
            </div>
        </div>

        <!-- Sessions Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4 w-10 text-center">
                                <input type="checkbox" id="selectAllSessions" onchange="toggleSelectAllSessions(this)" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                            </th>
                            <th class="py-3.5 px-3 w-12 text-center">No</th>
                            <th class="py-3.5 px-4">Nama Siswa</th>
                            <th class="py-3.5 px-4">Ujian & Mapel</th>
                            <th class="py-3.5 px-4 text-center">Kelas</th>
                            <th class="py-3.5 px-4 text-center">Jawaban</th>
                            <th class="py-3.5 px-4 text-center">Sisa Waktu</th>
                            <th class="py-3.5 px-4 text-center">Status Sesi</th>
                            <th class="py-3.5 px-4 text-center">Pelanggaran</th>
                            <th class="py-3.5 px-4 text-center">Skor</th>
                            <th class="py-3.5 px-4 text-right">Aksi Proktor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($sessions as $index => $session)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4 text-center">
                                <input type="checkbox" name="selected_ids[]" value="{{ $session->id }}" onchange="updateSelectedSessionCount()" class="session-checkbox w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                            </td>
                            <td class="py-3 px-3 text-center text-slate-400 font-mono">{{ $sessions->firstItem() + $index }}</td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $session->user->nama_lengkap ?? 'Siswa' }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">NIS: {{ $session->user->username ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="text-slate-800 font-semibold">{{ $session->exam->nama_ujian ?? '-' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $session->exam->subject->nama_mapel ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700">
                                    {{ $session->user->kelas ?? '-' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-bold text-slate-700">
                                {{ $session->answers_count }} Dijawab
                            </td>
                            <td class="py-3 px-4 text-center font-mono text-slate-600">
                                @if($session->sisa_waktu !== null)
                                    {{ gmdate('H:i:s', max(0, $session->sisa_waktu)) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($session->status_siswa === 'selesai')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Selesai
                                    </span>
                                @elseif($session->status_siswa === 'terblokir')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 animate-pulse flex items-center justify-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                        Terblokir
                                    </span>
                                @elseif($session->status_siswa === 'pengerjaan')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Mengerjakan
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                        Belum Mulai
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if(($session->jumlah_blokir ?? 0) > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200 shadow-sm" title="Terblokir sebanyak {{ $session->jumlah_blokir }} kali">
                                        <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                                        <span>{{ $session->jumlah_blokir }}x Blokir</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold text-emerald-600 bg-emerald-50 border border-emerald-100">
                                        <i class="fa-solid fa-check text-[10px]"></i> 0x
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-extrabold text-sm {{ $session->skor >= 75 ? 'text-emerald-600' : 'text-slate-800' }}">
                                {{ $session->skor }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Buka Blokir -->
                                    @if($session->status_siswa === 'terblokir')
                                    <button type="button" onclick="event.preventDefault(); document.getElementById('unblock-sess-{{ $session->id }}').submit();" class="px-2.5 py-1 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-[11px] shadow-sm flex items-center gap-1" title="Buka Blokir">
                                        <i class="fa-solid fa-lock-open"></i>
                                        <span>Buka</span>
                                    </button>
                                    @endif

                                    <!-- Force Submit Individual -->
                                    @if($session->status_siswa === 'pengerjaan' || $session->status_siswa === 'terblokir')
                                    <button type="button" onclick="event.preventDefault(); if(confirm('Kumpulkan paksa ujian siswa {{ addslashes($session->user->nama_lengkap ?? '') }} sekarang?')) document.getElementById('force-submit-sess-{{ $session->id }}').submit();" class="px-2.5 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] shadow-sm flex items-center gap-1" title="Submit Paksa">
                                        <i class="fa-solid fa-check-double"></i>
                                        <span>Force Submit</span>
                                    </button>
                                    @endif

                                    <!-- Reset Sesi -->
                                    <button type="button" onclick="event.preventDefault(); if(confirm('Reset sesi siswa {{ addslashes($session->user->nama_lengkap ?? '') }}? Siswa harus mulai ujian dari awal.')) document.getElementById('reset-sess-{{ $session->id }}').submit();" class="p-1.5 rounded-lg text-slate-400 hover:text-brand-600 hover:bg-slate-100" title="Reset Sesi">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="py-12 text-center text-slate-400">Tidak ada sesi siswa yang cocok dengan filter.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($sessions->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $sessions->links() }}
            </div>
            @endif
        </div>
    </form>

    <!-- Hidden Individual Forms -->
    @foreach($sessions as $session)
        <form id="unblock-sess-{{ $session->id }}" action="{{ route('admin.sessions.unblock', $session) }}" method="POST" class="hidden">@csrf</form>
        <form id="force-submit-sess-{{ $session->id }}" action="{{ route('admin.sessions.force_submit', $session) }}" method="POST" class="hidden">@csrf</form>
        <form id="reset-sess-{{ $session->id }}" action="{{ route('admin.sessions.reset', $session) }}" method="POST" class="hidden">@csrf</form>
    @endforeach

</div>

<script>
    function toggleSelectAllSessions(master) {
        const checkboxes = document.querySelectorAll('.session-checkbox');
        checkboxes.forEach(cb => cb.checked = master.checked);
        updateSelectedSessionCount();
    }

    function updateSelectedSessionCount() {
        const selected = document.querySelectorAll('.session-checkbox:checked');
        const count = selected.length;
        document.getElementById('selectedSessionCount').textContent = count;
        const bar = document.getElementById('bulkSessionBar');
        if (count > 0) {
            bar.classList.remove('hidden');
        } else {
            bar.classList.add('hidden');
            const master = document.getElementById('selectAllSessions');
            if (master) master.checked = false;
        }
    }

    function submitBulkSession(action) {
        const selected = document.querySelectorAll('.session-checkbox:checked');
        if (selected.length === 0) {
            alert('Pilih minimal satu sesi ujian!');
            return;
        }

        let confirmMsg = 'Lanjutkan aksi ini?';
        if (action === 'force_submit') {
            confirmMsg = `PERINGATAN: Kumpulkan dan selesaikan paksa ${selected.length} sesi ujian siswa terpilih sekarang? Nilai akan dihitung otomatis.`;
        } else if (action === 'reset') {
            confirmMsg = `Reset total ${selected.length} sesi siswa terpilih? Jawaban tersimpan akan dihapus dan siswa dapat mengulang dari awal.`;
        } else if (action === 'unblock') {
            confirmMsg = `Buka blokir ujian untuk ${selected.length} sesi siswa terpilih?`;
        } else if (action === 'delete') {
            confirmMsg = `Hapus ${selected.length} riwayat sesi ujian terpilih secara permanen?`;
        }

        if (confirm(confirmMsg)) {
            document.getElementById('bulkSessionActionType').value = action;
            document.getElementById('bulkSessionForm').submit();
        }
    }
</script>
@endsection
