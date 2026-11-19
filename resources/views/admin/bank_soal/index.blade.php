@extends('layouts.admin')

@section('page_title', 'Bank Soal Pusat')

@section('content')
<div class="space-y-6">

    <!-- Tabs Nav -->
    <div class="flex items-center gap-2 border-b border-slate-200">
        <a href="{{ route('admin.bank_soal.index', ['tab' => 'bank']) }}" class="px-5 py-3 border-b-2 font-bold text-sm flex items-center gap-2 transition-all {{ $tab === 'bank' ? 'border-brand-600 text-brand-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <i class="fa-solid fa-layer-group"></i>
            <span>Paket Bank Soal</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-mono {{ $tab === 'bank' ? 'bg-blue-100 text-brand-700' : 'bg-slate-100 text-slate-600' }}">{{ $banks->total() }}</span>
        </a>
        <a href="{{ route('admin.bank_soal.index', ['tab' => 'soal']) }}" class="px-5 py-3 border-b-2 font-bold text-sm flex items-center gap-2 transition-all {{ $tab === 'soal' ? 'border-brand-600 text-brand-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <i class="fa-solid fa-list-check"></i>
            <span>Daftar Butir Soal</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-mono {{ $tab === 'soal' ? 'bg-blue-100 text-brand-700' : 'bg-slate-100 text-slate-600' }}">{{ $questions->total() }}</span>
        </a>
    </div>

    @if($tab === 'bank')
    <!-- TAB 1: PAKET BANK SOAL -->
    <div class="space-y-4">
        <!-- Actions & Filter -->
        <div class="flex flex-col sm:flex-row justify-between gap-4">
            <form method="GET" action="{{ route('admin.bank_soal.index') }}" class="flex flex-wrap gap-2 flex-1">
                <input type="hidden" name="tab" value="bank">
                <input type="text" name="q_bank" value="{{ request('q_bank') }}" placeholder="Cari nama bank / guru / mapel..." class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none min-w-[220px]">
                <select name="subject_id" onchange="this.form.submit()" class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">Semua Mapel</option>
                    @foreach($subjects as $s)
                        <option value="{{ $s->id }}" {{ request('subject_id') == $s->id ? 'selected' : '' }}>{{ $s->nama_mapel }}</option>
                    @endforeach
                </select>
                <select name="status_rilis" onchange="this.form.submit()" class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="all">Semua Status Rilis</option>
                    <option value="rilis" {{ request('status_rilis') == 'rilis' ? 'selected' : '' }}>Sudah Dirilis</option>
                    <option value="draft" {{ request('status_rilis') == 'draft' ? 'selected' : '' }}>Draft</option>
                </select>
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold">Filter</button>
            </form>

            <button type="button" onclick="openModal('addBankModal')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow shrink-0">
                <i class="fa-solid fa-folder-plus"></i>
                <span>Tambah Bank Soal</span>
            </button>
        </div>

        <!-- Tabel Bank Soal -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4">Nama Bank Soal</th>
                            <th class="py-3.5 px-4">Mata Pelajaran</th>
                            <th class="py-3.5 px-4">Guru Pembuat</th>
                            <th class="py-3.5 px-4 text-center">Total Soal</th>
                            <th class="py-3.5 px-4">Status & Sasaran</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($banks as $index => $b)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4 text-center text-slate-400 font-mono">{{ $banks->firstItem() + $index }}</td>
                            <td class="py-3 px-4">
                                <a href="{{ route('admin.bank_soal.index', ['tab' => 'soal', 'bank_id' => $b->id]) }}" class="font-bold text-slate-900 hover:text-brand-600">
                                    {{ $b->nama_bank }}
                                </a>
                            </td>
                            <td class="py-3 px-4 text-slate-700">{{ $b->subject->nama_mapel ?? '-' }}</td>
                            <td class="py-3 px-4 text-slate-600">{{ $b->guru->nama_lengkap ?? '-' }}</td>
                            <td class="py-3 px-4 text-center font-bold text-slate-800">
                                <span class="px-2 py-0.5 rounded bg-blue-50 text-brand-700 font-mono">{{ $b->questions_count }} Soal</span>
                            </td>
                            <td class="py-3 px-4">
                                @if($b->status_rilis === 'rilis')
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Rilis: {{ $b->kelas_rilis ?: 'Semua' }}
                                    </span>
                                @else
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                        Draft
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <form action="{{ route('admin.bank_soal.acak_soal', $b) }}" method="POST" onsubmit="return confirm('Acak urutan nomor butir soal pada paket {{ $b->nama_bank }}?');" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 rounded-lg bg-purple-50 text-purple-700 hover:bg-purple-600 hover:text-white font-bold text-[11px] border border-purple-200 transition" title="Acak Nomor Urut Soal Paket Ini">
                                            <i class="fa-solid fa-shuffle mr-1"></i> Acak
                                        </button>
                                    </form>
                                    <a href="{{ route('guru.questions.export_pdf', $b) }}" target="_blank" class="px-2 py-1 rounded-lg bg-slate-900 text-white font-bold text-[11px] hover:bg-slate-800" title="Cetak PDF Naskah Soal">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </a>
                                    <a href="{{ route('guru.questions.export_excel', $b) }}" class="px-2 py-1 rounded-lg bg-emerald-600 text-white font-bold text-[11px] hover:bg-emerald-700" title="Export Excel">
                                        <i class="fa-solid fa-file-excel"></i>
                                    </a>
                                    <a href="{{ route('admin.bank_soal.index', ['tab' => 'soal', 'bank_id' => $b->id]) }}" class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 font-bold text-[11px]" title="Lihat Butir Soal">
                                        <i class="fa-solid fa-eye mr-1"></i> Soal
                                    </a>
                                    <form action="{{ route('admin.bank_soal.destroy', $b) }}" method="POST" onsubmit="return confirm('Hapus paket bank soal ini beserta butir soal di dalamnya?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-slate-100" title="Hapus">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">Tidak ada paket bank soal yang sesuai.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($banks->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $banks->links() }}
            </div>
            @endif
        </div>
    </div>
    @else
    <!-- TAB 2: BUTIR SOAL -->
    <div class="space-y-4">
        <!-- Filter & Header Actions -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
            <form method="GET" action="{{ route('admin.bank_soal.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 flex-1 w-full md:w-auto">
                <input type="hidden" name="tab" value="soal">
                <div>
                    <input type="text" name="q_soal" value="{{ request('q_soal') }}" placeholder="Cari teks pertanyaan..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <select name="bank_id" onchange="this.form.submit()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <option value="">Semua Paket Bank Soal</option>
                        @foreach($allBanks as $ab)
                            <option value="{{ $ab->id }}" {{ request('bank_id') == $ab->id ? 'selected' : '' }}>{{ $ab->nama_bank }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold">Filter</button>
                    @if(request()->hasAny(['q_soal', 'bank_id']))
                        <a href="{{ route('admin.bank_soal.index', ['tab' => 'soal']) }}" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-500 text-xs font-bold">Reset</a>
                    @endif
                </div>
            </form>

            <button type="button" onclick="openModal('addSoalModal')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow shrink-0 w-full md:w-auto justify-center">
                <i class="fa-solid fa-plus"></i>
                <span>Tambah Butir Soal</span>
            </button>
        </div>

        @php
            $selectedBank = request('bank_id') ? $allBanks->firstWhere('id', request('bank_id')) : null;
        @endphp

        <!-- Banner Opsi Paket Terpilih (Acak & Reset Soal Paket) -->
        @if($selectedBank)
        <div class="p-3.5 bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-2xl flex flex-wrap items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-purple-600 text-white flex items-center justify-center font-bold text-sm shadow-sm"><i class="fa-solid fa-layer-group"></i></span>
                <div>
                    <div class="text-xs font-extrabold text-slate-900">Paket Terpilih: {{ $selectedBank->nama_bank }} ({{ $selectedBank->subject->nama_mapel ?? '-' }})</div>
                    <div class="text-[11px] text-purple-700 font-medium">Kelola nomor urut seluruh butir soal dalam paket ini</div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <form action="{{ route('admin.bank_soal.acak_soal', $selectedBank) }}" method="POST" onsubmit="return confirm('Acak seluruh nomor urut butir soal pada paket {{ $selectedBank->nama_bank }}?');">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-sm transition flex items-center gap-1.5">
                        <i class="fa-solid fa-shuffle"></i>
                        <span>Acak Semua Soal di Paket Ini</span>
                    </button>
                </form>
                <form action="{{ route('admin.bank_soal.reset_urutan', $selectedBank) }}" method="POST" onsubmit="return confirm('Reset nomor urut butir soal di paket {{ $selectedBank->nama_bank }} kembali ke standar?');">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-sm transition flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-rotate-left"></i>
                        <span>Reset Urutan</span>
                    </button>
                </form>
            </div>
        </div>
        @endif

        <!-- Form Bulk Action & Multi Select -->
        <form id="bulkQuestionForm" action="{{ route('admin.bank_soal.questions.bulk_action') }}" method="POST">
            @csrf
            <input type="hidden" name="action_type" id="bulkQuestionActionType" value="">
            <input type="hidden" name="target_bank_id" id="bulkTargetBankId" value="">

            <!-- Floating / Sticky Bulk Action Bar -->
            <div id="bulkQuestionBar" class="hidden p-3.5 bg-blue-50 border border-blue-200 rounded-2xl flex flex-wrap items-center justify-between gap-3 shadow-md mb-4 transition-all">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-600 text-white text-xs font-bold" id="selectedQuestionCount">0</span>
                    <span class="text-xs font-bold text-slate-800">Butir soal terpilih (Multiple Select):</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Tombol Acak Soal Terpilih -->
                    <button type="button" onclick="submitBulkQuestion('acak')" class="px-3.5 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-sm transition flex items-center gap-1.5">
                        <i class="fa-solid fa-shuffle"></i>
                        <span>Acak Urutan Terpilih</span>
                    </button>
                    <!-- Tombol Pindahkan ke Paket Lain -->
                    <button type="button" onclick="openModal('bulkMoveModal')" class="px-3.5 py-1.5 rounded-xl bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-bold text-xs shadow-sm transition flex items-center gap-1.5">
                        <i class="fa-solid fa-folder-tree"></i>
                        <span>Pindahkan ke Bank Lain</span>
                    </button>
                    <!-- Tombol Hapus Terpilih -->
                    <button type="button" onclick="submitBulkQuestion('delete')" class="px-3.5 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-sm transition flex items-center gap-1.5">
                        <i class="fa-solid fa-trash-can"></i>
                        <span>Hapus Terpilih</span>
                    </button>
                </div>
            </div>

            <!-- Top Indicator Checkbox -->
            <div class="flex items-center justify-between px-2 pb-2 text-xs text-slate-500 font-semibold">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="selectAllQuestions" onchange="toggleSelectAllQuestions(this)" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                    <span class="text-xs font-bold text-slate-800">Pilih Semua Soal (Multiple Select)</span>
                </label>
                <span>Menampilkan {{ $questions->count() }} dari {{ $questions->total() }} Butir Soal</span>
            </div>

            <!-- Questions List Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="py-3.5 px-3 w-10 text-center">
                                    <input type="checkbox" id="selectAllQuestionsHeader" onchange="toggleSelectAllQuestions(this)" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                                </th>
                                <th class="py-3.5 px-3 w-12 text-center">No</th>
                                <th class="py-3.5 px-4">Pertanyaan & Pilihan Jawaban</th>
                                <th class="py-3.5 px-4 w-44">Paket Bank Soal</th>
                                <th class="py-3.5 px-4 text-center w-20">Kunci</th>
                                <th class="py-3.5 px-4 text-right w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @forelse($questions as $index => $q)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <!-- Checkbox Multi Select -->
                                <td class="py-3 px-3 text-center align-top">
                                    <input type="checkbox" name="selected_ids[]" value="{{ $q->id }}" onchange="updateSelectedQuestionCount()" class="question-checkbox w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300 mt-1 cursor-pointer">
                                </td>
                                <td class="py-3 px-3 text-center text-slate-400 font-mono align-top">{{ $questions->firstItem() + $index }}</td>
                                <td class="py-3 px-4 space-y-2">
                                    <div class="text-sm font-semibold text-slate-900 leading-relaxed">
                                        {!! nl2br(e(Str::limit($q->pertanyaan, 200))) !!}
                                    </div>
                                    @if($q->img_pertanyaan)
                                    <div class="pt-1">
                                        <img src="{{ $q->getImageUrl($q->img_pertanyaan) }}" alt="Gambar Soal" class="max-h-28 rounded-lg border border-slate-200 object-contain bg-slate-50 p-1">
                                    </div>
                                    @endif
                                    <div class="grid grid-cols-2 gap-2 text-[11px] pt-1">
                                        <div class="p-1.5 rounded border flex flex-col justify-between {{ $q->kunci_jawaban === 'A' ? 'bg-emerald-50 border-emerald-300 text-emerald-800 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600' }}">
                                            <div><strong>A.</strong> {{ Str::limit($q->pil_a, 50) }}</div>
                                            @if($q->img_a)
                                                <span class="text-[10px] text-brand-600 font-normal mt-1"><i class="fa-solid fa-image"></i> Gambar Opsi A</span>
                                            @endif
                                        </div>
                                        <div class="p-1.5 rounded border flex flex-col justify-between {{ $q->kunci_jawaban === 'B' ? 'bg-emerald-50 border-emerald-300 text-emerald-800 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600' }}">
                                            <div><strong>B.</strong> {{ Str::limit($q->pil_b, 50) }}</div>
                                            @if($q->img_b)
                                                <span class="text-[10px] text-brand-600 font-normal mt-1"><i class="fa-solid fa-image"></i> Gambar Opsi B</span>
                                            @endif
                                        </div>
                                        <div class="p-1.5 rounded border flex flex-col justify-between {{ $q->kunci_jawaban === 'C' ? 'bg-emerald-50 border-emerald-300 text-emerald-800 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600' }}">
                                            <div><strong>C.</strong> {{ Str::limit($q->pil_c, 50) }}</div>
                                            @if($q->img_c)
                                                <span class="text-[10px] text-brand-600 font-normal mt-1"><i class="fa-solid fa-image"></i> Gambar Opsi C</span>
                                            @endif
                                        </div>
                                        <div class="p-1.5 rounded border flex flex-col justify-between {{ $q->kunci_jawaban === 'D' ? 'bg-emerald-50 border-emerald-300 text-emerald-800 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600' }}">
                                            <div><strong>D.</strong> {{ Str::limit($q->pil_d, 50) }}</div>
                                            @if($q->img_d)
                                                <span class="text-[10px] text-brand-600 font-normal mt-1"><i class="fa-solid fa-image"></i> Gambar Opsi D</span>
                                            @endif
                                        </div>
                                        @if($q->pil_e || $q->img_e)
                                        <div class="p-1.5 rounded border col-span-2 flex flex-col justify-between {{ $q->kunci_jawaban === 'E' ? 'bg-emerald-50 border-emerald-300 text-emerald-800 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600' }}">
                                            <div><strong>E.</strong> {{ Str::limit($q->pil_e, 50) }}</div>
                                            @if($q->img_e)
                                                <span class="text-[10px] text-brand-600 font-normal mt-1"><i class="fa-solid fa-image"></i> Gambar Opsi E</span>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-slate-600 align-top">
                                    <div class="font-bold text-slate-800">{{ $q->bankSoal->nama_bank ?? 'Umum' }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $q->subject->nama_mapel ?? '-' }}</div>
                                    <div class="text-[10px] font-mono text-purple-700 font-bold mt-1 bg-purple-50 px-2 py-0.5 rounded inline-block">Urutan: #{{ $q->urutan ?: '-' }}</div>
                                </td>
                                <td class="py-3 px-4 text-center align-top">
                                    <span class="w-7 h-7 rounded-full bg-emerald-600 text-white font-extrabold flex items-center justify-center mx-auto text-xs font-mono shadow-sm">
                                        {{ $q->kunci_jawaban }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right align-top">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Tombol Edit Soal Admin -->
                                        <button type="button" onclick="openEditSoalModalById({{ $q->id }})" class="p-2 rounded-xl bg-blue-50 text-brand-700 hover:bg-brand-600 hover:text-white font-bold text-xs transition shadow-sm" title="Edit Butir Soal Ini">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <!-- Tombol Hapus Soal Admin -->
                                        <form action="{{ route('admin.bank_soal.questions.destroy', $q) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus butir soal #{{ $q->id }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white font-bold text-xs transition shadow-sm" title="Hapus Soal">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">Tidak ada butir soal yang sesuai.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($questions->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $questions->links() }}
                </div>
                @endif
            </div>
        </form>
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

        <form action="{{ route('admin.bank_soal.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Nama Paket Bank Soal</label>
                <input type="text" name="nama_bank" required placeholder="Contoh: Bank Soal Informatika Kelas 8" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Mata Pelajaran</label>
                    <select name="subject_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}">{{ $s->nama_mapel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Guru Pengampu</label>
                    <select name="guru_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}">{{ $t->nama_lengkap }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Rilis ke Kelas (Bisa Pilih Beberapa)</label>
                <div class="grid grid-cols-3 gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50">
                    @foreach($classesList as $k)
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="kelas_rilis[]" value="{{ $k }}" class="rounded text-brand-600">
                        <span class="text-xs font-semibold">{{ $k }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="pt-3 flex justify-end gap-2">
                <button type="button" onclick="closeModal('addBankModal')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold">Simpan Paket</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Butir Soal Baru (Admin) -->
<div id="addSoalModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-3xl w-full max-h-[90vh] overflow-y-auto shadow-2xl space-y-5 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Tambah Butir Soal Baru</h3>
                <p class="text-xs text-slate-500 mt-0.5">Tambahkan soal pilihan ganda baru ke paket bank soal</p>
            </div>
            <button type="button" onclick="closeModal('addSoalModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup Modal"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form action="{{ route('admin.bank_soal.questions.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
            @csrf

            <!-- Pilih Paket Bank Soal -->
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Paket Bank Soal Tujuan</label>
                <select name="bank_soal_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">-- Pilih Paket Bank Soal --</option>
                    @foreach($allBanks as $ab)
                        <option value="{{ $ab->id }}" {{ request('bank_id') == $ab->id ? 'selected' : '' }}>
                            {{ $ab->nama_bank }} ({{ $ab->subject->nama_mapel ?? '-' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Teks Pertanyaan -->
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Teks Pertanyaan</label>
                <textarea name="pertanyaan" rows="3" required placeholder="Tuliskan butir soal di sini..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"></textarea>
            </div>

            <!-- Upload Gambar Pertanyaan -->
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Gambar Pertanyaan (Opsional)</label>
                <input type="file" name="img_pertanyaan" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-brand-700 hover:file:bg-blue-100">
            </div>

            <!-- Pilihan Jawaban A - E -->
            <div class="space-y-3 pt-2">
                <label class="block font-bold text-slate-700 uppercase">Pilihan Jawaban & Gambar Opsi</label>

                <!-- Pilihan A -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">A</span>
                        <input type="text" name="pil_a" required placeholder="Teks Pilihan A" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                    <div class="pl-8 flex items-center gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Gambar Pilihan A:</label>
                        <input type="file" name="img_a" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>

                <!-- Pilihan B -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">B</span>
                        <input type="text" name="pil_b" required placeholder="Teks Pilihan B" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                    <div class="pl-8 flex items-center gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Gambar Pilihan B:</label>
                        <input type="file" name="img_b" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>

                <!-- Pilihan C -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">C</span>
                        <input type="text" name="pil_c" placeholder="Teks Pilihan C" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                    <div class="pl-8 flex items-center gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Gambar Pilihan C:</label>
                        <input type="file" name="img_c" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>

                <!-- Pilihan D -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">D</span>
                        <input type="text" name="pil_d" placeholder="Teks Pilihan D" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                    <div class="pl-8 flex items-center gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Gambar Pilihan D:</label>
                        <input type="file" name="img_d" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>

                <!-- Pilihan E -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">E</span>
                        <input type="text" name="pil_e" placeholder="Teks Pilihan E (Opsional)" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                    <div class="pl-8 flex items-center gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Gambar Pilihan E:</label>
                        <input type="file" name="img_e" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>
            </div>

            <!-- Kunci Jawaban & Urutan -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Kunci Jawaban Benar</label>
                    <select name="kunci_jawaban" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Urutan Nomor Soal (Opsional)</label>
                    <input type="number" name="urutan" min="0" placeholder="Auto / isi nomor" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="pt-3 flex justify-end gap-2">
                <button type="button" onclick="closeModal('addSoalModal')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold">Simpan Soal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Butir Soal (Admin) -->
<div id="editSoalModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-3xl w-full max-h-[90vh] overflow-y-auto shadow-2xl space-y-5 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Edit Butir Soal (Admin)</h3>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui pertanyaan, pilihan jawaban, kunci, atau gambar pendukung</p>
            </div>
            <button type="button" onclick="closeModal('editSoalModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup Modal"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form id="editSoalForm" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
            @csrf
            @method('PUT')

            <!-- Paket Bank Soal -->
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Paket Bank Soal</label>
                <select name="bank_soal_id" id="edit_bank_soal_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">-- Tetap / Tanpa Paket --</option>
                    @foreach($allBanks as $ab)
                        <option value="{{ $ab->id }}">{{ $ab->nama_bank }} ({{ $ab->subject->nama_mapel ?? '-' }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Teks Pertanyaan -->
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Teks Pertanyaan</label>
                <textarea name="pertanyaan" id="edit_pertanyaan" rows="3" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"></textarea>
            </div>

            <!-- Gambar Pertanyaan -->
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Gambar Pertanyaan</label>
                <div id="preview_img_pertanyaan" class="mb-2 hidden">
                    <img src="" id="img_preview_src" class="max-h-36 rounded-lg border border-slate-200 object-contain bg-slate-50 p-1">
                    <label class="inline-flex items-center gap-1.5 mt-1 text-rose-600 text-[11px] font-semibold cursor-pointer">
                        <input type="checkbox" name="delete_img_pertanyaan" value="1"> Hapus gambar pertanyaan saat disimpan
                    </label>
                </div>
                <input type="file" name="img_pertanyaan" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-brand-700 hover:file:bg-blue-100">
            </div>

            <!-- Pilihan Jawaban A - E -->
            <div class="space-y-3 pt-2">
                <label class="block font-bold text-slate-700 uppercase">Pilihan Jawaban & Gambar Opsi</label>

                <!-- Pilihan A -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">A</span>
                        <input type="text" name="pil_a" id="edit_pil_a" required class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                    <div id="preview_img_a" class="pl-8 mb-1 hidden">
                        <img src="" id="a_preview_src" class="max-h-24 rounded border border-slate-200 object-contain bg-white p-1">
                        <label class="inline-flex items-center gap-1.5 mt-1 text-rose-600 text-[11px] font-semibold cursor-pointer">
                            <input type="checkbox" name="delete_img_a" value="1"> Hapus gambar A
                        </label>
                    </div>
                    <div class="pl-8 flex items-center gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Ganti Gambar A:</label>
                        <input type="file" name="img_a" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>

                <!-- Pilihan B -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">B</span>
                        <input type="text" name="pil_b" id="edit_pil_b" required class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                    <div id="preview_img_b" class="pl-8 mb-1 hidden">
                        <img src="" id="b_preview_src" class="max-h-24 rounded border border-slate-200 object-contain bg-white p-1">
                        <label class="inline-flex items-center gap-1.5 mt-1 text-rose-600 text-[11px] font-semibold cursor-pointer">
                            <input type="checkbox" name="delete_img_b" value="1"> Hapus gambar B
                        </label>
                    </div>
                    <div class="pl-8 flex items-center gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Ganti Gambar B:</label>
                        <input type="file" name="img_b" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>

                <!-- Pilihan C -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">C</span>
                        <input type="text" name="pil_c" id="edit_pil_c" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                    <div id="preview_img_c" class="pl-8 mb-1 hidden">
                        <img src="" id="c_preview_src" class="max-h-24 rounded border border-slate-200 object-contain bg-white p-1">
                        <label class="inline-flex items-center gap-1.5 mt-1 text-rose-600 text-[11px] font-semibold cursor-pointer">
                            <input type="checkbox" name="delete_img_c" value="1"> Hapus gambar C
                        </label>
                    </div>
                    <div class="pl-8 flex items-center gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Ganti Gambar C:</label>
                        <input type="file" name="img_c" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>

                <!-- Pilihan D -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">D</span>
                        <input type="text" name="pil_d" id="edit_pil_d" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                    <div id="preview_img_d" class="pl-8 mb-1 hidden">
                        <img src="" id="d_preview_src" class="max-h-24 rounded border border-slate-200 object-contain bg-white p-1">
                        <label class="inline-flex items-center gap-1.5 mt-1 text-rose-600 text-[11px] font-semibold cursor-pointer">
                            <input type="checkbox" name="delete_img_d" value="1"> Hapus gambar D
                        </label>
                    </div>
                    <div class="pl-8 flex items-center gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Ganti Gambar D:</label>
                        <input type="file" name="img_d" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>

                <!-- Pilihan E -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">E</span>
                        <input type="text" name="pil_e" id="edit_pil_e" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                    <div id="preview_img_e" class="pl-8 mb-1 hidden">
                        <img src="" id="e_preview_src" class="max-h-24 rounded border border-slate-200 object-contain bg-white p-1">
                        <label class="inline-flex items-center gap-1.5 mt-1 text-rose-600 text-[11px] font-semibold cursor-pointer">
                            <input type="checkbox" name="delete_img_e" value="1"> Hapus gambar E
                        </label>
                    </div>
                    <div class="pl-8 flex items-center gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Ganti Gambar E:</label>
                        <input type="file" name="img_e" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>
            </div>

            <!-- Kunci Jawaban & Urutan -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Kunci Jawaban Benar</label>
                    <select name="kunci_jawaban" id="edit_kunci" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Urutan Nomor Soal</label>
                    <input type="number" name="urutan" id="edit_urutan" min="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="pt-3 flex justify-end gap-2">
                <button type="button" onclick="closeModal('editSoalModal')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Pindah Paket Soal Massal (Admin) -->
<div id="bulkMoveModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-5 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Pindahkan Butir Soal Terpilih</h3>
                <p class="text-xs text-slate-500 mt-0.5">Pilih paket bank soal tujuan pemindahan</p>
            </div>
            <button type="button" onclick="closeModal('bulkMoveModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup Modal"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <div class="space-y-4 text-xs">
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Paket Bank Soal Tujuan</label>
                <select id="move_target_bank_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">-- Pilih Paket Bank Soal Tujuan --</option>
                    @foreach($allBanks as $ab)
                        <option value="{{ $ab->id }}">{{ $ab->nama_bank }} ({{ $ab->subject->nama_mapel ?? '-' }})</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Mata pelajaran dan kelas butir soal akan otomatis disinkronkan mengikuti paket tujuan.</p>
            </div>

            <div class="pt-3 flex justify-end gap-2">
                <button type="button" onclick="closeModal('bulkMoveModal')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Batal</button>
                <button type="button" onclick="submitBulkMove()" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold">Pindahkan Sekarang</button>
            </div>
        </div>
    </div>
</div>

<script>
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

    // Multi Select Controls
    function toggleSelectAllQuestions(master) {
        const checkboxes = document.querySelectorAll('.question-checkbox');
        checkboxes.forEach(cb => cb.checked = master.checked);
        const header = document.getElementById('selectAllQuestionsHeader');
        const list = document.getElementById('selectAllQuestions');
        if (header) header.checked = master.checked;
        if (list) list.checked = master.checked;
        updateSelectedQuestionCount();
    }

    function updateSelectedQuestionCount() {
        const selected = document.querySelectorAll('.question-checkbox:checked');
        const count = selected.length;
        const countEl = document.getElementById('selectedQuestionCount');
        if (countEl) countEl.textContent = count;
        const bar = document.getElementById('bulkQuestionBar');
        if (bar) {
            if (count > 0) {
                bar.classList.remove('hidden');
            } else {
                bar.classList.add('hidden');
                const header = document.getElementById('selectAllQuestionsHeader');
                const list = document.getElementById('selectAllQuestions');
                if (header) header.checked = false;
                if (list) list.checked = false;
            }
        }
    }

    function submitBulkQuestion(action) {
        const selected = document.querySelectorAll('.question-checkbox:checked');
        const count = selected.length;
        if (count === 0) {
            alert('Pilih minimal satu butir soal terlebih dahulu!');
            return;
        }

        if (action === 'delete') {
            if (!confirm('Apakah Anda yakin ingin menghapus ' + count + ' butir soal terpilih?')) return;
        } else if (action === 'acak') {
            if (count < 2) {
                alert('Pilih minimal 2 butir soal untuk diacak nomor urutnya!');
                return;
            }
            if (!confirm('Acak nomor urut untuk ' + count + ' butir soal terpilih?')) return;
        }

        document.getElementById('bulkQuestionActionType').value = action;
        document.getElementById('bulkQuestionForm').submit();
    }

    function submitBulkMove() {
        const selected = document.querySelectorAll('.question-checkbox:checked');
        const count = selected.length;
        if (count === 0) {
            alert('Pilih minimal satu butir soal terlebih dahulu!');
            return;
        }

        const targetBankId = document.getElementById('move_target_bank_id').value;
        if (!targetBankId) {
            alert('Pilih paket bank soal tujuan pemindahan!');
            return;
        }

        if (!confirm('Pindahkan ' + count + ' butir soal terpilih ke paket tujuan?')) return;

        document.getElementById('bulkTargetBankId').value = targetBankId;
        document.getElementById('bulkQuestionActionType').value = 'move';
        document.getElementById('bulkQuestionForm').submit();
    }

    // Data butir soal disimpan aman tanpa escaping HTML attribute error
    const questionsData = @json($questions->keyBy('id'));

    function openEditSoalModalById(id) {
        const q = questionsData[id];
        if (q) {
            openEditSoalModal(id, q);
        }
    }

    function openEditSoalModal(id, q) {
        document.getElementById('editSoalForm').action = "/admin/bank-soal/questions/" + id;
        document.getElementById('edit_bank_soal_id').value = q.bank_soal_id || '';
        document.getElementById('edit_pertanyaan').value = q.pertanyaan || '';
        document.getElementById('edit_pil_a').value = q.pil_a || '';
        document.getElementById('edit_pil_b').value = q.pil_b || '';
        document.getElementById('edit_pil_c').value = q.pil_c || '';
        document.getElementById('edit_pil_d').value = q.pil_d || '';
        document.getElementById('edit_pil_e').value = q.pil_e || '';
        document.getElementById('edit_kunci').value = (q.kunci_jawaban || 'A').toUpperCase();
        document.getElementById('edit_urutan').value = q.urutan || 0;

        // Preview Gambar Pertanyaan
        const previewQ = document.getElementById('preview_img_pertanyaan');
        const imgQ = document.getElementById('img_preview_src');
        if (q.img_pertanyaan) {
            imgQ.src = "/assets/img/soal/" + q.img_pertanyaan;
            previewQ.classList.remove('hidden');
        } else {
            previewQ.classList.add('hidden');
        }

        // Preview Opsi A - E
        ['a', 'b', 'c', 'd', 'e'].forEach(opt => {
            const field = 'img_' + opt;
            const container = document.getElementById('preview_' + field);
            const img = document.getElementById(opt + '_preview_src');
            if (container && img) {
                if (q[field]) {
                    img.src = "/assets/img/soal/" + q[field];
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                }
            }
        });

        openModal('editSoalModal');
    }
</script>
@endsection

