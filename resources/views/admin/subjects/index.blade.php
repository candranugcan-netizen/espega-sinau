@extends('layouts.admin')

@section('page_title', 'Mata Pelajaran (Mapel)')

@section('content')
<div class="space-y-6">

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-circle-exclamation text-rose-600 text-lg"></i>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl space-y-1 shadow-sm">
            <div class="font-bold text-sm flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Terdapat kesalahan validasi:</span>
            </div>
            <ul class="list-disc list-inside text-xs space-y-0.5 ml-2">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="w-2.5 h-6 bg-brand-600 rounded-full"></span>
                <h2 class="text-xl font-bold text-slate-900">Kurikulum Mata Pelajaran</h2>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Kelola daftar mata pelajaran kurikulum merdeka/nasional, pengampu guru, KKM, serta keterhubungan ke bank soal dan ujian.
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="openAddModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors">
                <i class="fa-solid fa-plus"></i>
                <span>Tambah Mata Pelajaran</span>
            </button>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Mapel</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $totalMapel }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Mapel terdaftar</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Guru Pengampu</div>
            <div class="text-2xl font-extrabold text-brand-600 mt-1">{{ $totalGuruPengampu }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Pendidik aktif mengajar</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kelompok Umum (A)</div>
            <div class="text-2xl font-extrabold text-blue-700 mt-1">{{ \App\Models\Subject::where('kelompok', 'Kelompok A (Umum)')->count() }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Mapel Wajib Nasional</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kelompok B & Mulok</div>
            <div class="text-2xl font-extrabold text-amber-700 mt-1">{{ \App\Models\Subject::where('kelompok', '!=', 'Kelompok A (Umum)')->count() }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Keterampilan & Muatan Lokal</div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <form method="GET" action="{{ route('admin.subjects.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-1">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama mapel, kode, atau guru pengampu..." class="w-full pl-9 pr-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
            </div>
            <select name="kelompok" class="px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:border-brand-500 bg-white">
                <option value="">Semua Kelompok</option>
                <option value="Kelompok A (Umum)" {{ request('kelompok') == 'Kelompok A (Umum)' ? 'selected' : '' }}>Kelompok A (Umum)</option>
                <option value="Kelompok B" {{ request('kelompok') == 'Kelompok B' ? 'selected' : '' }}>Kelompok B</option>
                <option value="Muatan Lokal" {{ request('kelompok') == 'Muatan Lokal' ? 'selected' : '' }}>Muatan Lokal</option>
                <option value="Pilihan" {{ request('kelompok') == 'Pilihan' ? 'selected' : '' }}>Pilihan / Ekstrakurikuler</option>
            </select>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-xs font-semibold hover:bg-slate-900 transition-colors">
                    Filter
                </button>
                @if(request('q') || request('kelompok'))
                    <a href="{{ route('admin.subjects.index') }}" class="px-3 py-2 bg-slate-100 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-200 transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table of Subjects -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-bold text-[11px]">
                    <tr>
                        <th class="py-3.5 px-4">Kode</th>
                        <th class="py-3.5 px-4">Nama Mata Pelajaran</th>
                        <th class="py-3.5 px-4">Kelompok</th>
                        <th class="py-3.5 px-4">Guru Koordinator / Pengampu</th>
                        <th class="py-3.5 px-4 text-center">KKM</th>
                        <th class="py-3.5 px-4 text-center">Bank Soal & Soal</th>
                        <th class="py-3.5 px-4 text-center">Ujian Terkait</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($subjects as $subj)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-4 font-mono font-bold text-brand-600">
                                <span class="px-2 py-0.5 bg-brand-50 border border-brand-200 rounded text-xs">
                                    {{ $subj->kode_mapel ?? 'MP' . $subj->id }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 text-sm">{{ $subj->nama_mapel }}</div>
                                @if($subj->keterangan)
                                    <div class="text-[11px] text-slate-400 mt-0.5 truncate max-w-xs">{{ $subj->keterangan }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 bg-slate-100 rounded-full font-medium text-[11px] text-slate-700">
                                    {{ $subj->kelompok ?? 'Kelompok A (Umum)' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($subj->guru)
                                    <div class="font-semibold text-slate-900 flex items-center gap-1.5">
                                        <i class="fa-solid fa-chalkboard-user text-slate-400 text-xs"></i>
                                        <span>{{ $subj->guru->nama_lengkap }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400">{{ $subj->guru->username }}</div>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Belum ditentukan</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-block px-2 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 rounded-md font-bold text-xs">
                                    {{ $subj->kkm ?? 75 }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="font-bold text-slate-900">{{ $subj->bank_soals_count }} Paket</div>
                                <div class="text-[10px] text-slate-500">({{ $subj->questions_count }} Butir Soal)</div>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $subj->exams_count > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $subj->exams_count }} Ujian
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" 
                                            onclick='openEditModal(@json($subj))'
                                            class="p-1.5 text-slate-600 hover:text-amber-600 hover:bg-slate-100 rounded-lg transition-colors" title="Edit Mapel">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <form action="{{ route('admin.subjects.destroy', $subj) }}" method="POST" class="inline" onsubmit="return confirm('Hapus mata pelajaran {{ $subj->nama_mapel }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-slate-100 rounded-lg transition-colors" title="Hapus Mapel">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-book-open text-3xl mb-2 text-slate-300"></i>
                                <div class="text-sm">Belum ada mata pelajaran terdaftar.</div>
                                <div class="text-xs text-slate-400 mt-1">Klik tombol "Tambah Mata Pelajaran" untuk mendaftarkan baru.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($subjects->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $subjects->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Mapel -->
<div id="modalAdd" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-8">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-book-bookmark text-brand-400"></i>
                <h3 class="font-bold text-sm">Tambah Mata Pelajaran Baru</h3>
            </div>
            <button type="button" onclick="closeAddModal()" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>
        <form action="{{ route('admin.subjects.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Mata Pelajaran <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_mapel" required placeholder="Contoh: Matematika, Bahasa Indonesia, IPA" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kode Mapel</label>
                    <input type="text" name="kode_mapel" placeholder="Contoh: MTK, BIND" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 uppercase">
                    <p class="text-[10px] text-slate-400 mt-1">Kosongkan jika ingin dibuat otomatis.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">KKM / Ketuntasan</label>
                    <input type="number" name="kkm" min="0" max="100" value="75" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kelompok Mapel <span class="text-rose-500">*</span></label>
                <select name="kelompok" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 bg-white">
                    <option value="Kelompok A (Umum)">Kelompok A (Umum / Wajib)</option>
                    <option value="Kelompok B">Kelompok B (Seni, Olahraga, Prakarya)</option>
                    <option value="Muatan Lokal">Muatan Lokal (Bahasa Daerah, Budaya)</option>
                    <option value="Pilihan">Pilihan / Peminatan</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Guru Koordinator / Pengampu</label>
                <select name="guru_id" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 bg-white">
                    <option value="">-- Pilih Guru Pengampu (Opsional) --</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->nama_lengkap }} ({{ $teacher->username }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Keterangan</label>
                <textarea name="keterangan" rows="2" placeholder="Catatan tambahan mengenai mata pelajaran..." class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors">
                    Simpan Mapel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Mapel -->
<div id="modalEdit" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-8">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-amber-400"></i>
                <h3 class="font-bold text-sm">Edit Mata Pelajaran</h3>
            </div>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>
        <form id="formEdit" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Mata Pelajaran <span class="text-rose-500">*</span></label>
                <input type="text" id="edit_nama_mapel" name="nama_mapel" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kode Mapel</label>
                    <input type="text" id="edit_kode_mapel" name="kode_mapel" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 uppercase">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">KKM / Ketuntasan</label>
                    <input type="number" id="edit_kkm" name="kkm" min="0" max="100" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kelompok Mapel <span class="text-rose-500">*</span></label>
                <select id="edit_kelompok" name="kelompok" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 bg-white">
                    <option value="Kelompok A (Umum)">Kelompok A (Umum / Wajib)</option>
                    <option value="Kelompok B">Kelompok B (Seni, Olahraga, Prakarya)</option>
                    <option value="Muatan Lokal">Muatan Lokal (Bahasa Daerah, Budaya)</option>
                    <option value="Pilihan">Pilihan / Peminatan</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Guru Koordinator / Pengampu</label>
                <select id="edit_guru_id" name="guru_id" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 bg-white">
                    <option value="">-- Pilih Guru Pengampu (Opsional) --</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->nama_lengkap }} ({{ $teacher->username }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Keterangan</label>
                <textarea id="edit_keterangan" name="keterangan" rows="2" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl transition-colors">
                    Perbarui Mapel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalAdd').classList.remove('hidden');
    }
    function closeAddModal() {
        document.getElementById('modalAdd').classList.add('hidden');
    }

    function openEditModal(subj) {
        const form = document.getElementById('formEdit');
        form.action = `/admin/subjects/${subj.id}`;
        document.getElementById('edit_nama_mapel').value = subj.nama_mapel || '';
        document.getElementById('edit_kode_mapel').value = subj.kode_mapel || '';
        document.getElementById('edit_kkm').value = subj.kkm || '75';
        document.getElementById('edit_kelompok').value = subj.kelompok || 'Kelompok A (Umum)';
        document.getElementById('edit_guru_id').value = subj.guru_id || '';
        document.getElementById('edit_keterangan').value = subj.keterangan || '';
        document.getElementById('modalEdit').classList.remove('hidden');
    }
    function closeEditModal() {
        document.getElementById('modalEdit').classList.add('hidden');
    }

    // Modal Close on backdrop & Escape key
    window.addEventListener('click', function(e) {
        const modalAdd = document.getElementById('modalAdd');
        const modalEdit = document.getElementById('modalEdit');
        if (e.target === modalAdd) closeAddModal();
        if (e.target === modalEdit) closeEditModal();
    });

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAddModal();
            closeEditModal();
        }
    });
</script>
@endsection

