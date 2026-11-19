@extends('layouts.admin')

@section('page_title', 'Daftar Rombel / Kelas')

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
                <h2 class="text-xl font-bold text-slate-900">Rombongan Belajar (Rombel)</h2>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Kelola data kelas, penetapan wali kelas, ruang belajar, dan koneksi langsung dengan peserta didik serta ujian.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <form action="{{ route('admin.rombels.sync') }}" method="POST" onsubmit="return confirm('Sinkronkan rombel dari seluruh data kelas siswa yang ada saat ini?')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold border border-slate-300 transition-colors" title="Ambil rombel unik otomatis dari data siswa">
                    <i class="fa-solid fa-arrows-rotate text-slate-600"></i>
                    <span>Sinkron Dari Siswa</span>
                </button>
            </form>
            <button type="button" onclick="openAddModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors">
                <i class="fa-solid fa-plus"></i>
                <span>Tambah Rombel</span>
            </button>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Rombel</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $totalRombel }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Kelas terdaftar</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Siswa</div>
            <div class="text-2xl font-extrabold text-brand-600 mt-1">{{ $totalSiswaRombel }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Siswa aktif di rombel</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tingkat 7</div>
            <div class="text-2xl font-extrabold text-blue-700 mt-1">{{ $rombelTingkat7 }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Rombel Kelas VII</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tingkat 8</div>
            <div class="text-2xl font-extrabold text-indigo-700 mt-1">{{ $rombelTingkat8 }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Rombel Kelas VIII</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm col-span-2 md:col-span-1">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tingkat 9</div>
            <div class="text-2xl font-extrabold text-amber-700 mt-1">{{ $rombelTingkat9 }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Rombel Kelas IX</div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <form method="GET" action="{{ route('admin.rombels.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-1">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama rombel, ruang, wali kelas..." class="w-full pl-9 pr-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
            </div>
            <select name="tingkat" class="px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:border-brand-500 bg-white">
                <option value="">Semua Tingkat</option>
                <option value="7" {{ request('tingkat') == '7' ? 'selected' : '' }}>Tingkat VII (7)</option>
                <option value="8" {{ request('tingkat') == '8' ? 'selected' : '' }}>Tingkat VIII (8)</option>
                <option value="9" {{ request('tingkat') == '9' ? 'selected' : '' }}>Tingkat IX (9)</option>
            </select>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-xs font-semibold hover:bg-slate-900 transition-colors">
                    Filter
                </button>
                @if(request('q') || request('tingkat'))
                    <a href="{{ route('admin.rombels.index') }}" class="px-3 py-2 bg-slate-100 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-200 transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table of Rombel -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-bold text-[11px]">
                    <tr>
                        <th class="py-3.5 px-4">Nama Rombel</th>
                        <th class="py-3.5 px-4 text-center">Tingkat</th>
                        <th class="py-3.5 px-4">Wali Kelas</th>
                        <th class="py-3.5 px-4">Ruang</th>
                        <th class="py-3.5 px-4 text-center">Jumlah Siswa</th>
                        <th class="py-3.5 px-4 text-center">Ujian Terhubung</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($rombels as $rombel)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-4">
                                <a href="{{ route('admin.rombels.show', $rombel) }}" class="font-bold text-slate-900 hover:text-brand-600 text-sm flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                                    {{ $rombel->nama_rombel }}
                                </a>
                                @if($rombel->keterangan)
                                    <div class="text-[11px] text-slate-400 mt-0.5 truncate max-w-xs">{{ $rombel->keterangan }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold 
                                    {{ $rombel->tingkat == '7' ? 'bg-blue-100 text-blue-800' : ($rombel->tingkat == '8' ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800') }}">
                                    Kelas {{ $rombel->tingkat }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($rombel->waliKelas)
                                    <div class="font-semibold text-slate-900 flex items-center gap-1.5">
                                        <i class="fa-solid fa-user-tie text-slate-400 text-xs"></i>
                                        <span>{{ $rombel->waliKelas->nama_lengkap }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400">NIP: {{ $rombel->waliKelas->nip ?? '-' }}</div>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Belum ditentukan</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-medium text-slate-700">{{ $rombel->ruang ?? '-' }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="font-bold text-slate-900 text-sm">{{ $rombel->students_count }}</div>
                                <div class="text-[10px] text-slate-500">
                                    L: {{ $rombel->jumlah_laki }} | P: {{ $rombel->jumlah_perempuan }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @php $examCount = $rombel->exams->count(); @endphp
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold {{ $examCount > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                    <i class="fa-solid fa-file-signature text-[10px]"></i>
                                    {{ $examCount }} Ujian
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ route('admin.rombels.show', $rombel) }}" class="p-1.5 text-slate-600 hover:text-brand-600 hover:bg-slate-100 rounded-lg transition-colors" title="Lihat Detail & Anggota">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <button type="button" 
                                            onclick='openEditModal(@json($rombel))'
                                            class="p-1.5 text-slate-600 hover:text-amber-600 hover:bg-slate-100 rounded-lg transition-colors" title="Edit Rombel">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <form action="{{ route('admin.rombels.destroy', $rombel) }}" method="POST" class="inline" onsubmit="return confirm('Hapus rombel {{ $rombel->nama_rombel }}? Siswa tidak akan terhapus, namun relasi rombel akan dicabut.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-slate-100 rounded-lg transition-colors" title="Hapus Rombel">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-chalkboard text-3xl mb-2 text-slate-300"></i>
                                <div class="text-sm">Belum ada rombongan belajar terdaftar.</div>
                                <div class="text-xs text-slate-400 mt-1">Gunakan tombol "Sinkron Dari Siswa" atau "Tambah Rombel".</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rombels->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $rombels->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Rombel -->
<div id="modalAdd" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-8">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-chalkboard text-brand-400"></i>
                <h3 class="font-bold text-sm">Tambah Rombel Baru</h3>
            </div>
            <button type="button" onclick="closeAddModal()" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>
        <form action="{{ route('admin.rombels.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Rombel <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_rombel" required placeholder="Contoh: 7A, 8B, 9C" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 uppercase">
                <p class="text-[11px] text-slate-400 mt-1">Format singkatan kelas sesuai data siswa.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tingkat <span class="text-rose-500">*</span></label>
                    <select name="tingkat" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 bg-white">
                        <option value="7">Tingkat 7 (VII)</option>
                        <option value="8">Tingkat 8 (VIII)</option>
                        <option value="9">Tingkat 9 (IX)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Ruang Kelas</label>
                    <input type="text" name="ruang" placeholder="Contoh: R-01, Lab IPA" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Wali Kelas</label>
                <select name="wali_kelas_id" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 bg-white">
                    <option value="">-- Pilih Wali Kelas (Opsional) --</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->nama_lengkap }} ({{ $teacher->username }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Keterangan / Catatan</label>
                <textarea name="keterangan" rows="2" placeholder="Catatan tambahan mengenai rombel ini..." class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors">
                    Simpan Rombel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Rombel -->
<div id="modalEdit" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-8">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-amber-400"></i>
                <h3 class="font-bold text-sm">Edit Rombel</h3>
            </div>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>
        <form id="formEdit" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Rombel <span class="text-rose-500">*</span></label>
                <input type="text" id="edit_nama_rombel" name="nama_rombel" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 uppercase">
                <p class="text-[11px] text-amber-600 mt-1 font-medium"><i class="fa-solid fa-circle-info mr-1"></i>Jika nama rombel diubah, data kelas siswa terkait akan otomatis diperbarui.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tingkat <span class="text-rose-500">*</span></label>
                    <select id="edit_tingkat" name="tingkat" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 bg-white">
                        <option value="7">Tingkat 7 (VII)</option>
                        <option value="8">Tingkat 8 (VIII)</option>
                        <option value="9">Tingkat 9 (IX)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Ruang Kelas</label>
                    <input type="text" id="edit_ruang" name="ruang" placeholder="Contoh: R-01" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Wali Kelas</label>
                <select id="edit_wali_kelas_id" name="wali_kelas_id" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 bg-white">
                    <option value="">-- Pilih Wali Kelas (Opsional) --</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->nama_lengkap }} ({{ $teacher->username }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Keterangan / Catatan</label>
                <textarea id="edit_keterangan" name="keterangan" rows="2" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl transition-colors">
                    Perbarui Rombel
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

    function openEditModal(rombel) {
        const form = document.getElementById('formEdit');
        form.action = `/admin/rombels/${rombel.id}`;
        document.getElementById('edit_nama_rombel').value = rombel.nama_rombel || '';
        document.getElementById('edit_tingkat').value = rombel.tingkat || '7';
        document.getElementById('edit_ruang').value = rombel.ruang || '';
        document.getElementById('edit_wali_kelas_id').value = rombel.wali_kelas_id || '';
        document.getElementById('edit_keterangan').value = rombel.keterangan || '';
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

