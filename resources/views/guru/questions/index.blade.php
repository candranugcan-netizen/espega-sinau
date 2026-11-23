@extends('layouts.guru')

@section('page_title', 'Kelola Butir Soal - ' . $bank->nama_bank)

@section('content')
<div class="space-y-4 sm:space-y-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('guru.bank_soal.index') }}" class="text-xs font-semibold text-cyan-600 hover:underline">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Bank Soal
                </a>
            </div>
            <h2 class="break-words text-lg sm:text-xl font-bold text-slate-900">{{ $bank->nama_bank }}</h2>
            <p class="mt-1 text-sm leading-relaxed text-slate-500 sm:text-xs">
                Mata Pelajaran: <strong>{{ $bank->subject->nama_mapel ?? '-' }}</strong> • Sasaran: <strong>{{ $bank->kelas_rilis ?: 'Draft (Belum Rilis)' }}</strong> • Total: <strong>{{ $questions->count() }} Butir Soal</strong>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Export PDF -->
            <a href="{{ route('guru.questions.export_pdf', $bank) }}" target="_blank" class="inline-flex min-h-11 items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-sm transition-colors" title="Export PDF Siap Cetak (2 Kolom + Kunci)">
                <i class="fa-solid fa-file-pdf text-xs"></i>
                <span>Cetak PDF</span>
            </a>

            <!-- Export Excel -->
            <a href="{{ route('guru.questions.export_excel', $bank) }}" class="inline-flex min-h-11 items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition-colors" title="Export ke Spreadsheet Excel">
                <i class="fa-solid fa-file-excel text-xs"></i>
                <span>Export Excel</span>
            </a>

            <!-- Import Soal Excel -->
            <button type="button" onclick="openModal('importSoalModal')" class="inline-flex min-h-11 items-center gap-1.5 px-3 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs shadow-sm transition-colors">
                <i class="fa-solid fa-file-import text-xs"></i>
                <span>Import Soal</span>
            </button>

            <!-- Acak Semua Soal -->
            <form action="{{ route('guru.questions.acak_semua', $bank) }}" method="POST" onsubmit="return confirm('Acak urutan seluruh butir soal di paket ini?');" class="inline">
                @csrf
                <button type="submit" class="inline-flex min-h-11 items-center gap-1.5 px-3 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-sm transition-colors" title="Acak urutan semua butir soal">
                    <i class="fa-solid fa-shuffle text-xs"></i>
                    <span>Acak Semua</span>
                </button>
            </form>

            <!-- Reset Urutan -->
            <form action="{{ route('guru.questions.reset_urutan', $bank) }}" method="POST" onsubmit="return confirm('Kembalikan urutan butir soal ke nomor ID default?');" class="inline">
                @csrf
                <button type="submit" class="inline-flex min-h-11 items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold text-xs shadow-sm transition-colors" title="Reset urutan nomor soal">
                    <i class="fa-solid fa-rotate-left text-xs"></i>
                    <span>Reset Urutan</span>
                </button>
            </form>

            <!-- Tambah Soal Manual -->
            <button type="button" onclick="openModal('addSoalModal')" class="inline-flex min-h-11 items-center gap-2 px-3.5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs shadow transition-colors">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Tambah Soal</span>
            </button>
        </div>
    </div>

    <!-- Bulk Action Bar -->
    <form id="bulkQuestionForm" action="{{ route('guru.questions.bulk_action', $bank) }}" method="POST">
        @csrf
        <input type="hidden" name="action_type" id="bulkQuestionActionType" value="">

        <div id="bulkQuestionBar" class="hidden p-3 bg-cyan-50 border border-cyan-200 rounded-2xl flex flex-col sm:flex-row flex-wrap items-start sm:items-center justify-between gap-3 shadow-sm transition-all animate-fadeIn">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-cyan-600 text-white text-xs font-bold" id="selectedQuestionCount">0</span>
                <span class="text-xs font-bold text-slate-800">Butir soal terpilih:</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="submitBulkQuestion('acak')" class="min-h-11 px-3 py-1.5 rounded-xl bg-white border border-purple-200 text-purple-700 hover:bg-purple-50 font-bold text-xs transition flex items-center gap-1.5">
                    <i class="fa-solid fa-shuffle"></i> Acak Urutan Terpilih
                </button>
                <button type="button" onclick="submitBulkQuestion('delete')" class="min-h-11 px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition flex items-center gap-1.5">
                    <i class="fa-solid fa-trash-can"></i> Hapus Terpilih
                </button>
            </div>
        </div>

        @if($questions->isNotEmpty())
        <div class="flex flex-col gap-2 px-2 pt-2 text-xs text-slate-500 font-semibold sm:flex-row sm:items-center sm:justify-between">
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" id="selectAllQuestions" onchange="toggleSelectAllQuestions(this)" class="w-4 h-4 rounded text-cyan-600 focus:ring-cyan-500 border-slate-300">
                <span>Pilih Semua Soal (Multiple Select)</span>
            </label>
            <span>Total {{ $questions->count() }} Soal Terdaftar</span>
        </div>
        @endif

        <!-- Questions List -->
        <div class="space-y-4">
            @forelse($questions as $index => $q)
            <div class="bg-white rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-slate-200 shadow-sm space-y-4 hover:border-cyan-300 transition-colors">
                <div class="flex items-start justify-between gap-2 sm:gap-4">
                    <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                        <input type="checkbox" name="selected_ids[]" value="{{ $q->id }}" onchange="updateSelectedQuestionCount()" class="question-checkbox w-4 h-4 rounded text-cyan-600 focus:ring-cyan-500 border-slate-300 mt-0.5">
                        <span class="w-8 h-8 rounded-xl bg-cyan-50 text-cyan-700 font-bold font-mono text-sm flex items-center justify-center shrink-0 border border-cyan-100">
                            {{ $q->urutan ?: ($index + 1) }}
                        </span>
                        <div class="min-w-0">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Butir Soal #{{ $index + 1 }}</span>
                            <span class="block text-[10px] text-slate-400 font-mono sm:inline sm:ml-2">(ID: #{{ $q->id }})</span>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-1 sm:gap-2">
                        <span class="px-2 sm:px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] sm:text-xs font-bold font-mono">
                            Kunci: {{ $q->kunci_jawaban }}
                        </span>

                        <!-- Edit Button -->
                        <button type="button" onclick="openEditSoalModalById({{ $q->id }})" class="p-1.5 rounded-lg text-slate-400 hover:text-cyan-600 hover:bg-slate-100" title="Edit Butir Soal">
                            <i class="fa-solid fa-pen-to-square text-sm"></i>
                        </button>

                        <!-- Delete Button -->
                        <button type="button" onclick="event.preventDefault(); if(confirm('Hapus butir soal ini?')) document.getElementById('del-q-{{ $q->id }}').submit();" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-slate-100" title="Hapus Soal">
                            <i class="fa-solid fa-trash-can text-sm"></i>
                        </button>
                    </div>
                </div>

                <!-- Teks Pertanyaan -->
                <div class="break-words text-sm font-semibold text-slate-900 leading-relaxed sm:pl-11">
                    {!! nl2br(e($q->pertanyaan)) !!}
                </div>

                <!-- Gambar Pertanyaan jika ada -->
                @if($q->img_pertanyaan)
                <div class="sm:pl-11">
                    <img src="{{ $q->getImageUrl($q->img_pertanyaan) }}" alt="Gambar Soal" class="max-h-60 max-w-full rounded-xl border border-slate-200 shadow-sm object-contain bg-slate-50 p-1">
                </div>
                @endif

                <!-- Pilihan Jawaban A, B, C, D, E (Teks & Gambar Pilihan) -->
                <div class="grid sm:grid-cols-2 gap-3 pt-2 text-xs sm:pl-11">
                    @foreach(['A', 'B', 'C', 'D', 'E'] as $opt)
                        @php
                            $optKey = strtolower($opt);
                            $teks = $q->{'pil_' . $optKey};
                            $gbr = $q->{'img_' . $optKey};
                            $isKunci = (strtoupper($q->kunci_jawaban) === $opt);
                        @endphp

                        @if(!empty($teks) || !empty($gbr))
                        <div class="p-3 rounded-xl border {{ $isKunci ? 'bg-emerald-50 border-emerald-300 text-emerald-900 font-bold' : 'bg-slate-50 border-slate-200 text-slate-700' }} flex flex-col gap-2">
                            <div class="flex items-start gap-2">
                                <strong class="font-mono text-sm {{ $isKunci ? 'text-emerald-700' : 'text-slate-500' }}">{{ $opt }}.</strong>
                                <span class="leading-relaxed">{{ $teks }}</span>
                            </div>
                            @if(!empty($gbr))
                            <div class="mt-1">
                                <img src="{{ $q->getImageUrl($gbr) }}" alt="Pilihan {{ $opt }}" class="max-h-32 rounded-lg border border-slate-200 object-contain bg-white p-1">
                            </div>
                            @endif
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @empty
            <div class="bg-white rounded-xl sm:rounded-3xl p-8 sm:p-12 text-center text-sm text-slate-400 border border-slate-200">
                <i class="fa-solid fa-file-circle-question text-4xl mb-3 text-slate-300"></i>
                <p>Belum ada butir soal di dalam paket ini. Klik tombol "Tambah Soal" atau "Import Soal" di atas.</p>
            </div>
            @endforelse
        </div>
    </form>

    <!-- Hidden Individual Delete Forms -->
    @foreach($questions as $q)
    <form id="del-q-{{ $q->id }}" action="{{ route('guru.questions.destroy', $q) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
    @endforeach

</div>

<!-- Modal Import Soal Excel -->
<div id="importSoalModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-xl sm:rounded-3xl p-4 sm:p-8 max-w-lg w-full shadow-2xl space-y-5 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Import Soal dari Excel</h3>
                <p class="text-xs text-slate-500 mt-0.5">Unggah berkas spreadsheet .xlsx atau .csv</p>
            </div>
            <button type="button" onclick="closeModal('importSoalModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup Modal"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form action="{{ route('guru.questions.import', $bank) }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
            @csrf

            <!-- Panduan Format Kolom -->
            <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-900">
                <div class="font-bold mb-1 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-info"></i> Susunan Kolom Berkas Excel:
                </div>
                <ol class="list-decimal list-inside space-y-0.5 text-[11px] text-amber-800">
                    <li>Kolom A : <b>Pertanyaan</b></li>
                    <li>Kolom B : <b>Pilihan A</b></li>
                    <li>Kolom C : <b>Pilihan B</b></li>
                    <li>Kolom D : <b>Pilihan C</b></li>
                    <li>Kolom E : <b>Pilihan D</b></li>
                    <li>Kolom F : <b>Pilihan E</b> (Opsional)</li>
                    <li>Kolom G : <b>Kunci Jawaban</b> (Huruf A, B, C, D, atau E)</li>
                </ol>
            </div>

            <!-- Upload Area -->
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Pilih Berkas Excel / CSV</label>
                <input type="file" name="file_excel" required accept=".xlsx,.xls,.csv" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
            </div>

            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('guru.questions.template') }}" class="inline-flex items-center gap-1.5 text-cyan-600 hover:text-cyan-800 font-bold text-xs">
                    <i class="fa-solid fa-download"></i> Unduh Template Excel
                </a>
                <div class="flex gap-2">
                    <button type="button" onclick="closeModal('importSoalModal')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold">Mulai Import</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Butir Soal Baru (Dengan Gambar Soal & Pilihan A-E) -->
<div id="addSoalModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-xl sm:rounded-3xl p-4 sm:p-8 max-w-3xl w-full max-h-[90dvh] overflow-y-auto shadow-2xl space-y-5 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Tambah Butir Soal Baru</h3>
                <p class="text-xs text-slate-500 mt-0.5">Mendukung ilustrasi gambar pada pertanyaan dan setiap pilihan jawaban</p>
            </div>
            <button type="button" onclick="closeModal('addSoalModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup Modal"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form action="{{ route('guru.questions.store', $bank) }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
            @csrf

            <!-- Pertanyaan -->
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Teks Pertanyaan</label>
                <textarea name="pertanyaan" rows="3" required placeholder="Tuliskan butir pertanyaan ujian..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none"></textarea>
            </div>

            <!-- Gambar Pertanyaan -->
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Gambar Soal / Stimulus (Opsional)</label>
                <input type="file" name="img_pertanyaan" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100">
            </div>

            <!-- Pilihan Jawaban A - E -->
            <div class="space-y-3 pt-2">
                <label class="block font-bold text-slate-700 uppercase">Pilihan Jawaban & Gambar Opsi</label>

                <!-- Pilihan A -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">A</span>
                        <input type="text" name="pil_a" required placeholder="Teks Pilihan A" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    </div>
                    <div class="flex min-w-0 flex-col gap-1 pl-8 sm:flex-row sm:items-center sm:gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Gambar Pilihan A:</label>
                        <input type="file" name="img_a" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>

                <!-- Pilihan B -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">B</span>
                        <input type="text" name="pil_b" required placeholder="Teks Pilihan B" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    </div>
                    <div class="flex min-w-0 flex-col gap-1 pl-8 sm:flex-row sm:items-center sm:gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Gambar Pilihan B:</label>
                        <input type="file" name="img_b" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>

                <!-- Pilihan C -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">C</span>
                        <input type="text" name="pil_c" placeholder="Teks Pilihan C" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    </div>
                    <div class="flex min-w-0 flex-col gap-1 pl-8 sm:flex-row sm:items-center sm:gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Gambar Pilihan C:</label>
                        <input type="file" name="img_c" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>

                <!-- Pilihan D -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">D</span>
                        <input type="text" name="pil_d" placeholder="Teks Pilihan D" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    </div>
                    <div class="flex min-w-0 flex-col gap-1 pl-8 sm:flex-row sm:items-center sm:gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Gambar Pilihan D:</label>
                        <input type="file" name="img_d" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>

                <!-- Pilihan E -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">E</span>
                        <input type="text" name="pil_e" placeholder="Teks Pilihan E (Opsional)" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    </div>
                    <div class="flex min-w-0 flex-col gap-1 pl-8 sm:flex-row sm:items-center sm:gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Gambar Pilihan E:</label>
                        <input type="file" name="img_e" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>
            </div>

            <!-- Kunci Jawaban -->
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Kunci Jawaban Benar</label>
                <select name="kunci_jawaban" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                    <option value="E">E</option>
                </select>
            </div>

            <div class="pt-3 flex justify-end gap-2">
                <button type="button" onclick="closeModal('addSoalModal')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold">Simpan Soal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Butir Soal -->
<div id="editSoalModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-xl sm:rounded-3xl p-4 sm:p-8 max-w-3xl w-full max-h-[90dvh] overflow-y-auto shadow-2xl space-y-5 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Edit Butir Soal</h3>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui butir pertanyaan atau gambar ilustrasi</p>
            </div>
            <button type="button" onclick="closeModal('editSoalModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup Modal"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form id="editSoalForm" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
            @csrf
            @method('PUT')

            <!-- Pertanyaan -->
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Teks Pertanyaan</label>
                <textarea name="pertanyaan" id="edit_pertanyaan" rows="3" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none"></textarea>
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
                <input type="file" name="img_pertanyaan" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100">
            </div>

            <!-- Pilihan Jawaban A - E -->
            <div class="space-y-3 pt-2">
                <label class="block font-bold text-slate-700 uppercase">Pilihan Jawaban & Gambar Opsi</label>

                <!-- Pilihan A -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">A</span>
                        <input type="text" name="pil_a" id="edit_pil_a" required class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    </div>
                    <div class="flex min-w-0 flex-col gap-1 pl-8 sm:flex-row sm:items-center sm:gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Ganti Gambar A:</label>
                        <input type="file" name="img_a" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>

                <!-- Pilihan B -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">B</span>
                        <input type="text" name="pil_b" id="edit_pil_b" required class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    </div>
                    <div class="flex min-w-0 flex-col gap-1 pl-8 sm:flex-row sm:items-center sm:gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Ganti Gambar B:</label>
                        <input type="file" name="img_b" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>

                <!-- Pilihan C -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">C</span>
                        <input type="text" name="pil_c" id="edit_pil_c" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    </div>
                    <div class="flex min-w-0 flex-col gap-1 pl-8 sm:flex-row sm:items-center sm:gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Ganti Gambar C:</label>
                        <input type="file" name="img_c" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>

                <!-- Pilihan D -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">D</span>
                        <input type="text" name="pil_d" id="edit_pil_d" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    </div>
                    <div class="flex min-w-0 flex-col gap-1 pl-8 sm:flex-row sm:items-center sm:gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Ganti Gambar D:</label>
                        <input type="file" name="img_d" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>

                <!-- Pilihan E -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center font-mono text-xs">E</span>
                        <input type="text" name="pil_e" id="edit_pil_e" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    </div>
                    <div class="flex min-w-0 flex-col gap-1 pl-8 sm:flex-row sm:items-center sm:gap-2">
                        <label class="text-[11px] text-slate-500 font-medium shrink-0">Ganti Gambar E:</label>
                        <input type="file" name="img_e" accept="image/*" class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-white file:border-slate-200 file:text-slate-700">
                    </div>
                </div>
            </div>

            <!-- Kunci Jawaban & Urutan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Kunci Jawaban Benar</label>
                    <select name="kunci_jawaban" id="edit_kunci" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Urutan Nomor Soal</label>
                    <input type="number" name="urutan" id="edit_urutan" min="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                </div>
            </div>

            <div class="pt-3 flex justify-end gap-2">
                <button type="button" onclick="closeModal('editSoalModal')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Data questions dimuat aman tanpa escaping HTML attribute error
    const questionsData = @json($questions->keyBy('id'));

    function openEditSoalModalById(id) {
        const q = questionsData[id];
        if (q) {
            openEditSoalModal(id, q);
        }
    }

    function openEditSoalModal(id, q) {
        document.getElementById('editSoalForm').action = "/guru/questions/" + id;
        document.getElementById('edit_pertanyaan').value = q.pertanyaan || '';
        document.getElementById('edit_pil_a').value = q.pil_a || '';
        document.getElementById('edit_pil_b').value = q.pil_b || '';
        document.getElementById('edit_pil_c').value = q.pil_c || '';
        document.getElementById('edit_pil_d').value = q.pil_d || '';
        document.getElementById('edit_pil_e').value = q.pil_e || '';
        document.getElementById('edit_kunci').value = (q.kunci_jawaban || 'A').toUpperCase();
        document.getElementById('edit_urutan').value = q.urutan || 0;

        const previewContainer = document.getElementById('preview_img_pertanyaan');
        const previewImg = document.getElementById('img_preview_src');
        if (q.img_pertanyaan) {
            previewImg.src = "/assets/img/soal/" + q.img_pertanyaan;
            previewContainer.classList.remove('hidden');
        } else {
            previewContainer.classList.add('hidden');
        }

        openModal('editSoalModal');
    }

    function toggleSelectAllQuestions(master) {
        const checkboxes = document.querySelectorAll('.question-checkbox');
        checkboxes.forEach(cb => cb.checked = master.checked);
        updateSelectedQuestionCount();
    }

    function updateSelectedQuestionCount() {
        const selected = document.querySelectorAll('.question-checkbox:checked');
        const count = selected.length;
        document.getElementById('selectedQuestionCount').textContent = count;
        const bar = document.getElementById('bulkQuestionBar');
        if (count > 0) {
            bar.classList.remove('hidden');
        } else {
            bar.classList.add('hidden');
            const master = document.getElementById('selectAllQuestions');
            if (master) master.checked = false;
        }
    }

    function submitBulkQuestion(action) {
        const selected = document.querySelectorAll('.question-checkbox:checked');
        if (selected.length === 0) {
            alert('Pilih minimal satu butir soal!');
            return;
        }

        let confirmMsg = 'Lanjutkan aksi massal ini?';
        if (action === 'delete') {
            confirmMsg = `PERINGATAN: Hapus permanen ${selected.length} butir soal terpilih beserta berkas gambarnya?`;
        } else if (action === 'acak') {
            confirmMsg = `Acak posisi urutan pada ${selected.length} butir soal terpilih?`;
        }

        if (confirm(confirmMsg)) {
            document.getElementById('bulkQuestionActionType').value = action;
            document.getElementById('bulkQuestionForm').submit();
        }
    }
</script>
@endsection
