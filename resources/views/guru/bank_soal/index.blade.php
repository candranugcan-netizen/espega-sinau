@extends('layouts.guru')

@section('page_title', 'Bank Soal Saya')

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Kelola Paket Bank Soal Anda</h2>
            <p class="text-xs text-slate-500 mt-1">Buat paket soal baru, edit butir soal, dan atur rilis kelas sasaran ujian.</p>
        </div>
        <button type="button" onclick="openModal('addBankModal')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-sm shadow transition-colors">
            <i class="fa-solid fa-plus text-xs"></i>
            <span>Buat Bank Soal Baru</span>
        </button>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('guru.bank_soal.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama bank soal..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
            </div>
            <div>
                <select name="subject_id" onchange="this.form.submit()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    <option value="">Semua Mapel</option>
                    @foreach($subjects as $s)
                        <option value="{{ $s->id }}" {{ request('subject_id') == $s->id ? 'selected' : '' }}>{{ $s->nama_mapel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold">Filter</button>
                @if(request()->hasAny(['q', 'subject_id']))
                    <a href="{{ route('guru.bank_soal.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-500 text-xs font-bold">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Grid Bank Soal Cards -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($banks as $b)
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex flex-col justify-between hover:border-cyan-400 hover:shadow-md transition-all">
            <div class="space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <span class="px-2.5 py-1 rounded-lg bg-cyan-50 text-cyan-700 font-bold text-xs border border-cyan-100">
                        {{ $b->subject->nama_mapel ?? 'Mata Pelajaran' }}
                    </span>
                    @if($b->status_rilis === 'rilis')
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Rilis
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                            Draft
                        </span>
                    @endif
                </div>

                <h3 class="font-bold text-slate-900 text-base leading-snug">
                    {{ $b->nama_bank }}
                </h3>

                <div class="text-xs text-slate-500 space-y-1">
                    <div><i class="fa-solid fa-list-check mr-1.5 text-slate-400"></i> Jumlah: <strong>{{ $b->questions_count }} Butir Soal</strong></div>
                    <div><i class="fa-solid fa-users mr-1.5 text-slate-400"></i> Kelas Rilis: <strong>{{ $b->kelas_rilis ?: 'Belum Dirilis' }}</strong></div>
                </div>
            </div>

            <div class="pt-5 mt-5 border-t border-slate-100 flex items-center justify-between gap-1.5">
                <a href="{{ route('guru.questions.index', $b) }}" class="flex-1 py-2 px-3 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs text-center transition-colors">
                    <i class="fa-solid fa-pen-to-square mr-1"></i> Soal
                </a>
                <a href="{{ route('guru.questions.export_pdf', $b) }}" target="_blank" class="p-2 rounded-xl bg-slate-900 text-white hover:bg-slate-800 text-xs flex items-center justify-center w-8 h-8" title="Cetak PDF Naskah Soal">
                    <i class="fa-solid fa-file-pdf"></i>
                </a>
                <a href="{{ route('guru.questions.export_excel', $b) }}" class="p-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 text-xs flex items-center justify-center w-8 h-8" title="Export Excel">
                    <i class="fa-solid fa-file-excel"></i>
                </a>
                <form action="{{ route('guru.bank_soal.destroy', $b) }}" method="POST" onsubmit="return confirm('Hapus paket bank soal ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-slate-100 transition-colors" title="Hapus Paket">
                        <i class="fa-solid fa-trash-can text-sm"></i>
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-3 bg-white rounded-3xl p-12 text-center text-slate-400 border border-slate-200">
            <i class="fa-solid fa-folder-open text-4xl mb-3 text-slate-300"></i>
            <p>Belum ada paket bank soal. Klik tombol "Buat Bank Soal Baru" di atas untuk memulai.</p>
        </div>
        @endforelse
    </div>

    @if($banks->hasPages())
    <div class="p-4 bg-white rounded-2xl border border-slate-200">
        {{ $banks->links() }}
    </div>
    @endif

</div>

<!-- Modal Tambah Bank Soal -->
<div id="addBankModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-5 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-extrabold text-slate-900 text-lg">Buat Paket Bank Soal Baru</h3>
            <button type="button" onclick="closeModal('addBankModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup Modal"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form action="{{ route('guru.bank_soal.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Nama Paket Bank Soal</label>
                <input type="text" name="nama_bank" required placeholder="Contoh: Bank Soal Matematika Kelas 8" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Mata Pelajaran</label>
                <select name="subject_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    @foreach($subjects as $s)
                        <option value="{{ $s->id }}">{{ $s->nama_mapel }} (Kelas {{ $s->kelas }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Rilis ke Rombel Kelas (Bisa Pilih Beberapa)</label>
                <div class="grid grid-cols-3 gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50">
                    @foreach($classesList as $k)
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="kelas_rilis[]" value="{{ $k }}" class="rounded text-cyan-600">
                        <span class="text-xs font-semibold">{{ $k }}</span>
                    </label>
                    @endforeach
                </div>
                <span class="text-[10px] text-slate-400 mt-1 block">Jika tidak ada yang dicentang, paket disimpan sebagai status Draft.</span>
            </div>

            <div class="pt-3 flex justify-end gap-2">
                <button type="button" onclick="closeModal('addBankModal')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold">Simpan Paket</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
</script>
@endsection

