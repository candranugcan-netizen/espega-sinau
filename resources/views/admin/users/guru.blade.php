@extends('layouts.admin')

@section('page_title', 'Manajemen Data Guru')

@section('content')
<div class="space-y-6">

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center justify-between shadow-sm animate-fadeIn">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                <span class="text-xs sm:text-sm font-semibold">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-center justify-between shadow-sm animate-fadeIn">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-circle-exclamation text-rose-600 text-lg"></i>
                <span class="text-xs sm:text-sm font-semibold">{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl space-y-1 shadow-sm">
            <div class="font-bold text-xs sm:text-sm flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Terdapat kesalahan pengisian data:</span>
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
                <h2 class="text-xl font-bold text-slate-900">Manajemen Tenaga Pendidik (Guru)</h2>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Kelola akun guru pengampu, penetapan mata pelajaran binaan, penugasan wali kelas, dan kredensial login terenkripsi aman.
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="openModalAddGuru()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors">
                <i class="fa-solid fa-user-plus text-xs"></i>
                <span>Tambah Guru Baru</span>
            </button>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Guru</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $totalGuru }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Pendidik terdaftar</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pengampu Mapel</div>
            <div class="text-2xl font-extrabold text-brand-600 mt-1">{{ $totalPengampu }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Memiliki mapel binaan</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Wali Kelas</div>
            <div class="text-2xl font-extrabold text-amber-600 mt-1">{{ $totalWaliKelas }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Penanggung jawab rombel</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Akun Aktif</div>
            <div class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $totalAktif }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Akses sistem normal</div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <form method="GET" action="{{ route('admin.users.guru') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-1">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama guru, NIP, username, atau email..." class="w-full pl-9 pr-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-xs font-semibold hover:bg-slate-900 transition-colors">
                    Cari
                </button>
                @if(request('q'))
                    <a href="{{ route('admin.users.guru') }}" class="px-3 py-2 bg-slate-100 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-200 transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Teachers Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase text-[11px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4">Nama Lengkap & NIP</th>
                        <th class="py-3.5 px-4">Username & Kontak</th>
                        <th class="py-3.5 px-4">Mata Pelajaran Binaan</th>
                        <th class="py-3.5 px-4">Tugas Tambahan</th>
                        <th class="py-3.5 px-4 text-center">Keamanan</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($teachers as $index => $t)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-4 text-center text-slate-400 font-mono">{{ $teachers->firstItem() + $index }}</td>
                        
                        <!-- Nama & NIP -->
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full {{ $t->isBlocked() ? 'bg-rose-500' : 'bg-emerald-500' }}"></span>
                                <span>{{ $t->nama_lengkap }}</span>
                            </div>
                            <div class="text-[11px] text-slate-500 font-mono mt-0.5">
                                NIP: {{ $t->nip ?: '-' }}
                            </div>
                        </td>

                        <!-- Username & Kontak -->
                        <td class="py-3.5 px-4">
                            <div class="font-mono font-bold text-slate-800 flex items-center gap-1">
                                <i class="fa-solid fa-user text-slate-400 text-[10px]"></i>
                                <span>{{ $t->username }}</span>
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                {{ $t->email ?: ($t->telepon ?: 'Tanpa kontak') }}
                            </div>
                        </td>

                        <!-- Mapel Binaan -->
                        <td class="py-3.5 px-4">
                            <div class="flex flex-wrap gap-1 max-w-xs">
                                @forelse($t->subjects as $s)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-blue-50 text-brand-700 border border-blue-200 text-[10px] font-semibold">
                                        {{ $s->kode_mapel ?? $s->nama_mapel }}
                                    </span>
                                @empty
                                    <span class="text-slate-400 italic text-[11px]">Belum diatur</span>
                                @endforelse
                            </div>
                            @if($t->bank_soals_count > 0)
                                <div class="text-[10px] text-slate-400 mt-1">
                                    <i class="fa-solid fa-book-open mr-0.5"></i> {{ $t->bank_soals_count }} Paket Bank Soal
                                </div>
                            @endif
                        </td>

                        <!-- Tugas Tambahan (Wali Kelas) -->
                        <td class="py-3.5 px-4">
                            @if($t->rombelWali && $t->rombelWali->count() > 0)
                                <div class="flex flex-wrap gap-1">
                                    @foreach($t->rombelWali as $rw)
                                        <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-bold">
                                            Wali {{ $rw->nama_rombel }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-slate-400 text-[11px]">-</span>
                            @endif
                        </td>

                        <!-- Keamanan & Password Ter-Hash -->
                        <td class="py-3.5 px-4 text-center">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-mono font-bold" title="Password dienkripsi satu arah dengan algoritma Bcrypt">
                                <i class="fa-solid fa-shield-halved text-[10px]"></i> Hashed
                            </span>
                            @if($t->token)
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5" title="Token Akses Darurat Proktor">
                                    Token: {{ $t->token }}
                                </div>
                            @endif
                        </td>

                        <!-- Status Akun -->
                        <td class="py-3.5 px-4 text-center">
                            @if($t->isBlocked())
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">
                                    Diblokir
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">
                                    Aktif
                                </span>
                            @endif
                        </td>

                        <!-- Aksi -->
                        <td class="py-3.5 px-4 text-right">
                            <div class="inline-flex items-center gap-1">
                                <!-- Tombol Edit -->
                                <button type="button" 
                                        onclick='openModalEditGuru(@json($t), @json($t->subjects->pluck("id")))'
                                        class="p-1.5 text-slate-600 hover:text-amber-600 hover:bg-slate-100 rounded-lg transition-colors" title="Edit Data Guru">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                <!-- Tombol Reset Token & Password -->
                                <form action="{{ route('admin.users.reset_token', $t) }}" method="POST" class="inline" onsubmit="return confirm('Reset akses token dan generate password baru untuk {{ $t->nama_lengkap }}?')">
                                    @csrf
                                    <button type="submit" class="p-1.5 text-slate-600 hover:text-brand-600 hover:bg-slate-100 rounded-lg transition-colors" title="Reset Token & Password">
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                </form>

                                <!-- Tombol Toggle Blokir -->
                                <form action="{{ route('admin.users.toggle_blokir', $t) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-1.5 text-slate-600 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors" title="{{ $t->isBlocked() ? 'Buka Blokir Akun' : 'Blokir Akun Sementara' }}">
                                        <i class="fa-solid {{ $t->isBlocked() ? 'fa-lock-open text-emerald-600' : 'fa-lock text-slate-400' }}"></i>
                                    </button>
                                </form>

                                <!-- Tombol Hapus Guru -->
                                <form action="{{ route('admin.users.destroy_guru', $t) }}" method="POST" class="inline" onsubmit="return confirm('Hapus akun guru {{ $t->nama_lengkap }}? Mata pelajaran dan rombel terkait akan dilepas secara aman.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-slate-100 rounded-lg transition-colors" title="Hapus Guru">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            <i class="fa-solid fa-chalkboard-user text-3xl mb-2 text-slate-300"></i>
                            <div class="text-sm font-semibold">Tidak ada data guru yang cocok.</div>
                            <div class="text-xs text-slate-400 mt-1">Gunakan tombol "Tambah Guru Baru" untuk mendaftarkan pendidik.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($teachers->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $teachers->links() }}
        </div>
        @endif
    </div>

</div>

<!-- Modal Tambah Guru Baru -->
<div id="modalAddGuru" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto flex items-center justify-center p-4">
    <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-8" onclick="event.stopPropagation()">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-brand-400"></i>
                <h3 class="font-bold text-sm">Tambah Tenaga Pendidik (Guru)</h3>
            </div>
            <button type="button" onclick="closeModalAddGuru()" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <form action="{{ route('admin.users.store_guru') }}" method="POST" class="p-6 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Lengkap Guru <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_lengkap" required placeholder="Contoh: Drs. Bambang Suryanto, M.Pd" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">NIP (Nomor Induk Pegawai)</label>
                    <input type="text" name="nip" placeholder="Contoh: 197805122005011003" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Username Login <span class="text-rose-500">*</span></label>
                    <input type="text" name="username" required placeholder="Contoh: guru.bambang" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 font-mono">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Digunakan saat masuk ke portal guru.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Password Awal (Opsional)</label>
                    <input type="password" name="password" placeholder="Kosongkan jika default (sama dg username)" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                    <span class="text-[10px] text-emerald-600 mt-0.5 block"><i class="fa-solid fa-lock mr-0.5"></i>Otomatis di-hash satu arah (Bcrypt).</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email Resmi (Opsional)</label>
                    <input type="email" name="email" placeholder="Contoh: bambang@smpn3ngawen.sch.id" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">No. WhatsApp / Telepon</label>
                    <input type="text" name="telepon" placeholder="Contoh: 081234567890" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Alamat Domisili</label>
                    <input type="text" name="alamat" placeholder="Contoh: Ngawen, Gunungkidul" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <!-- Pilihan Mata Pelajaran Binaan -->
            <div class="pt-2 border-t border-slate-100">
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tugaskan Mata Pelajaran yang Diampu</label>
                <p class="text-[11px] text-slate-500 mb-2">Pilih satu atau lebih mata pelajaran yang akan dipegang oleh guru ini.</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-36 overflow-y-auto p-2.5 bg-slate-50 rounded-xl border border-slate-200">
                    @forelse($allSubjects as $subj)
                        <label class="flex items-center gap-2 p-1.5 bg-white rounded-lg border border-slate-200 text-xs hover:border-brand-300 cursor-pointer">
                            <input type="checkbox" name="subject_ids[]" value="{{ $subj->id }}" class="rounded text-brand-600 focus:ring-brand-500">
                            <span class="truncate font-medium text-slate-800" title="{{ $subj->nama_mapel }}">{{ $subj->nama_mapel }}</span>
                        </label>
                    @empty
                        <div class="col-span-3 text-center text-xs text-slate-400 py-2">Belum ada mata pelajaran terdaftar.</div>
                    @endforelse
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
                <button type="button" onclick="closeModalAddGuru()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors">
                    Simpan Guru
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Guru -->
<div id="modalEditGuru" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto flex items-center justify-center p-4">
    <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-8" onclick="event.stopPropagation()">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-user-pen text-amber-400"></i>
                <h3 class="font-bold text-sm">Edit Data Guru</h3>
            </div>
            <button type="button" onclick="closeModalEditGuru()" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <form id="formEditGuru" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Lengkap Guru <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_nama_lengkap" name="nama_lengkap" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">NIP (Nomor Induk Pegawai)</label>
                    <input type="text" id="edit_nip" name="nip" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Username Login <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_username" name="username" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Ganti Password Baru</label>
                    <input type="password" id="edit_password" name="password" placeholder="Kosongkan jika password tidak diubah" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                    <span class="text-[10px] text-emerald-600 mt-0.5 block"><i class="fa-solid fa-lock mr-0.5"></i>Otomatis di-hash ke database (Bcrypt).</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Status Akun <span class="text-rose-500">*</span></label>
                    <select id="edit_status_blokir" name="status_blokir" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 bg-white">
                        <option value="0">Aktif (Dapat Login)</option>
                        <option value="1">Diblokir (Akses Dinonaktifkan)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email Resmi</label>
                    <input type="email" id="edit_email" name="email" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">No. WhatsApp / Telepon</label>
                    <input type="text" id="edit_telepon" name="telepon" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 font-mono">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Alamat Domisili</label>
                    <input type="text" id="edit_alamat" name="alamat" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <!-- Pilihan Mata Pelajaran Binaan -->
            <div class="pt-2 border-t border-slate-100">
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tugaskan Mata Pelajaran yang Diampu</label>
                <p class="text-[11px] text-slate-500 mb-2">Pilih satu atau lebih mata pelajaran yang akan dipegang oleh guru ini.</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-36 overflow-y-auto p-2.5 bg-slate-50 rounded-xl border border-slate-200">
                    @forelse($allSubjects as $subj)
                        <label class="flex items-center gap-2 p-1.5 bg-white rounded-lg border border-slate-200 text-xs hover:border-brand-300 cursor-pointer">
                            <input type="checkbox" name="subject_ids[]" value="{{ $subj->id }}" id="edit_subj_{{ $subj->id }}" class="rounded text-brand-600 focus:ring-brand-500">
                            <span class="truncate font-medium text-slate-800" title="{{ $subj->nama_mapel }}">{{ $subj->nama_mapel }}</span>
                        </label>
                    @empty
                        <div class="col-span-3 text-center text-xs text-slate-400 py-2">Belum ada mata pelajaran terdaftar.</div>
                    @endforelse
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
                <button type="button" onclick="closeModalEditGuru()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl transition-colors">
                    Perbarui Guru
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModalAddGuru() {
        document.getElementById('modalAddGuru').classList.remove('hidden');
    }

    function closeModalAddGuru() {
        document.getElementById('modalAddGuru').classList.add('hidden');
    }

    function openModalEditGuru(guru, assignedSubjectIds) {
        const form = document.getElementById('formEditGuru');
        form.action = `/admin/users/guru/${guru.id}`;

        document.getElementById('edit_nama_lengkap').value = guru.nama_lengkap || '';
        document.getElementById('edit_nip').value = guru.nip || '';
        document.getElementById('edit_username').value = guru.username || '';
        document.getElementById('edit_password').value = '';
        document.getElementById('edit_status_blokir').value = guru.status_blokir ?? '0';
        document.getElementById('edit_email').value = guru.email || '';
        document.getElementById('edit_telepon').value = guru.telepon || '';
        document.getElementById('edit_alamat').value = guru.alamat || '';

        // Reset semua checkbox subject
        document.querySelectorAll('#modalEditGuru input[name="subject_ids[]"]').forEach(cb => {
            cb.checked = false;
        });

        // Centang subject yang dimiliki
        if (Array.isArray(assignedSubjectIds)) {
            assignedSubjectIds.forEach(id => {
                const cb = document.getElementById('edit_subj_' + id);
                if (cb) cb.checked = true;
            });
        }

        document.getElementById('modalEditGuru').classList.remove('hidden');
    }

    function closeModalEditGuru() {
        document.getElementById('modalEditGuru').classList.add('hidden');
    }

    // Modal Close on backdrop & Escape key
    window.addEventListener('click', function(e) {
        const modalAdd = document.getElementById('modalAddGuru');
        const modalEdit = document.getElementById('modalEditGuru');
        if (e.target === modalAdd) closeModalAddGuru();
        if (e.target === modalEdit) closeModalEditGuru();
    });

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModalAddGuru();
            closeModalEditGuru();
        }
    });
</script>
@endsection
