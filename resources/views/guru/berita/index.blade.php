@extends('layouts.guru')

@section('page_title', 'Buku Harian Guru & Warta Pembelajaran')

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Buku Harian Guru & Jurnal Pembelajaran</h2>
            <p class="text-xs text-slate-500 mt-1">Dokumentasikan aktivitas mengajar harian, refleksi pembelajaran, maupun warta kegiatan sekolah yang dapat ditampilkan di website utama.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('landing') }}#berita-sekolah" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition">
                <i class="fa-solid fa-arrow-up-right-from-square text-xs text-cyan-600"></i>
                <span>Lihat Web Sekolah</span>
            </a>
            <button type="button" onclick="openModal('addJurnalModal')" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-extrabold text-xs transition shadow-sm">
                <i class="fa-solid fa-pen-nib text-xs"></i>
                <span>Tulis Jurnal / Berita Baru</span>
            </button>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Catatan Saya</span>
                <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ number_format($totalJurnal) }}</div>
                <div class="text-[11px] text-slate-400 mt-0.5">Buku harian & warta kelas</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-book-journal-whills"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tayang di Website Utama</span>
                <div class="text-2xl font-extrabold text-emerald-600 mt-1">{{ number_format($totalWeb) }}</div>
                <div class="text-[11px] text-emerald-700/80 mt-0.5">Dapat dibaca oleh umum & wali murid</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-globe"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Catatan Internal (Pribadi)</span>
                <div class="text-2xl font-extrabold text-slate-700 mt-1">{{ number_format($totalPrivate) }}</div>
                <div class="text-[11px] text-slate-400 mt-0.5">Hanya tersimpan di akun Anda</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-lock"></i>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('guru.berita.index') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
            <div>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul catatan..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
            </div>

            <div>
                <select name="academic_year_id" onchange="this.form.submit()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    <option value="">Semua Tahun Ajaran</option>
                    @foreach($academicYears as $ay)
                        <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>TA: {{ $ay->full_label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="bulan" onchange="this.form.submit()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    <option value="">Semua Bulan</option>
                    @for($m = 1; $m <= 12; $m++)
                        @php
                            $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                        @endphp
                        <option value="{{ $m }}" {{ request('bulan') == $m ? 'selected' : '' }}>{{ $monthNames[$m] }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <select name="kategori" onchange="this.form.submit()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                    <option value="">Semua Kategori</option>
                    <option value="buku_harian" {{ request('kategori') == 'buku_harian' ? 'selected' : '' }}>Buku Harian Mengajar</option>
                    <option value="berita" {{ request('kategori') == 'berita' ? 'selected' : '' }}>Berita / Kegiatan</option>
                    <option value="prestasi" {{ request('kategori') == 'prestasi' ? 'selected' : '' }}>Prestasi Siswa</option>
                    <option value="pengumuman" {{ request('kategori') == 'pengumuman' ? 'selected' : '' }}>Pengumuman</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold">Filter</button>
                @if(request()->hasAny(['q', 'academic_year_id', 'bulan', 'kategori']))
                    <a href="{{ route('guru.berita.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-500 text-xs font-bold">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">No</th>
                        <th class="py-3.5 px-4 w-20 text-center">Foto</th>
                        <th class="py-3.5 px-4">Judul Catatan / Warta</th>
                        <th class="py-3.5 px-4 text-center">Kategori</th>
                        <th class="py-3.5 px-4 text-center">Tahun Ajaran</th>
                        <th class="py-3.5 px-4 text-center">Tanggal</th>
                        <th class="py-3.5 px-4 text-center">Tayang di Web?</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($beritas as $idx => $b)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 text-center text-slate-400 font-mono">{{ $beritas->firstItem() + $idx }}</td>
                        <td class="py-3 px-4 text-center">
                            @if($b->gambar)
                                <img src="{{ asset($b->gambar) }}" alt="{{ $b->judul }}" class="w-14 h-11 object-cover rounded-lg border border-slate-200 mx-auto">
                            @else
                                <div class="w-14 h-11 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center text-xs mx-auto border border-slate-200">
                                    <i class="fa-solid fa-image"></i>
                                </div>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900 text-sm line-clamp-1" title="{{ $b->judul }}">{{ $b->judul }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">
                                {{ Str::limit(strip_tags($b->konten), 90) }}
                            </div>
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($b->kategori === 'buku_harian')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    Buku Harian
                                </span>
                            @elseif($b->kategori === 'prestasi')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    Prestasi
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-800 border border-cyan-200">
                                    {{ ucfirst($b->kategori) }}
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center text-slate-600 text-[11px]">
                            {{ $b->academicYear?->full_label ?? '-' }}
                        </td>
                        <td class="py-3 px-4 text-center text-slate-600 text-[11px]">
                            {{ $b->tanggal ? $b->tanggal->format('d/m/Y') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <form action="{{ route('guru.berita.toggle_web', $b) }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold transition {{ $b->tampil_di_web ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200' }}" title="Ubah status publikasi">
                                    <i class="fa-solid {{ $b->tampil_di_web ? 'fa-globe' : 'fa-lock' }}"></i>
                                    <span>{{ $b->tampil_di_web ? 'Tayang di Web' : 'Internal' }}</span>
                                </button>
                            </form>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                @if($b->tampil_di_web)
                                <a href="{{ route('berita.show', $b->slug) }}" target="_blank" class="p-1.5 rounded-lg text-slate-400 hover:text-cyan-600 hover:bg-slate-100" title="Buka di Website">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                                @endif
                                <button type="button" onclick="openEditJurnalModal({{ $b->id }}, '{{ addslashes($b->judul) }}', '{{ $b->kategori }}', '{{ $b->tanggal ? $b->tanggal->format('Y-m-d') : '' }}', '{{ $b->academic_year_id }}', {{ $b->tampil_di_web ? 1 : 0 }}, `{{ addslashes($b->konten) }}`)" class="p-1.5 rounded-lg text-slate-400 hover:text-cyan-600 hover:bg-slate-100" title="Edit Catatan">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('guru.berita.destroy', $b) }}" method="POST" onsubmit="return confirm('Hapus catatan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-slate-100" title="Hapus Catatan">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">Belum ada buku harian atau catatan yang Anda tulis. Klik tombol di atas untuk membuat catatan pertama!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($beritas->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $beritas->links() }}
        </div>
        @endif
    </div>

</div>

<!-- Modal Tambah Jurnal Guru -->
<div id="addJurnalModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-2xl w-full shadow-2xl space-y-5 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Tulis Buku Harian / Berita Pembelajaran</h3>
                <p class="text-xs text-slate-500 mt-0.5">Catat refleksi kegiatan mengajar atau publikasikan berita kegiatan kelas Anda</p>
            </div>
            <button type="button" onclick="closeModal('addJurnalModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form action="{{ route('guru.berita.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
            @csrf

            <div>
                <label class="block font-bold text-slate-700 mb-1">Judul Catatan / Warta <span class="text-rose-500">*</span></label>
                <input type="text" name="judul" required placeholder="Contoh: Praktik Percobaan IPA dan Refleksi Belajar di Kelas 8A" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kategori <span class="text-rose-500">*</span></label>
                    <select name="kategori" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="buku_harian">Buku Harian Mengajar (Jurnal)</option>
                        <option value="berita">Berita Kegiatan Siswa</option>
                        <option value="prestasi">Prestasi & Karya Siswa</option>
                        <option value="pengumuman">Pengumuman Kelas</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tahun Ajaran Terkait</label>
                    <select name="academic_year_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="">-- Periode Aktif Saat Ini --</option>
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ ($activeYear && $activeYear->id == $ay->id) ? 'selected' : '' }}>TA: {{ $ay->full_label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tanggal Kegiatan <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal" value="{{ now()->toDateString() }}" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Foto / Dokumentasi Pembelajaran (Opsional)</label>
                <input type="file" name="gambar" accept="image/*" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                <span class="text-[10px] text-emerald-600 font-semibold mt-1 flex items-center gap-1">
                    <i class="fa-solid fa-wand-magic-sparkles text-[10px]"></i>
                    <span>Foto otomatis dikompresi menjadi WebP super ringan & jernih.</span>
                </span>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Isi Catatan / Jurnal Refleksi <span class="text-rose-500">*</span></label>
                <textarea name="konten" rows="6" required placeholder="Tuliskan pengalaman pembelajaran, kendala yang dihadapi, solusi, atau capaian siswa hari ini..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none font-sans"></textarea>
            </div>

            <div class="p-3 bg-cyan-50/60 rounded-xl border border-cyan-200 flex items-center gap-2.5">
                <input type="checkbox" name="tampil_di_web" id="guru_tampil_web" value="1" class="w-4 h-4 rounded text-cyan-600 focus:ring-cyan-500 border-slate-300">
                <label for="guru_tampil_web" class="text-xs font-bold text-slate-800 cursor-pointer">
                    Tampilkan catatan ini di Website Utama Sekolah (bisa dibaca masyarakat & orang tua)
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('addJurnalModal')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold shadow-sm">Simpan Catatan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Jurnal Guru -->
<div id="editJurnalModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-2xl w-full shadow-2xl space-y-5 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Edit Catatan / Buku Harian</h3>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui rincian jurnal Anda</p>
            </div>
            <button type="button" onclick="closeModal('editJurnalModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form id="editJurnalForm" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
            @csrf
            @method('PUT')

            <div>
                <label class="block font-bold text-slate-700 mb-1">Judul <span class="text-rose-500">*</span></label>
                <input type="text" name="judul" id="edit_judul" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kategori <span class="text-rose-500">*</span></label>
                    <select name="kategori" id="edit_kategori" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="buku_harian">Buku Harian Mengajar</option>
                        <option value="berita">Berita Kegiatan Siswa</option>
                        <option value="prestasi">Prestasi & Karya Siswa</option>
                        <option value="pengumuman">Pengumuman Kelas</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tahun Ajaran</label>
                    <select name="academic_year_id" id="edit_academic_year_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}">TA: {{ $ay->full_label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tanggal Kegiatan <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal" id="edit_tanggal" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Ganti Foto Dokumentasi (Opsional)</label>
                <input type="file" name="gambar" accept="image/*" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                <span class="text-[10px] text-emerald-600 font-semibold mt-1 flex items-center gap-1">
                    <i class="fa-solid fa-wand-magic-sparkles text-[10px]"></i>
                    <span>Foto baru otomatis dikonversi dan dikompresi ke format WebP.</span>
                </span>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Isi Catatan <span class="text-rose-500">*</span></label>
                <textarea name="konten" id="edit_konten" rows="6" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none font-sans"></textarea>
            </div>

            <div class="p-3 bg-cyan-50/60 rounded-xl border border-cyan-200 flex items-center gap-2.5">
                <input type="checkbox" name="tampil_di_web" id="edit_tampil_web" value="1" class="w-4 h-4 rounded text-cyan-600 focus:ring-cyan-500 border-slate-300">
                <label for="edit_tampil_web" class="text-xs font-bold text-slate-800 cursor-pointer">
                    Tampilkan catatan ini di Website Utama Sekolah
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('editJurnalModal')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold shadow-sm">Simpan Perubahan</button>
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

    function openEditJurnalModal(id, judul, kategori, tanggal, ayId, tampilWeb, konten) {
        document.getElementById('editJurnalForm').action = "/guru/berita/" + id;
        document.getElementById('edit_judul').value = judul;
        document.getElementById('edit_kategori').value = kategori;
        document.getElementById('edit_tanggal').value = tanggal;
        document.getElementById('edit_academic_year_id').value = ayId;
        document.getElementById('edit_tampil_web').checked = tampilWeb === 1;
        document.getElementById('edit_konten').value = konten;
        openModal('editJurnalModal');
    }

    window.addEventListener('click', function(e) {
        ['addJurnalModal', 'editJurnalModal'].forEach(id => {
            const m = document.getElementById(id);
            if (m && e.target === m) closeModal(id);
        });
    });

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            ['addJurnalModal', 'editJurnalModal'].forEach(id => closeModal(id));
        }
    });
</script>
@endsection

