@extends('layouts.admin')

@section('page_title', 'Layanan Permohonan Informasi & Pengaduan')

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Permohonan Informasi & Pengaduan Publik</h2>
            <p class="text-xs text-slate-500 mt-1">Kelola permohonan informasi, pertanyaan, dan pengaduan masyarakat yang diajukan melalui portal utama website sekolah.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('landing') }}#kontak" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition">
                <i class="fa-solid fa-arrow-up-right-from-square text-xs text-brand-600"></i>
                <span>Lihat Form & Cek Tiket di Web</span>
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- Quick Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('admin.permohonan.index') }}" class="bg-white rounded-2xl p-5 border {{ $statusFilter === 'all' ? 'border-brand-500 ring-2 ring-brand-100' : 'border-slate-200' }} shadow-sm hover:border-brand-300 transition block">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Masuk</span>
                    <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ number_format($counts['all']) }}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">Semua permohonan</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-inbox"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.permohonan.index', ['status' => 'pending']) }}" class="bg-white rounded-2xl p-5 border {{ $statusFilter === 'pending' ? 'border-amber-500 ring-2 ring-amber-100' : 'border-slate-200' }} shadow-sm hover:border-amber-300 transition block">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">Menunggu Respon</span>
                    <div class="text-2xl font-extrabold text-amber-600 mt-1">{{ number_format($counts['pending']) }}</div>
                    <div class="text-[11px] text-amber-600/80 mt-0.5">Belum ditanggapi</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.permohonan.index', ['status' => 'proses']) }}" class="bg-white rounded-2xl p-5 border {{ $statusFilter === 'proses' ? 'border-sky-500 ring-2 ring-sky-100' : 'border-slate-200' }} shadow-sm hover:border-sky-300 transition block">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-sky-600 uppercase tracking-wider">Sedang Diproses</span>
                    <div class="text-2xl font-extrabold text-sky-600 mt-1">{{ number_format($counts['proses']) }}</div>
                    <div class="text-[11px] text-sky-600/80 mt-0.5">Dalam koordinasi</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.permohonan.index', ['status' => 'selesai']) }}" class="bg-white rounded-2xl p-5 border {{ $statusFilter === 'selesai' ? 'border-emerald-500 ring-2 ring-emerald-100' : 'border-slate-200' }} shadow-sm hover:border-emerald-300 transition block">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Selesai Ditanggapi</span>
                    <div class="text-2xl font-extrabold text-emerald-600 mt-1">{{ number_format($counts['selesai']) }}</div>
                    <div class="text-[11px] text-emerald-600/80 mt-0.5">Sudah diberikan jawaban</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </a>
    </div>

    <!-- Filter Tab Bar & Pencarian -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
        <!-- Status Tabs -->
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('admin.permohonan.index', ['status' => 'all', 'q' => $q]) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $statusFilter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Semua ({{ $counts['all'] }})
            </a>
            <a href="{{ route('admin.permohonan.index', ['status' => 'pending', 'q' => $q]) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $statusFilter === 'pending' ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                Menunggu ({{ $counts['pending'] }})
            </a>
            <a href="{{ route('admin.permohonan.index', ['status' => 'proses', 'q' => $q]) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $statusFilter === 'proses' ? 'bg-sky-500 text-white' : 'bg-sky-50 text-sky-700 hover:bg-sky-100' }}">
                Diproses ({{ $counts['proses'] }})
            </a>
            <a href="{{ route('admin.permohonan.index', ['status' => 'selesai', 'q' => $q]) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $statusFilter === 'selesai' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                Selesai ({{ $counts['selesai'] }})
            </a>
            <a href="{{ route('admin.permohonan.index', ['status' => 'ditolak', 'q' => $q]) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $statusFilter === 'ditolak' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                Ditolak ({{ $counts['ditolak'] }})
            </a>
        </div>

        <!-- Search Form -->
        <form method="GET" action="{{ route('admin.permohonan.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <div class="relative w-full md:w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari no tiket, nama, pesan..." class="w-full pl-9 pr-3.5 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition">Cari</button>
            @if(!empty($q))
                <a href="{{ route('admin.permohonan.index', ['status' => $statusFilter]) }}" class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-slate-500 text-xs hover:bg-slate-50" title="Reset pencarian">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            @endif
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3.5">Nomor Tiket & Tanggal</th>
                        <th class="px-5 py-3.5">Pemohon & Kontak</th>
                        <th class="px-5 py-3.5">Keperluan & Pesan</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5">Petugas Penjawab</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($permohonans as $item)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="font-mono font-bold text-slate-900 block text-xs tracking-wide">{{ $item->nomor_laporan }}</span>
                                <span class="text-[11px] text-slate-400 mt-0.5 block flex items-center gap-1">
                                    <i class="fa-regular fa-clock text-[10px]"></i>
                                    {{ $item->created_at?->format('d M Y, H:i') }} WIB
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="font-bold text-slate-900">{{ $item->nama_lengkap }}</div>
                                <div class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                                    <i class="fa-solid fa-address-book text-[10px] text-slate-400"></i>
                                    <span>{{ $item->kontak }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 max-w-xs md:max-w-md">
                                <div class="font-bold text-slate-800 truncate" title="{{ $item->subjek }}">{{ $item->subjek }}</div>
                                <p class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">{{ $item->pesan }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $item->status_badge_class }}">
                                    @if($item->status === 'pending')
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                    @elseif($item->status === 'proses')
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500 mr-1.5"></span>
                                    @elseif($item->status === 'selesai')
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                    @else
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span>
                                    @endif
                                    {{ $item->status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                @if($item->tanggapan)
                                    <div class="font-bold text-slate-800">{{ $item->petugas?->nama ?? 'Admin Sekolah' }}</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $item->tanggal_tanggapan?->format('d M Y, H:i') }} WIB</div>
                                @else
                                    <span class="text-[11px] text-slate-400 italic">Belum ditanggapi</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" onclick="openDetailModal({{ json_encode($item) }})" class="px-2.5 py-1.5 rounded-lg bg-brand-50 hover:bg-brand-100 text-brand-700 font-bold text-xs transition flex items-center gap-1.5" title="Buka Detail & Beri Tanggapan">
                                        <i class="fa-solid fa-reply"></i>
                                        <span>Tanggapi</span>
                                    </button>
                                    <form method="POST" action="{{ route('admin.permohonan.destroy', $item->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus permohonan {{ $item->nomor_laporan }}?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition" title="Hapus Laporan">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                <i class="fa-solid fa-inbox text-3xl mb-2 text-slate-300 block"></i>
                                <div class="font-bold text-slate-600">Tidak ada permohonan informasi ditemukan</div>
                                <div class="text-xs text-slate-400 mt-1">Belum ada data pada kriteria status atau pencarian ini.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($permohonans->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $permohonans->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal Detail & Tanggapi Permohonan -->
<div id="detailModal" class="modal-overlay fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="modal-dialog relative bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 text-left shadow-2xl my-auto animate-fadeIn border border-slate-200" onclick="event.stopPropagation()">
        <!-- Modal Header -->
        <div class="flex items-start justify-between border-b border-slate-100 pb-4">
            <div>
                <div class="flex items-center gap-2">
                    <span id="modal_nomor_laporan" class="font-mono text-sm font-extrabold text-brand-700 bg-brand-50 px-2.5 py-1 rounded-lg border border-brand-200"></span>
                    <span id="modal_status_badge" class="px-2.5 py-0.5 rounded-full text-xs font-bold border"></span>
                </div>
                <div class="text-xs text-slate-400 mt-1.5" id="modal_tanggal_kirim"></div>
            </div>
            <button type="button" onclick="closeModal('detailModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors shrink-0" title="Tutup Modal">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Detail Permohonan -->
        <div class="py-4 space-y-4">
            <div class="grid grid-cols-2 gap-4 p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Nama Lengkap Pemohon</span>
                    <span id="modal_nama_lengkap" class="font-bold text-slate-900 text-sm mt-0.5 block"></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Kontak (WhatsApp / Email)</span>
                    <span id="modal_kontak" class="font-semibold text-slate-800 text-sm mt-0.5 block"></span>
                </div>
            </div>

            <div>
                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Keperluan / Subjek</label>
                <div id="modal_subjek" class="font-bold text-slate-900 text-sm p-3 rounded-xl bg-slate-50 border border-slate-200"></div>
            </div>

            <div>
                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Isi Pesan Permohonan / Pengaduan</label>
                <div id="modal_pesan" class="text-xs text-slate-700 leading-relaxed p-3.5 rounded-xl bg-slate-50 border border-slate-200 whitespace-pre-line max-h-48 overflow-y-auto"></div>
            </div>

            <!-- Form Tanggapan Admin -->
            <form id="tanggapanForm" method="POST" action="" class="space-y-4 pt-2 border-t border-slate-100">
                @csrf
                @method('PUT')

                <div>
                    <label for="form_status" class="block text-xs font-bold text-slate-700 uppercase mb-1">Status Permohonan</label>
                    <select id="form_status" name="status" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-brand-500 focus:outline-none bg-white">
                        <option value="pending">Menunggu Respon</option>
                        <option value="proses">Sedang Diproses (Koordinasi Internal)</option>
                        <option value="selesai">Selesai Ditanggapi (Diberikan Jawaban)</option>
                        <option value="ditolak">Ditolak / Tidak Sesuai Ketentuan</option>
                    </select>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="form_tanggapan" class="text-xs font-bold text-slate-700 uppercase">Tanggapan / Jawaban Resmi Sekolah</label>
                        <span class="text-[10px] text-slate-400">Dapat dibaca oleh pemohon di website</span>
                    </div>
                    <textarea id="form_tanggapan" name="tanggapan" rows="4" placeholder="Tuliskan jawaban atau keterangan tindak lanjut dari pihak sekolah..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs leading-relaxed focus:ring-2 focus:ring-brand-500 focus:outline-none"></textarea>
                </div>

                <div id="modal_info_petugas" class="text-[11px] text-slate-500 p-2.5 rounded-lg bg-slate-50 border border-slate-200 hidden">
                    <i class="fa-solid fa-circle-info text-brand-600 mr-1"></i>
                    <span id="modal_info_petugas_text"></span>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('detailModal')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold transition">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-extrabold text-xs transition shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Simpan Tanggapan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openDetailModal(item) {
        document.getElementById('modal_nomor_laporan').textContent = item.nomor_laporan;
        
        const badgeEl = document.getElementById('modal_status_badge');
        badgeEl.className = 'px-2.5 py-0.5 rounded-full text-xs font-bold border ' + (item.status_badge_class || 'bg-slate-50 text-slate-700 border-slate-200');
        badgeEl.textContent = item.status_label || item.status;

        const dateKirim = item.created_at ? new Date(item.created_at).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' }) + ' WIB' : '-';
        document.getElementById('modal_tanggal_kirim').textContent = 'Diajukan pada: ' + dateKirim;

        document.getElementById('modal_nama_lengkap').textContent = item.nama_lengkap;
        document.getElementById('modal_kontak').textContent = item.kontak;
        document.getElementById('modal_subjek').textContent = item.subjek;
        document.getElementById('modal_pesan').textContent = item.pesan;

        // Set action form
        document.getElementById('tanggapanForm').action = '/admin/permohonan-informasi/' + item.id;
        document.getElementById('form_status').value = item.status || 'pending';
        document.getElementById('form_tanggapan').value = item.tanggapan || '';

        const infoPetugas = document.getElementById('modal_info_petugas');
        const infoPetugasText = document.getElementById('modal_info_petugas_text');
        if (item.tanggapan && item.petugas) {
            infoPetugas.classList.remove('hidden');
            const dateTanggapan = item.tanggal_tanggapan ? new Date(item.tanggal_tanggapan).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' }) + ' WIB' : '';
            infoPetugasText.textContent = 'Terakhir ditanggapi oleh ' + item.petugas.nama + ' pada ' + dateTanggapan;
        } else {
            infoPetugas.classList.add('hidden');
        }

        if (typeof window.openModal === 'function') {
            window.openModal('detailModal');
        } else {
            const m = document.getElementById('detailModal');
            if (m) {
                m.classList.remove('hidden');
                m.classList.add('flex');
            }
        }
    }
</script>
@endsection

