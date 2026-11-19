@extends('layouts.admin')

@section('page_title', 'Manajemen Tahun Ajaran & SIAKAD')

@section('content')
<div class="space-y-6">

    <!-- Active Academic Year Banner -->
    <div class="bg-slate-900 text-white rounded-2xl p-6 sm:p-7 border border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-500/20 text-brand-300 border border-brand-400/30 text-xs font-bold uppercase tracking-wider">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Tahun Ajaran Berjalan
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                {{ $activeYear ? $activeYear->full_label : 'Belum Ditentukan' }}
            </h2>
            <p class="text-xs sm:text-sm text-slate-300 max-w-2xl">
                Seluruh aktivitas akademik seperti bank soal, jadwal ujian CBT, dan status rombel siswa saat ini bertumpu pada tahun ajaran ini. Siswa yang lulus di periode ini akan otomatis tercatat di Buku Induk Alumni.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <!-- Modal Wizard Kenaikan Kelas -->
            <button type="button" onclick="openModal('wizardKenaikanModal')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-xs shadow-sm transition-colors">
                <i class="fa-solid fa-graduation-cap text-sm"></i>
                <span>Proses Kenaikan & Kelulusan</span>
            </button>

            <!-- Tambah Tahun Ajaran -->
            <button type="button" onclick="openModal('addAYModal')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-extrabold text-xs shadow-sm transition-colors">
                <i class="fa-solid fa-plus text-sm"></i>
                <span>Tambah Tahun Ajaran</span>
            </button>
        </div>
    </div>

    <!-- Quick SIAKAD Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Siswa Aktif -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Siswa Aktif Berjalan</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-brand-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-extrabold text-slate-900">{{ number_format($totalSiswaAktif) }}</span>
                <span class="text-xs text-slate-500 font-medium ml-1">Siswa</span>
            </div>
            <div class="mt-2 text-[11px] text-slate-400 font-medium">
                Kelas 7: <b>{{ $siswaKelas7 }}</b> • Kelas 8: <b>{{ $siswaKelas8 }}</b> • Kelas 9: <b>{{ $siswaKelas9 }}</b>
            </div>
        </div>

        <!-- Total Alumni -->
        <a href="{{ route('admin.alumni.index') }}" class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:border-brand-300 transition-colors block group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider group-hover:text-brand-600">Buku Induk Alumni</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-award"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-extrabold text-slate-900">{{ number_format($totalAlumni) }}</span>
                <span class="text-xs text-slate-500 font-medium ml-1">Lulusan</span>
            </div>
            <div class="mt-2 text-[11px] text-brand-600 font-bold flex items-center gap-1">
                <span>Lihat Data Alumni Lengkap</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </div>
        </a>

        <!-- Total Ujian TA Ini -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ujian CBT TA Berjalan</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-extrabold text-slate-900">{{ number_format($totalUjianTahunIni) }}</span>
                <span class="text-xs text-slate-500 font-medium ml-1">Jadwal Ujian</span>
            </div>
            <div class="mt-2 text-[11px] text-slate-400 font-medium">
                Terhubung ke tahun ajaran aktif
            </div>
        </div>

        <!-- Total Bank Soal TA Ini -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Paket Bank Soal</span>
                <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-book-open"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-extrabold text-slate-900">{{ number_format($totalBankSoalTahunIni) }}</span>
                <span class="text-xs text-slate-500 font-medium ml-1">Paket Soal</span>
            </div>
            <div class="mt-2 text-[11px] text-slate-400 font-medium">
                Tersedia dari bapak/ibu guru
            </div>
        </div>
    </div>

    <!-- Academic Years Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="font-extrabold text-slate-900 text-base">Daftar Periode Tahun Ajaran</h3>
                <p class="text-xs text-slate-500 mt-0.5">Kelola riwayat tahun ajaran, semester ganjil/genap, dan aktifkan periode berjalan.</p>
            </div>
            <div class="text-xs text-slate-500 font-semibold">
                Total: <b>{{ $academicYears->count() }}</b> Periode
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">No</th>
                        <th class="py-3.5 px-4">Tahun Ajaran</th>
                        <th class="py-3.5 px-4 text-center">Semester</th>
                        <th class="py-3.5 px-4 text-center">Status Periode</th>
                        <th class="py-3.5 px-4 text-center">Rentang Tanggal</th>
                        <th class="py-3.5 px-4 text-center">Aktivitas Terkait</th>
                        <th class="py-3.5 px-4">Keterangan</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($academicYears as $idx => $ay)
                    <tr class="hover:bg-slate-50/80 transition-colors {{ $ay->is_active ? 'bg-blue-50/30' : '' }}">
                        <td class="py-3.5 px-4 text-center text-slate-400 font-mono">{{ $idx + 1 }}</td>
                        <td class="py-3.5 px-4 font-bold text-slate-900 text-sm">
                            {{ $ay->nama }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if(strtolower($ay->semester) === 'ganjil')
                                <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    Ganjil (1)
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                    Genap (2)
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($ay->is_active)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Aktif Berjalan
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                    Arsip
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center text-slate-600 text-[11px]">
                            @if($ay->tanggal_mulai && $ay->tanggal_selesai)
                                {{ $ay->tanggal_mulai->format('d/m/Y') }} - {{ $ay->tanggal_selesai->format('d/m/Y') }}
                            @else
                                <span class="text-slate-400 italic">Belum diatur</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="inline-flex items-center gap-2 text-[11px] text-slate-600">
                                <span title="Ujian">{{ $ay->exams_count }} Ujian</span> •
                                <span title="Bank Soal">{{ $ay->bank_soals_count }} Bank</span>
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-slate-500 text-[11px] max-w-xs truncate" title="{{ $ay->keterangan }}">
                            {{ $ay->keterangan ?: '-' }}
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                @if(!$ay->is_active)
                                    <!-- Jadikan Aktif -->
                                    <form action="{{ route('admin.academic_years.set_active', $ay) }}" method="POST" onsubmit="return confirm('Aktifkan Tahun Ajaran {{ $ay->full_label }} sebagai periode berjalan saat ini?')">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-brand-50 text-brand-700 border border-brand-200 hover:bg-brand-600 hover:text-white font-bold text-[11px] transition">
                                            Aktifkan
                                        </button>
                                    </form>
                                @endif

                                <!-- Edit -->
                                <button type="button" onclick="openEditAYModal({{ $ay->id }}, '{{ addslashes($ay->nama) }}', '{{ $ay->semester }}', '{{ $ay->tanggal_mulai ? $ay->tanggal_mulai->format('Y-m-d') : '' }}', '{{ $ay->tanggal_selesai ? $ay->tanggal_selesai->format('Y-m-d') : '' }}', '{{ addslashes($ay->keterangan ?? '') }}', {{ $ay->is_active ? 1 : 0 }})" class="p-1.5 rounded-lg text-slate-400 hover:text-brand-600 hover:bg-slate-100" title="Edit Data">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                @if(!$ay->is_active)
                                    <!-- Hapus -->
                                    <form action="{{ route('admin.academic_years.destroy', $ay) }}" method="POST" onsubmit="return confirm('Hapus periode tahun ajaran {{ $ay->full_label }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-slate-100" title="Hapus Periode">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">Belum ada periode tahun ajaran yang terdaftar.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Tambah Tahun Ajaran -->
<div id="addAYModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-5 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Tambah Tahun Ajaran Baru</h3>
                <p class="text-xs text-slate-500 mt-0.5">Definisikan periode akademik sekolah baru</p>
            </div>
            <button type="button" onclick="closeModal('addAYModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup Modal"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form action="{{ route('admin.academic_years.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Tahun Ajaran <span class="text-rose-500">*</span></label>
                <input type="text" name="nama" required placeholder="Contoh: 2026/2027 atau 2027/2028" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Semester <span class="text-rose-500">*</span></label>
                <select name="semester" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="ganjil">Semester Ganjil (Gasal)</option>
                    <option value="genap">Semester Genap</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Keterangan / Catatan</label>
                <textarea name="keterangan" rows="2" placeholder="Catatan tambahan (opsional)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-brand-500 focus:outline-none"></textarea>
            </div>

            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-2.5">
                <input type="checkbox" name="is_active" id="add_is_active" value="1" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                <label for="add_is_active" class="text-xs font-bold text-slate-800 cursor-pointer">
                    Langsung tetapkan sebagai Tahun Ajaran Aktif Berjalan
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('addAYModal')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs transition">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-extrabold text-xs transition shadow-sm">Simpan Tahun Ajaran</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Tahun Ajaran -->
<div id="editAYModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-5 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Edit Tahun Ajaran</h3>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui informasi periode akademik</p>
            </div>
            <button type="button" onclick="closeModal('editAYModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup Modal"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form id="editAYForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Tahun Ajaran <span class="text-rose-500">*</span></label>
                <input type="text" name="nama" id="edit_nama" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Semester <span class="text-rose-500">*</span></label>
                <select name="semester" id="edit_semester" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="ganjil">Semester Ganjil (Gasal)</option>
                    <option value="genap">Semester Genap</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" id="edit_tanggal_mulai" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" id="edit_tanggal_selesai" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Keterangan / Catatan</label>
                <textarea name="keterangan" id="edit_keterangan" rows="2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-brand-500 focus:outline-none"></textarea>
            </div>

            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-2.5">
                <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                <label for="edit_is_active" class="text-xs font-bold text-slate-800 cursor-pointer">
                    Tetapkan sebagai Tahun Ajaran Aktif Berjalan
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('editAYModal')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs transition">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-extrabold text-xs transition shadow-sm">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Wizard Kenaikan Kelas & Kelulusan Massal -->
<div id="wizardKenaikanModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-2xl w-full shadow-2xl space-y-6 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-slate-900 text-lg flex items-center gap-2">
                    <i class="fa-solid fa-graduation-cap text-amber-500"></i>
                    <span>Wizard Kenaikan Kelas & Kelulusan Alumni</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Memproses perpindahan rombel dan kelulusan siswa secara terstruktur dengan rekam jejak otomatis.</p>
            </div>
            <button type="button" onclick="closeModal('wizardKenaikanModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup Modal"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <!-- Mode Tabs -->
        <div class="flex border-b border-slate-200">
            <button type="button" onclick="switchWizardTab('otomatis')" id="tabBtnOtomatis" class="py-2.5 px-4 text-xs font-extrabold border-b-2 border-brand-600 text-brand-600 transition">
                1. Kenaikan Otomatis Akhir Tahun
            </button>
            <button type="button" onclick="switchWizardTab('per_kelas')" id="tabBtnPerKelas" class="py-2.5 px-4 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition">
                2. Kenaikan / Mutasi Per Rombel
            </button>
        </div>

        <!-- Tab 1: Otomatis Akhir Tahun -->
        <div id="wizardTabOtomatis" class="space-y-4">
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900 space-y-2">
                <div class="font-bold flex items-center gap-2 text-amber-800">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Alur Kenaikan Kelas Otomatis:</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-amber-800/90 leading-relaxed">
                    <li><b>Siswa Kelas 9</b> ({{ $siswaKelas9 }} Siswa) &rarr; Dinyatakan <b>LULUS</b> dan langsung dicatat ke <b>Buku Induk Alumni</b>.</li>
                    <li><b>Siswa Kelas 8</b> ({{ $siswaKelas8 }} Siswa) &rarr; Naik ke <b>Kelas 9</b> (misal: 8A &rarr; 9A, 8B &rarr; 9B).</li>
                    <li><b>Siswa Kelas 7</b> ({{ $siswaKelas7 }} Siswa) &rarr; Naik ke <b>Kelas 8</b> (misal: 7A &rarr; 8A, 7B &rarr; 8B).</li>
                    <li>Setiap perpindahan akan otomatis disimpan ke <i>Riwayat Rekam Jejak Rombel</i> siswa.</li>
                </ul>
            </div>

            <form action="{{ route('admin.academic_years.kenaikan_kelas') }}" method="POST" onsubmit="return confirm('PERINGATAN: Apakah Anda yakin ingin memproses Kenaikan Kelas & Kelulusan Otomatis untuk seluruh siswa?')">
                @csrf
                <input type="hidden" name="mode" value="otomatis">

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600 flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-brand-600"></i>
                    <span>Tahun Ajaran Target Rekam Jejak: <b>{{ $activeYear ? $activeYear->full_label : '-' }}</b></span>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeModal('wizardKenaikanModal')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs transition">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-xs transition shadow-sm">
                        <i class="fa-solid fa-bolt mr-1"></i> Jalankan Kenaikan Otomatis
                    </button>
                </div>
            </form>
        </div>

        <!-- Tab 2: Per Rombel Kelas -->
        <div id="wizardTabPerKelas" class="space-y-4 hidden">
            <div class="p-3 bg-blue-50 border border-blue-200 text-xs text-blue-900 rounded-xl">
                Gunakan tab ini untuk menaikkan satu kelas spesifik, memindahkan siswa, atau meluluskan rombel tertentu.
            </div>

            <form action="{{ route('admin.academic_years.kenaikan_kelas') }}" method="POST" onsubmit="return confirm('Proses perpindahan untuk kelas yang dipilih?')">
                @csrf
                <input type="hidden" name="mode" value="per_kelas">

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Rombel Asal <span class="text-rose-500">*</span></label>
                        <select name="kelas_asal" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            <option value="">-- Pilih Kelas Asal --</option>
                            @foreach($classesList as $cl)
                                <option value="{{ $cl }}">Kelas {{ $cl }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Aksi yang Diterapkan <span class="text-rose-500">*</span></label>
                        <select name="aksi" id="perKelasAksi" onchange="toggleKelasTujuan(this.value)" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            <option value="naik">Naik Kelas / Pindah ke Rombel Baru</option>
                            <option value="lulus">Luluskan Siswa (Menjadi Alumni)</option>
                        </select>
                    </div>

                    <div id="containerKelasTujuan">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Kelas Tujuan <span class="text-rose-500">*</span></label>
                        <input type="text" name="kelas_tujuan" id="inputKelasTujuan" placeholder="Contoh: 8A, 9B" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeModal('wizardKenaikanModal')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs transition">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-extrabold text-xs transition shadow-sm">
                        Proses Rombel
                    </button>
                </div>
            </form>
        </div>

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

    function openEditAYModal(id, nama, semester, mulai, selesai, ket, isActive) {
        const form = document.getElementById('editAYForm');
        form.action = `/admin/academic-years/${id}`;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_semester').value = semester;
        document.getElementById('edit_tanggal_mulai').value = mulai;
        document.getElementById('edit_tanggal_selesai').value = selesai;
        document.getElementById('edit_keterangan').value = ket;
        document.getElementById('edit_is_active').checked = isActive === 1;

        openModal('editAYModal');
    }

    function switchWizardTab(tab) {
        const tabOto = document.getElementById('wizardTabOtomatis');
        const tabPer = document.getElementById('wizardTabPerKelas');
        const btnOto = document.getElementById('tabBtnOtomatis');
        const btnPer = document.getElementById('tabBtnPerKelas');

        if (tab === 'otomatis') {
            tabOto.classList.remove('hidden');
            tabPer.classList.add('hidden');
            btnOto.className = 'py-2.5 px-4 text-xs font-extrabold border-b-2 border-brand-600 text-brand-600 transition';
            btnPer.className = 'py-2.5 px-4 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition';
        } else {
            tabOto.classList.add('hidden');
            tabPer.classList.remove('hidden');
            btnPer.className = 'py-2.5 px-4 text-xs font-extrabold border-b-2 border-brand-600 text-brand-600 transition';
            btnOto.className = 'py-2.5 px-4 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition';
        }
    }

    function toggleKelasTujuan(val) {
        const box = document.getElementById('containerKelasTujuan');
        const inp = document.getElementById('inputKelasTujuan');
        if (val === 'lulus') {
            box.classList.add('hidden');
            inp.removeAttribute('required');
        } else {
            box.classList.remove('hidden');
            inp.setAttribute('required', 'required');
        }
    }

    // Tutup modal jika klik backdrop
    window.addEventListener('click', function(e) {
        ['addAYModal', 'editAYModal', 'wizardKenaikanModal'].forEach(id => {
            const m = document.getElementById(id);
            if (m && e.target === m) {
                closeModal(id);
            }
        });
    });

    // Esc key untuk menutup modal
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            ['addAYModal', 'editAYModal', 'wizardKenaikanModal'].forEach(id => closeModal(id));
        }
    });
</script>
@endsection

