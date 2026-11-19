@extends('layouts.admin')

@section('page_title', 'Kelola Jadwal Ujian')

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Daftar Jadwal Ujian</h2>
            <p class="text-xs text-slate-500 mt-1">Konfigurasi jadwal waktu, token akses, sasaran kelas, dan durasi ujian CBT.</p>
        </div>
        <button type="button" onclick="openModal('addExamModal')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow transition-colors">
            <i class="fa-solid fa-plus text-xs"></i>
            <span>Buat Ujian Baru</span>
        </button>
    </div>

    @if(session('error'))
    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-3 shadow-sm animate-fadeIn">
        <i class="fa-solid fa-triangle-exclamation text-rose-600 text-lg shrink-0"></i>
        <div class="font-semibold">{{ session('error') }}</div>
    </div>
    @endif

    @if(session('success'))
    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-3 shadow-sm animate-fadeIn">
        <i class="fa-solid fa-circle-check text-emerald-600 text-lg shrink-0"></i>
        <div class="font-semibold">{{ session('success') }}</div>
    </div>
    @endif

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.exams.index') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
            <div>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama ujian / token..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <select name="academic_year_id" onchange="this.form.submit()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">Semua Tahun Ajaran</option>
                    @foreach($academicYears ?? [] as $ay)
                        <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>TA: {{ $ay->full_label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="subject_id" onchange="this.form.submit()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">Semua Mata Pelajaran</option>
                    @foreach($subjects as $s)
                        <option value="{{ $s->id }}" {{ request('subject_id') == $s->id ? 'selected' : '' }}>{{ $s->nama_mapel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="kelas" onchange="this.form.submit()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">Semua Target Kelas</option>
                    @foreach($classesList as $k)
                        <option value="{{ $k }}" {{ request('kelas') == $k ? 'selected' : '' }}>Kelas {{ $k }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['q', 'academic_year_id', 'subject_id', 'kelas']))
                <a href="{{ route('admin.exams.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-500 hover:text-slate-800 text-xs font-bold transition-colors">
                    Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Exams Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4">Nama Ujian</th>
                        <th class="py-3.5 px-4">Mata Pelajaran</th>
                        <th class="py-3.5 px-4 text-center">Kelas</th>
                        <th class="py-3.5 px-4">Waktu Mulai</th>
                        <th class="py-3.5 px-4 text-center">Durasi</th>
                        <th class="py-3.5 px-4 text-center">Token Ujian</th>
                        <th class="py-3.5 px-4 text-center">Peserta</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($exams as $index => $exam)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 text-center text-slate-400 font-mono">{{ $exams->firstItem() + $index }}</td>
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900 text-xs">{{ $exam->nama_ujian }}</div>
                            <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                @if($exam->academicYear)
                                    <span class="text-[10px] text-slate-500 font-medium">TA: {{ $exam->academicYear->full_label }}</span>
                                @endif
                                @if($exam->bankSoal)
                                    <span class="text-[10px] text-brand-600 flex items-center gap-1" title="Paket Bank Soal Terhubung">
                                        <i class="fa-solid fa-box-archive text-[9px]"></i>
                                        <span>{{ $exam->bankSoal->nama_bank }} ({{ $exam->bankSoal->guru->nama_lengkap ?? 'Guru' }})</span>
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-800">{{ $exam->subject->nama_mapel ?? '-' }}</div>
                            @if($exam->bankSoal)
                                <div class="text-[10px] text-slate-500 mt-0.5">
                                    Rilis Guru: <span class="font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">{{ $exam->bankSoal->kelas_rilis ?: 'Semua' }}</span>
                                    <span class="text-slate-400">({{ $exam->bankSoal->questions_count ?? $exam->bankSoal->questions()->count() }} Soal)</span>
                                </div>
                            @else
                                <div class="text-[10px] text-slate-400 italic">Sesuai kelas rilis guru</div>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-blue-50 text-brand-700 border border-blue-200">
                                {{ $exam->target_kelas }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-slate-600">
                            {{ $exam->waktu_mulai ? $exam->waktu_mulai->format('d M Y, H:i') : '-' }} WIB
                        </td>
                        <td class="py-3 px-4 text-center text-slate-700">{{ $exam->durasi }} Menit</td>
                        <td class="py-3 px-4 text-center">
                            <div class="inline-flex items-center gap-1.5 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200 font-mono font-bold text-brand-700">
                                <span>{{ $exam->token_ujian }}</span>
                                <form action="{{ route('admin.exams.regenerate_token', $exam) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" title="Reset Token Ujian" class="text-slate-400 hover:text-brand-600">
                                        <i class="fa-solid fa-arrows-rotate text-[10px]"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-center font-bold text-slate-700">
                            <a href="{{ route('admin.sessions.index', ['exam_id' => $exam->id]) }}" class="hover:underline text-brand-600">
                                {{ $exam->sessions_count }} Sesi
                            </a>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" onclick="editExamById({{ $exam->id }})" class="p-1.5 rounded-lg text-slate-500 hover:text-brand-600 hover:bg-slate-100" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('admin.exams.destroy', $exam) }}" method="POST" onsubmit="return confirm('Hapus ujian ini? Riwayat jawaban siswa juga akan terhapus.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-slate-100" title="Hapus">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-slate-400">Tidak ada jadwal ujian yang sesuai.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($exams->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $exams->links() }}
        </div>
        @endif
    </div>

</div>

<!-- Modal Tambah Ujian -->
<div id="addExamModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-5 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-extrabold text-slate-900 text-lg">Buat Jadwal Ujian Baru</h3>
            <button type="button" onclick="closeModal('addExamModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup Modal"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form action="{{ route('admin.exams.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Nama Ujian</label>
                <input type="text" name="nama_ujian" required placeholder="Contoh: ASTS GANJIL BAHASA INGGRIS" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <!-- Pilih Paket Bank Soal Guru -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="font-bold text-slate-700 uppercase">Paket Bank Soal (Rilis Guru)</label>
                    <span class="text-[10px] text-brand-600 font-semibold">Cegah tumpang tindih soal antar-guru</span>
                </div>
                <select name="bank_soal_id" id="add_bank_soal_id" onchange="onBankSoalSelected(this, 'add')" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">-- Pilih Paket Bank Soal (Rilis Guru) --</option>
                    @foreach($bankSoals as $b)
                        <option value="{{ $b->id }}"
                                data-subject-id="{{ $b->subject_id }}"
                                data-kelas-rilis="{{ $b->kelas_rilis }}"
                                data-guru="{{ $b->guru->nama_lengkap ?? 'Guru Pengampu' }}"
                                data-soal-count="{{ $b->questions_count }}">
                            [{{ $b->subject->nama_mapel ?? '-' }}] {{ $b->nama_bank }} - {{ $b->guru->nama_lengkap ?? 'Guru' }} (Rilis: {{ $b->kelas_rilis ?: 'Belum Ada' }} • {{ $b->questions_count }} Soal)
                        </option>
                    @endforeach
                </select>
                <div id="add_bank_info" class="hidden mt-2 p-3 rounded-2xl bg-blue-50 border border-blue-200 text-[11px] text-slate-700 space-y-1">
                    <!-- Dynamic Info Keterangan Rilis -->
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Mata Pelajaran</label>
                    <select name="subject_id" id="add_subject_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}">{{ $s->nama_mapel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Target Kelas</label>
                    <select name="target_kelas" id="add_target_kelas" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <option value="Semua Kelas">Semua Kelas</option>
                        @foreach($classesList as $k)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Hanya kelas tempat soal dirilis yang dapat dipilih.</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Waktu Mulai</label>
                    <input type="datetime-local" name="waktu_mulai" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Durasi (Menit)</label>
                    <input type="number" name="durasi" value="90" min="5" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Batas Selesai Ujian (Opsional)</label>
                <input type="datetime-local" name="waktu_selesai" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                <span class="text-[10px] text-slate-400">Jika dikosongkan, dihitung otomatis dari Waktu Mulai + Durasi.</span>
            </div>

            <div class="pt-3 flex justify-end gap-2">
                <button type="button" onclick="closeModal('addExamModal')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-colors">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold transition-colors">Simpan & Generate Token</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Ujian -->
<div id="editExamModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-5 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-extrabold text-slate-900 text-lg">Edit Jadwal Ujian</h3>
            <button type="button" onclick="closeModal('editExamModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup Modal"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form id="editExamForm" method="POST" class="space-y-4 text-xs">
            @csrf
            @method('PUT')
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Nama Ujian</label>
                <input type="text" name="nama_ujian" id="edit_nama_ujian" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <!-- Pilih Paket Bank Soal Guru -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="font-bold text-slate-700 uppercase">Paket Bank Soal (Rilis Guru)</label>
                    <span class="text-[10px] text-brand-600 font-semibold">Cegah tumpang tindih soal antar-guru</span>
                </div>
                <select name="bank_soal_id" id="edit_bank_soal_id" onchange="onBankSoalSelected(this, 'edit')" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">-- Pilih Paket Bank Soal (Rilis Guru) --</option>
                    @foreach($bankSoals as $b)
                        <option value="{{ $b->id }}"
                                data-subject-id="{{ $b->subject_id }}"
                                data-kelas-rilis="{{ $b->kelas_rilis }}"
                                data-guru="{{ $b->guru->nama_lengkap ?? 'Guru Pengampu' }}"
                                data-soal-count="{{ $b->questions_count }}">
                            [{{ $b->subject->nama_mapel ?? '-' }}] {{ $b->nama_bank }} - {{ $b->guru->nama_lengkap ?? 'Guru' }} (Rilis: {{ $b->kelas_rilis ?: 'Belum Ada' }} • {{ $b->questions_count }} Soal)
                        </option>
                    @endforeach
                </select>
                <div id="edit_bank_info" class="hidden mt-2 p-3 rounded-2xl bg-blue-50 border border-blue-200 text-[11px] text-slate-700 space-y-1">
                    <!-- Dynamic Info Keterangan Rilis -->
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Mata Pelajaran</label>
                    <select name="subject_id" id="edit_subject_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}">{{ $s->nama_mapel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Target Kelas</label>
                    <select name="target_kelas" id="edit_target_kelas" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <option value="Semua Kelas">Semua Kelas</option>
                        @foreach($classesList as $k)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Hanya kelas tempat soal dirilis yang dapat dipilih.</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Waktu Mulai</label>
                    <input type="datetime-local" name="waktu_mulai" id="edit_waktu_mulai" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Durasi (Menit)</label>
                    <input type="number" name="durasi" id="edit_durasi" min="5" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Batas Selesai Ujian</label>
                    <input type="datetime-local" name="waktu_selesai" id="edit_waktu_selesai" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Token Ujian</label>
                    <input type="text" name="token_ujian" id="edit_token_ujian" maxlength="10" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-mono uppercase font-bold text-brand-700 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="pt-3 flex justify-end gap-2">
                <button type="button" onclick="closeModal('editExamModal')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-colors">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold transition-colors">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Data exams dimuat aman tanpa escaping HTML attribute error
    const examsData = @json($exams->keyBy('id'));

    function editExamById(id) {
        const exam = examsData[id];
        if (exam) {
            editExam(exam);
        }
    }

    function onBankSoalSelected(selectEl, prefix) {
        if (!selectEl || selectEl.selectedIndex < 0) return;
        const option = selectEl.options[selectEl.selectedIndex];
        if (!option) return;

        const subjectId = option.getAttribute('data-subject-id');
        const kelasRilis = option.getAttribute('data-kelas-rilis') || '';
        const guru = option.getAttribute('data-guru') || '';
        const soalCount = option.getAttribute('data-soal-count') || '0';
        const infoEl = document.getElementById(prefix + '_bank_info');
        const subjectSelect = document.getElementById(prefix + '_subject_id');
        const kelasSelect = document.getElementById(prefix + '_target_kelas');

        if (subjectId && subjectSelect) {
            subjectSelect.value = subjectId;
        }

        if (option.value && infoEl) {
            infoEl.classList.remove('hidden');
            infoEl.innerHTML = `
                <div class="flex items-center gap-1.5 font-bold text-brand-800">
                    <i class="fa-solid fa-circle-info text-brand-600"></i>
                    <span>Keterangan Rilis Paket Soal:</span>
                </div>
                <div class="text-[11px] text-slate-600 space-y-0.5 pl-4">
                    <div>• Guru Pengampu: <b>${guru}</b></div>
                    <div>• Kelas Tempat Soal Dirilis: <b class="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">${kelasRilis || 'Belum Ditentukan'}</b> (${soalCount} Butir Soal)</div>
                    <div class="text-rose-600 font-semibold">• Sesuai kebijakan sekolah, mapel hanya dapat diaktifkan pada kelas tempat soal dirilis.</div>
                </div>
            `;
        } else if (infoEl) {
            infoEl.classList.add('hidden');
            infoEl.innerHTML = '';
        }

        if (kelasSelect) {
            filterTargetClasses(kelasSelect, kelasRilis);
        }
    }

    function filterTargetClasses(selectEl, kelasRilis) {
        if (!selectEl) return;

        // Inisialisasi data-original-text jika belum ada
        Array.from(selectEl.options).forEach(opt => {
            if (!opt.getAttribute('data-original-text')) {
                opt.setAttribute('data-original-text', opt.text);
            }
        });

        if (!kelasRilis) {
            Array.from(selectEl.options).forEach(opt => {
                opt.disabled = false;
                opt.text = opt.getAttribute('data-original-text') || opt.value;
            });
            return;
        }

        const allowed = kelasRilis.toUpperCase().split(/[,\s;]+/).filter(Boolean);
        const isAll = allowed.includes('ALL') || allowed.includes('SEMUA') || allowed.includes('SEMUA KELAS');

        Array.from(selectEl.options).forEach(opt => {
            const val = opt.value.toUpperCase();
            const origText = opt.getAttribute('data-original-text') || opt.value;
            if (isAll || allowed.includes(val) || opt.value === '') {
                opt.disabled = false;
                opt.text = origText;
            } else {
                opt.disabled = true;
                opt.text = origText + ' (Belum Dirilis)';
            }
        });

        if (selectEl.options[selectEl.selectedIndex]?.disabled) {
            const firstValid = Array.from(selectEl.options).find(opt => !opt.disabled && opt.value !== '');
            if (firstValid) {
                selectEl.value = firstValid.value;
            }
        }
    }

    function formatDateTimeLocal(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) {
            return dateStr.length >= 16 ? dateStr.substring(0, 16) : '';
        }
        const pad = n => n < 10 ? '0' + n : n;
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function editExam(exam) {
        if (!exam) return;
        document.getElementById('editExamForm').action = '/admin/exams/' + exam.id;
        document.getElementById('edit_nama_ujian').value = exam.nama_ujian || '';
        document.getElementById('edit_subject_id').value = exam.subject_id || '';
        document.getElementById('edit_durasi').value = exam.durasi || 90;
        document.getElementById('edit_token_ujian').value = exam.token_ujian || '';

        const bankSelect = document.getElementById('edit_bank_soal_id');
        if (bankSelect) {
            bankSelect.value = exam.bank_soal_id || '';
            onBankSoalSelected(bankSelect, 'edit');
        }

        // Pastikan nilai target_kelas tetap sama jika valid setelah filter dijalankan
        const targetKelasEl = document.getElementById('edit_target_kelas');
        if (targetKelasEl && exam.target_kelas) {
            targetKelasEl.value = exam.target_kelas;
        }

        document.getElementById('edit_waktu_mulai').value = formatDateTimeLocal(exam.waktu_mulai);
        document.getElementById('edit_waktu_selesai').value = formatDateTimeLocal(exam.waktu_selesai);

        openModal('editExamModal');
    }
</script>
@endsection

