@extends('layouts.app')

@section('title', 'Ruang Ujian Siswa - Espega Sinau')

@section('body')
<div class="min-h-screen bg-slate-50 flex flex-col">

    <!-- Top Navigation Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="/assets/img/logo-espega.png" alt="Logo SMPN 3 Ngawen" class="w-11 h-11 object-contain shrink-0">
                <div>
                    <span class="block text-base font-extrabold text-slate-900 tracking-tight">ESPEGA SINAU</span>
                    <span class="block text-[11px] font-semibold text-brand-600">SMP Negeri 3 Ngawen</span>
                </div>
            </div>

            <!-- User Profile & Logout -->
            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <div class="text-sm font-bold text-slate-900">{{ $siswa->nama_lengkap }}</div>
                    <div class="text-xs text-slate-400 font-semibold">
                        Kelas: <span class="text-brand-600 font-bold">{{ $siswa->kelas }}</span> • NIS: {{ $siswa->username }}
                        @if(isset($activeAcademicYear) && $activeAcademicYear)
                            • <span class="text-slate-500 font-medium">TA {{ $activeAcademicYear->full_label }}</span>
                        @endif
                    </div>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-brand-50 text-brand-700 font-extrabold flex items-center justify-center text-sm border border-brand-200">
                    {{ strtoupper(substr($siswa->nama_lengkap, 0, 2)) }}
                </div>
                <a href="{{ route('logout') }}" class="p-2.5 rounded-xl border border-slate-200 text-slate-400 hover:text-rose-600 hover:bg-slate-50 transition-colors" title="Keluar">
                    <i class="fa-solid fa-power-off"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 space-y-8">

        <!-- Welcome Banner -->
        @if($siswa->isAlumni())
        <div class="bg-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-lg flex flex-col sm:flex-row items-center justify-between gap-6 border border-slate-800">
            <div class="space-y-2">
                <span class="inline-block px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 border border-amber-400/30 text-xs font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-graduation-cap mr-1"></i> Status: Alumni / Lulusan
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Selamat Datang, {{ $siswa->nama_lengkap }}!</h1>
                <p class="text-sm text-slate-300 max-w-xl">
                    Selamat atas kelulusan Anda dari SMP Negeri 3 Ngawen. Akun Anda tersimpan secara resmi di Buku Induk Alumni dan Anda dapat melihat riwayat ujian yang telah diselesaikan di bawah ini.
                </p>
            </div>
            <div class="p-4 rounded-2xl bg-white/5 border border-white/10 text-center shrink-0">
                <div class="text-xs font-semibold text-slate-400 uppercase">Status Kesiswaan</div>
                <div class="text-2xl font-extrabold text-amber-400 font-mono mt-0.5">ALUMNI</div>
                @if($siswa->tahunLulus)
                    <div class="text-[11px] text-slate-400 mt-1">Lulus TA {{ $siswa->tahunLulus->nama }}</div>
                @endif
            </div>
        </div>
        @else
        <div class="bg-gradient-to-r from-brand-900 to-blue-800 rounded-3xl p-6 sm:p-8 text-white shadow-lg flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="space-y-2">
                <span class="inline-block px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-xs font-bold text-blue-200">Portal Peserta Didik</span>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Selamat Datang, {{ $siswa->nama_lengkap }}!</h1>
                <p class="text-sm text-blue-100 max-w-xl">
                    Kerjakan setiap asesmen dengan jujur, teliti, dan penuh tanggung jawab. Periksa kembali jawaban Anda sebelum menekan tombol selesai.
                </p>
            </div>
            <div class="p-4 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 text-center shrink-0">
                <div class="text-xs font-semibold text-blue-200 uppercase">Rombel Kelas Anda</div>
                <div class="text-2xl font-extrabold text-amber-400 font-mono mt-0.5">KELAS {{ $siswa->kelas }}</div>
            </div>
        </div>
        @endif

        <!-- Section 1: Ujian Aktif / Berlangsung -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></span>
                    <h2 class="text-lg font-bold text-slate-900">Ujian Siap Dikerjakan (Aktif)</h2>
                </div>
                <span class="text-xs text-slate-500 font-semibold">{{ $activeExams->count() }} Ujian Aktif</span>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @forelse($activeExams as $exam)
                @php
                    $session = $exam->sessions->first();
                @endphp
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex flex-col justify-between hover:border-brand-400 hover:shadow-md transition-all">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-brand-700 font-bold text-xs border border-blue-100">
                                {{ $exam->subject->nama_mapel ?? 'Mata Pelajaran' }}
                            </span>
                            @if($session && $session->status_siswa === 'pengerjaan')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    Sedang Dikerjakan
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Siap Masuk
                                </span>
                            @endif
                        </div>

                        <h3 class="font-bold text-slate-900 text-base leading-snug">
                            {{ $exam->nama_ujian }}
                        </h3>

                        <div class="text-xs text-slate-500 space-y-1.5 pt-1">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-clock text-slate-400 w-4"></i>
                                <span>Durasi: <strong>{{ $exam->durasi }} Menit</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-calendar-day text-slate-400 w-4"></i>
                                <span>Mulai: <strong>{{ $exam->waktu_mulai ? $exam->waktu_mulai->format('d M Y, H:i') : '-' }} WIB</strong></span>
                            </div>
                            @if($exam->waktu_selesai)
                            <div class="flex items-center gap-2 text-rose-600 font-medium">
                                <i class="fa-solid fa-hourglass-end w-4"></i>
                                <span>Batas: <strong>{{ $exam->waktu_selesai->format('H:i') }} WIB</strong></span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="pt-5 mt-5 border-t border-slate-100">
                        @if($session && $session->status_siswa === 'pengerjaan')
                            <a href="{{ route('siswa.exam.show', $exam) }}" class="w-full py-3 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs text-center flex items-center justify-center gap-2 shadow-sm transition-colors">
                                <i class="fa-solid fa-play"></i>
                                <span>Lanjutkan Pengerjaan</span>
                            </a>
                        @else
                            <button type="button"
                                    onclick="openTokenModalFromBtn(this)"
                                    data-exam-id="{{ $exam->id }}"
                                    data-exam-title="{{ $exam->nama_ujian }}"
                                    data-exam-subject="{{ $exam->subject->nama_mapel ?? '-' }}"
                                    data-exam-class="{{ $exam->target_kelas }}"
                                    class="w-full py-3 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs text-center flex items-center justify-center gap-2 shadow-sm shadow-blue-500/20 transition-colors">
                                <i class="fa-solid fa-key"></i>
                                <span>Masukkan Token Ujian</span>
                            </button>
                        @endif
                    </div>
                </div>
                @empty
                <div class="col-span-3 bg-white rounded-3xl p-8 text-center text-slate-400 border border-slate-200">
                    <i class="fa-solid fa-calendar-xmark text-3xl mb-2 text-slate-300"></i>
                    <p class="text-xs">Tidak ada jadwal ujian yang sedang aktif untuk kelas {{ $siswa->kelas ?: '-' }} saat ini.</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Section 2: Jadwal Ujian Akan Datang (Upcoming) -->
        @if($upcomingExams->count() > 0)
        <div class="space-y-4 pt-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-calendar-clock text-amber-500 text-lg"></i>
                    <h2 class="text-lg font-bold text-slate-900">Jadwal Ujian Akan Datang</h2>
                </div>
                <span class="text-xs text-slate-500 font-semibold">{{ $upcomingExams->count() }} Ujian Terjadwal</span>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($upcomingExams as $exam)
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex flex-col justify-between hover:border-amber-300 hover:shadow-md transition-all">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 font-bold text-xs border border-amber-200">
                                {{ $exam->subject->nama_mapel ?? 'Mata Pelajaran' }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                Akan Datang
                            </span>
                        </div>

                        <h3 class="font-bold text-slate-900 text-base leading-snug">
                            {{ $exam->nama_ujian }}
                        </h3>

                        <div class="text-xs text-slate-500 space-y-1.5 pt-1">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-clock text-slate-400 w-4"></i>
                                <span>Durasi: <strong>{{ $exam->durasi }} Menit</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-calendar-day text-slate-400 w-4"></i>
                                <span>Jadwal Mulai: <strong class="text-slate-800">{{ $exam->waktu_mulai ? $exam->waktu_mulai->format('d M Y, H:i') : '-' }} WIB</strong></span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-400">
                                <i class="fa-solid fa-users-rectangle w-4"></i>
                                <span>Sasaran: <strong>{{ $exam->target_kelas }}</strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-5 mt-5 border-t border-slate-100 space-y-2">
                        <button type="button"
                                onclick="openTokenModalFromBtn(this)"
                                data-exam-id="{{ $exam->id }}"
                                data-exam-title="{{ $exam->nama_ujian }}"
                                data-exam-subject="{{ $exam->subject->nama_mapel ?? '-' }}"
                                data-exam-class="{{ $exam->target_kelas }}"
                                class="w-full py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs text-center flex items-center justify-center gap-2 transition-colors">
                            <i class="fa-solid fa-key"></i>
                            <span>Cek / Masukkan Token</span>
                        </button>
                        <p class="text-[11px] text-center text-slate-400">
                            Pengerjaan dibuka otomatis saat waktu jadwal tiba.
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Section 2: Riwayat Ujian yang Telah Selesai -->
        @if($completedExams->count() > 0)
        <div class="space-y-4 pt-4">
            <h2 class="text-lg font-bold text-slate-900">Ujian yang Telah Selesai</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($completedExams as $exam)
                @php
                    $session = $exam->sessions->first();
                @endphp
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex flex-col justify-between opacity-80 hover:opacity-100 transition-opacity">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-bold text-xs">
                                {{ $exam->subject->nama_mapel ?? '-' }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Selesai
                            </span>
                        </div>
                        <h3 class="font-bold text-slate-800 text-sm leading-snug">
                            {{ $exam->nama_ujian }}
                        </h3>
                        <div class="text-xs text-slate-500">
                            Skor Akhir: <strong class="text-sm font-mono text-emerald-600 font-extrabold">{{ $session->skor ?? 0 }}</strong>
                        </div>
                    </div>

                    <div class="pt-4 mt-4 border-t border-slate-100">
                        <a href="{{ route('siswa.exam.result', $exam) }}" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs text-center flex items-center justify-center gap-1.5 transition-colors">
                            <i class="fa-solid fa-square-poll-vertical"></i>
                            <span>Lihat Hasil Ujian</span>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-400">
        &copy; {{ date('Y') }} SMP Negeri 3 Ngawen. Platform Digital Espega Sinau.
    </footer>
</div>

<!-- Modal Masukkan Token Ujian (Kompatibel 100% Seluruh Perangkat: HP, Tablet, Laptop, Desktop) -->
<div id="tokenModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-5 text-center my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
        <!-- Tombol Tutup Silang (Ramah Touchscreen HP/Tablet) -->
        <button type="button" onclick="closeTokenModal()" class="absolute top-4 right-4 w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup Modal">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>

        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-brand-600 flex items-center justify-center mx-auto text-2xl shadow-inner">
            <i class="fa-solid fa-key"></i>
        </div>

        <div>
            <h3 class="font-extrabold text-slate-900 text-lg leading-snug" id="modalExamTitle">Verifikasi Token Ujian</h3>
            <p class="text-xs text-slate-500 mt-1" id="modalExamSub">Minta kode token ujian kepada guru pengawas ruang.</p>
        </div>

        <form id="tokenForm" onsubmit="handleTokenSubmit(event)" class="space-y-4">
            <div>
                <input type="text" id="tokenInput" maxlength="10" required autocomplete="off"
                       placeholder="Contoh: A9EECA"
                       class="w-full text-center tracking-widest font-mono text-xl uppercase font-extrabold px-4 py-3 rounded-2xl border-2 border-slate-200 focus:border-brand-600 focus:ring-4 focus:ring-blue-100 focus:outline-none">
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" onclick="closeTokenModal()" class="flex-1 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50 transition-colors">
                    Batal
                </button>
                <button type="submit" id="btnSubmitToken" class="flex-1 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition-colors">
                    Mulai Ujian
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    let currentExamId = null;

    function openTokenModalFromBtn(btn) {
        currentExamId = btn.getAttribute('data-exam-id');
        const title = btn.getAttribute('data-exam-title') || 'Verifikasi Token Ujian';
        const subject = btn.getAttribute('data-exam-subject') || '-';
        const kelas = btn.getAttribute('data-exam-class') || '';
        showTokenModal(currentExamId, title, subject, kelas);
    }

    function showTokenModal(id, title, subject, kelas) {
        currentExamId = id;
        document.getElementById('modalExamTitle').innerText = title;
        document.getElementById('modalExamSub').innerText = 'Mata Pelajaran: ' + subject + (kelas ? ' (Kelas ' + kelas + ')' : '');
        document.getElementById('tokenInput').value = '';

        window.openModal('tokenModal');
        setTimeout(() => {
            const inp = document.getElementById('tokenInput');
            if (inp) inp.focus();
        }, 150);
    }

    // Kompatibilitas mundur jika fungsi dipanggil via openTokenModal(exam)
    function openTokenModal(examOrId, title, subject, kelas) {
        if (typeof examOrId === 'object' && examOrId !== null) {
            showTokenModal(examOrId.id, examOrId.nama_ujian, (examOrId.subject ? examOrId.subject.nama_mapel : '-'), examOrId.target_kelas);
        } else {
            showTokenModal(examOrId, title, subject, kelas);
        }
    }

    function closeTokenModal() {
        window.closeModal('tokenModal');
    }

    // Tutup modal jika latar belakang diklik pada layar sentuh
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('tokenModal');
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    closeTokenModal();
                }
            });
        }
    });

    // Tutup modal jika tombol Escape ditekan
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeTokenModal();
        }
    });

    function handleTokenSubmit(e) {
        e.preventDefault();
        const token = document.getElementById('tokenInput').value.trim();
        const btn = document.getElementById('btnSubmitToken');

        if (!token || !currentExamId) return;

        btn.disabled = true;
        btn.innerText = 'Memverifikasi...';

        fetch(`/siswa/exam/${currentExamId}/verify-token`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ token: token })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerText = 'Mulai Ujian';

            if (data.status === 'success') {
                window.location.href = data.redirect;
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Masuk',
                    text: data.message || 'Token tidak valid'
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = 'Mulai Ujian';
            Swal.fire({
                icon: 'error',
                title: 'Koneksi Gagal',
                text: 'Terjadi kendala jaringan saat memverifikasi token.'
            });
        });
    }
</script>
@endpush
@endsection

