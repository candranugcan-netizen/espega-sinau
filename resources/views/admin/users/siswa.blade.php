@extends('layouts.admin')

@section('page_title', 'Manajemen Data Siswa & Alumni')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Data Peserta Didik & Kesiswaan</h2>
            <p class="text-xs text-slate-500 mt-1">Daftar akun siswa, rombel kelas, import/export data, cetak kartu, dan token akses ujian.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Cetak Kartu PDF -->
            <a href="{{ route('admin.users.siswa.cetak_kartu', ['kelas' => request('kelas'), 'q' => request('q')]) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-sm transition-colors">
                <i class="fa-solid fa-print text-xs"></i>
                <span>Cetak Kartu {{ request('kelas') ? '('.request('kelas').')' : '(Semua)' }}</span>
            </a>

            <!-- Export Excel -->
            <a href="{{ route('admin.users.siswa.export', ['kelas' => request('kelas')]) }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition-colors">
                <i class="fa-solid fa-file-excel text-xs"></i>
                <span>Export Excel</span>
            </a>

            <!-- Import Excel -->
            <button type="button" onclick="openModal('importSiswaModal')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs shadow-sm transition-colors">
                <i class="fa-solid fa-file-import text-xs"></i>
                <span>Import Excel</span>
            </button>

            <!-- Tambah Siswa Manual -->
            <button type="button" onclick="openModal('addSiswaModal')" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-sm transition-colors">
                <i class="fa-solid fa-user-plus text-xs"></i>
                <span>Tambah Siswa</span>
            </button>
        </div>
    </div>

    <!-- Tab Status Siswa (Siswa Aktif vs Buku Induk Alumni) -->
    <div class="flex border-b border-slate-200">
        <a href="{{ route('admin.users.siswa', array_merge(request()->except(['page', 'status_siswa']), ['status_siswa' => 'aktif'])) }}" class="py-3 px-5 text-xs font-bold border-b-2 flex items-center gap-2 transition {{ ($statusSiswa ?? 'aktif') === 'aktif' ? 'border-brand-600 text-brand-600 font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <i class="fa-solid fa-user-graduate"></i>
            <span>Siswa Aktif ({{ number_format($totalAktif ?? 0) }})</span>
        </a>
        <a href="{{ route('admin.users.siswa', array_merge(request()->except(['page', 'status_siswa']), ['status_siswa' => 'alumni'])) }}" class="py-3 px-5 text-xs font-bold border-b-2 flex items-center gap-2 transition {{ ($statusSiswa ?? '') === 'alumni' ? 'border-brand-600 text-brand-600 font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <i class="fa-solid fa-graduation-cap"></i>
            <span>Buku Induk Alumni ({{ number_format($totalAlumni ?? 0) }})</span>
        </a>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.users.siswa') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
            <input type="hidden" name="status_siswa" value="{{ $statusSiswa ?? 'aktif' }}">

            <div>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / NIS / NISN..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <div>
                <select name="kelas" onchange="this.form.submit()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">Semua Rombel Kelas</option>
                    @foreach($classesList as $k)
                        <option value="{{ $k }}" {{ request('kelas') == $k ? 'selected' : '' }}>Kelas {{ $k }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="academic_year_id" onchange="this.form.submit()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">Semua Tahun Ajaran</option>
                    @foreach($academicYears ?? [] as $ay)
                        <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>
                            TA: {{ $ay->full_label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="status_blokir" onchange="this.form.submit()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">Semua Status Akun</option>
                    <option value="0" {{ request('status_blokir') === '0' ? 'selected' : '' }}>Aktif (Normal)</option>
                    <option value="1" {{ request('status_blokir') === '1' ? 'selected' : '' }}>Terblokir Ujian</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold">Filter</button>
                @if(request()->hasAny(['q', 'kelas', 'academic_year_id', 'status_blokir']))
                    <a href="{{ route('admin.users.siswa', ['status_siswa' => $statusSiswa ?? 'aktif']) }}" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-500 text-xs font-bold">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Bulk Action Bar -->
    <form id="bulkForm" action="{{ route('admin.users.siswa.bulk_action') }}" method="POST">
        @csrf
        <input type="hidden" name="action_type" id="bulkActionType" value="">

        <div id="bulkActionBar" class="hidden mb-4 p-3 bg-brand-50 border border-brand-200 rounded-2xl flex flex-wrap items-center justify-between gap-3 shadow-sm transition-all animate-fadeIn">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-600 text-white text-xs font-bold" id="selectedCount">0</span>
                <span class="text-xs font-bold text-slate-800">Siswa terpilih untuk aksi massal:</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="submitBulk('reset_token')" class="px-3 py-1.5 rounded-xl bg-white border border-brand-200 text-brand-700 hover:bg-brand-100 font-bold text-xs transition">
                    <i class="fa-solid fa-arrows-rotate mr-1"></i> Reset Token
                </button>
                <button type="button" onclick="submitBulk('luluskan')" class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-xs transition shadow-sm">
                    <i class="fa-solid fa-graduation-cap mr-1"></i> Luluskan Menjadi Alumni
                </button>
                <button type="button" onclick="submitBulk('blokir')" class="px-3 py-1.5 rounded-xl bg-white border border-rose-200 text-rose-700 hover:bg-rose-50 font-bold text-xs transition">
                    <i class="fa-solid fa-lock mr-1"></i> Blokir
                </button>
                <button type="button" onclick="submitBulk('buka_blokir')" class="px-3 py-1.5 rounded-xl bg-white border border-emerald-200 text-emerald-700 hover:bg-emerald-50 font-bold text-xs transition">
                    <i class="fa-solid fa-lock-open mr-1"></i> Buka Blokir
                </button>
                <button type="button" onclick="submitBulk('delete')" class="px-3 py-1.5 rounded-xl bg-rose-600 text-white hover:bg-rose-700 font-bold text-xs transition">
                    <i class="fa-solid fa-trash-can mr-1"></i> Hapus
                </button>
            </div>
        </div>

        <!-- Students Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4 w-10 text-center">
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                            </th>
                            <th class="py-3.5 px-3 w-12 text-center">No</th>
                            <th class="py-3.5 px-4">Nama Lengkap Siswa</th>
                            <th class="py-3.5 px-4">NISN / NIS</th>
                            <th class="py-3.5 px-4 text-center">L/P</th>
                            <th class="py-3.5 px-4 text-center">Rombel</th>
                            <th class="py-3.5 px-4 text-center">Token Ujian</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($users as $index => $u)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4 text-center">
                                <input type="checkbox" name="selected_ids[]" value="{{ $u->id }}" onchange="updateSelectedCount()" class="student-checkbox w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                            </td>
                            <td class="py-3 px-3 text-center text-slate-400 font-mono">{{ $users->firstItem() + $index }}</td>
                            <td class="py-3 px-4 font-bold text-slate-900">
                                <div class="text-sm">{{ $u->nama_lengkap }}</div>
                                @if($u->academicYear)
                                    <div class="text-[10px] text-slate-400 font-normal">TA: {{ $u->academicYear->full_label }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-600">
                                <div>{{ $u->nisn ?: '-' }}</div>
                                <div class="text-[10px] text-slate-400">NIS: {{ $u->username }}</div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($u->jenis_kelamin === 'L')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">L</span>
                                @elseif($u->jenis_kelamin === 'P')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-pink-50 text-pink-700 border border-pink-200">P</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($u->isAlumni())
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        Alumni
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-blue-50 text-brand-700 border border-blue-200">
                                        {{ $u->kelas }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200 font-mono font-bold text-brand-700">
                                    <span>{{ $u->token }}</span>
                                    <button type="button" onclick="event.preventDefault(); document.getElementById('reset-token-{{ $u->id }}').submit();" title="Reset Token Akses" class="text-slate-400 hover:text-brand-600">
                                        <i class="fa-solid fa-arrows-rotate text-[10px]"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($u->status_blokir === '1')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 animate-pulse">
                                        Terblokir
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Edit Siswa Modal Trigger -->
                                    <button type="button" onclick="openEditModal({{ $u->id }}, '{{ addslashes($u->nama_lengkap) }}', '{{ addslashes($u->username) }}', '{{ addslashes($u->kelas) }}', '{{ addslashes($u->nisn ?? '') }}', '{{ addslashes($u->nis ?? '') }}', '{{ $u->jenis_kelamin ?? '' }}', '{{ addslashes($u->telepon ?? '') }}', '{{ addslashes($u->alamat ?? '') }}', '{{ $u->status_siswa ?? 'aktif' }}')" class="p-1.5 rounded-lg text-slate-400 hover:text-brand-600 hover:bg-slate-100" title="Edit Data">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <a href="{{ route('admin.student_administration.show', $u) }}" class="p-1.5 rounded-lg text-slate-400 hover:text-brand-600 hover:bg-slate-100" title="Administrasi Siswa">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </a>

                                    <!-- Toggle Blokir -->
                                    <button type="button" onclick="event.preventDefault(); document.getElementById('toggle-blokir-{{ $u->id }}').submit();" class="p-1.5 rounded-lg {{ $u->status_blokir === '1' ? 'text-rose-600 bg-rose-50' : 'text-slate-400 hover:text-rose-600 hover:bg-slate-100' }}" title="{{ $u->status_blokir === '1' ? 'Buka Blokir' : 'Blokir Akun' }}">
                                        <i class="fa-solid {{ $u->status_blokir === '1' ? 'fa-lock-open' : 'fa-lock' }}"></i>
                                    </button>

                                    <!-- Hapus Siswa -->
                                    <button type="button" onclick="event.preventDefault(); if(confirm('Hapus data siswa {{ addslashes($u->nama_lengkap) }}?')) document.getElementById('delete-siswa-{{ $u->id }}').submit();" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-slate-100" title="Hapus">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">Tidak ada data siswa yang cocok dengan filter.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $users->links() }}
            </div>
            @endif
        </div>
    </form>

    <!-- Hidden Individual Forms for Actions -->
    @foreach($users as $u)
        <form id="reset-token-{{ $u->id }}" action="{{ route('admin.users.reset_token', $u) }}" method="POST" class="hidden">@csrf</form>
        <form id="toggle-blokir-{{ $u->id }}" action="{{ route('admin.users.toggle_blokir', $u) }}" method="POST" class="hidden">@csrf</form>
        <form id="delete-siswa-{{ $u->id }}" action="{{ route('admin.users.destroy', $u) }}" method="POST" class="hidden">@csrf @method('DELETE')</form>
    @endforeach

</div>

<!-- Modal Import Siswa Excel -->
<div id="importSiswaModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-5 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Import Data Siswa dari Excel</h3>
                <p class="text-xs text-slate-500 mt-0.5">Unggah berkas spreadsheet .xlsx atau .csv</p>
            </div>
            <button type="button" onclick="closeModal('importSiswaModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup Modal"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form action="{{ route('admin.users.siswa.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
            @csrf

            <!-- Panduan Format Kolom -->
            <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-900">
                <div class="font-bold mb-1 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-info"></i> Susunan Kolom File Excel:
                </div>
                <ol class="list-decimal list-inside space-y-0.5 text-[11px] text-amber-800">
                    <li>Kolom A : <b>NIS / Username</b> (Wajib diisi, misal: 2026001)</li>
                    <li>Kolom B : <b>Nama Lengkap Siswa</b> (Wajib diisi)</li>
                    <li>Kolom C : <b>Rombel Kelas</b> (Contoh: 7A, 8B, 9C)</li>
                </ol>
            </div>

            <!-- Upload Area -->
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Pilih Berkas Excel / CSV</label>
                <input type="file" name="file_excel" required accept=".xlsx,.xls,.csv" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('admin.users.siswa.template') }}" class="inline-flex items-center gap-1.5 text-brand-600 hover:text-brand-800 font-bold text-xs">
                    <i class="fa-solid fa-download"></i> Unduh Contoh Template Excel
                </a>
                <div class="flex gap-2">
                    <button type="button" onclick="closeModal('importSiswaModal')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold">Mulai Import</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Siswa Manual -->
<div id="addSiswaModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-5 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Tambah Siswa Baru</h3>
                <p class="text-xs text-slate-500 mt-0.5">Input data pokok siswa ke dalam pangkalan data sekolah</p>
            </div>
            <button type="button" onclick="closeModal('addSiswaModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup Modal"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form action="{{ route('admin.users.store_siswa') }}" method="POST" class="space-y-3.5 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Siswa <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_lengkap" required placeholder="Nama Siswa Lengkap Sesuai Akta" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Username / NIS <span class="text-rose-500">*</span></label>
                    <input type="text" name="username" required placeholder="Contoh: 3240" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Rombel Kelas <span class="text-rose-500">*</span></label>
                    <input type="text" name="kelas" required placeholder="Contoh: 7A, 8B" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">NISN (Nomor Induk Siswa Nasional)</label>
                    <input type="text" name="nisn" placeholder="10 digit NISN" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <option value="">-- Pilih L/P --</option>
                        <option value="L">Laki-Laki (L)</option>
                        <option value="P">Perempuan (P)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">No. Telepon / WhatsApp</label>
                    <input type="text" name="telepon" placeholder="08xxxxxxxxxx" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Token Akses (Opsional)</label>
                    <input type="text" name="token" placeholder="Generate acak jika kosong" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Alamat Tinggal</label>
                <textarea name="alamat" rows="2" placeholder="Dusun, Desa/Kelurahan, RT/RW" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"></textarea>
            </div>

            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('addSiswaModal')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold">Simpan Data Siswa</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Siswa -->
<div id="editSiswaModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-5 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Edit Data Siswa</h3>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui profil dan identitas peserta didik</p>
            </div>
            <button type="button" onclick="closeModal('editSiswaModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup Modal"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form id="editSiswaForm" method="POST" class="space-y-3.5 text-xs">
            @csrf
            @method('PUT')
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Siswa <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_lengkap" id="edit_nama" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Username / NIS <span class="text-rose-500">*</span></label>
                    <input type="text" name="username" id="edit_username" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Rombel Kelas <span class="text-rose-500">*</span></label>
                    <input type="text" name="kelas" id="edit_kelas" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">NISN</label>
                    <input type="text" name="nisn" id="edit_nisn" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="edit_jenis_kelamin" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <option value="">-- Pilih --</option>
                        <option value="L">Laki-Laki (L)</option>
                        <option value="P">Perempuan (P)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Status Kesiswaan</label>
                    <select name="status_siswa" id="edit_status_siswa" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <option value="aktif">Siswa Aktif</option>
                        <option value="alumni">Alumni / Lulusan</option>
                        <option value="mutasi">Mutasi Keluar</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">No. Telepon / WA</label>
                    <input type="text" name="telepon" id="edit_telepon" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Alamat Tinggal</label>
                <textarea name="alamat" id="edit_alamat" rows="2" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"></textarea>
            </div>

            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('editSiswaModal')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) { 
        const m = document.getElementById(id);
        if (m) {
            m.classList.remove('hidden'); 
            m.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(id) { 
        const m = document.getElementById(id);
        if (m) {
            m.classList.add('hidden'); 
            m.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }
    }

    function openEditModal(id, name, username, kelas, nisn, nis, jk, telp, alamat, status) {
        document.getElementById('editSiswaForm').action = "/admin/users/siswa/" + id;
        document.getElementById('edit_nama').value = name;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_kelas').value = kelas;
        document.getElementById('edit_nisn').value = nisn || '';
        document.getElementById('edit_jenis_kelamin').value = jk || '';
        document.getElementById('edit_telepon').value = telp || '';
        document.getElementById('edit_alamat').value = alamat || '';
        document.getElementById('edit_status_siswa').value = status || 'aktif';
        openModal('editSiswaModal');
    }

    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('.student-checkbox');
        checkboxes.forEach(cb => cb.checked = master.checked);
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const selected = document.querySelectorAll('.student-checkbox:checked');
        const count = selected.length;
        document.getElementById('selectedCount').textContent = count;
        const bar = document.getElementById('bulkActionBar');
        if (count > 0) {
            bar.classList.remove('hidden');
        } else {
            bar.classList.add('hidden');
            const master = document.getElementById('selectAllCheckbox');
            if (master) master.checked = false;
        }
    }

    function submitBulk(action) {
        const selected = document.querySelectorAll('.student-checkbox:checked');
        if (selected.length === 0) {
            alert('Pilih minimal satu siswa!');
            return;
        }

        let confirmMsg = 'Lanjutkan aksi massal ini?';
        if (action === 'delete') {
            confirmMsg = `PERINGATAN: Anda yakin ingin MENGHAPUS ${selected.length} data siswa terpilih secara permanen?`;
        } else if (action === 'reset_token') {
            confirmMsg = `Reset token akses untuk ${selected.length} siswa terpilih?`;
        } else if (action === 'blokir') {
            confirmMsg = `Blokir akses ujian untuk ${selected.length} siswa terpilih?`;
        } else if (action === 'buka_blokir') {
            confirmMsg = `Buka blokir akses untuk ${selected.length} siswa terpilih?`;
        } else if (action === 'luluskan') {
            confirmMsg = `Luluskan ${selected.length} siswa terpilih menjadi ALUMNI dan simpan ke Buku Induk Alumni?`;
        }

        if (confirm(confirmMsg)) {
            document.getElementById('bulkActionType').value = action;
            document.getElementById('bulkForm').submit();
        }
    }

    window.addEventListener('click', function(e) {
        ['importSiswaModal', 'addSiswaModal', 'editSiswaModal'].forEach(id => {
            const m = document.getElementById(id);
            if (m && e.target === m) {
                closeModal(id);
            }
        });
    });

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            ['importSiswaModal', 'addSiswaModal', 'editSiswaModal'].forEach(id => closeModal(id));
        }
    });
</script>
@endsection
