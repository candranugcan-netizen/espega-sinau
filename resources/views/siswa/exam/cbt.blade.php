@extends('layouts.app')

@section('title', 'CBT: ' . $exam->nama_ujian . ' - Espega Sinau')

@section('body')
<div class="h-screen w-screen flex flex-col bg-slate-100 overflow-hidden select-none" id="cbtApp">

    <!-- CBT Topbar -->
    <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between shrink-0 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <img src="/assets/img/logo-espega.png" alt="Logo" class="w-9 h-9 object-contain rounded-xl shrink-0">
            <div>
                <span class="block text-sm font-extrabold text-slate-900 leading-tight">{{ $exam->nama_ujian }}</span>
                <span class="block text-[11px] text-slate-500 font-semibold">{{ $exam->subject->nama_mapel ?? '-' }} • Kelas {{ $exam->target_kelas }}</span>
            </div>
        </div>

        <!-- Timer Countdown & Mobile Palette Trigger -->
        <div class="flex items-center gap-2 sm:gap-3">
            <!-- Tombol Navigasi Soal Khusus Ponsel & Tablet -->
            <button type="button" onclick="openModal('mobilePaletteModal')" class="md:hidden px-2.5 py-1.5 rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1.5 transition-colors" title="Buka Daftar Soal">
                <i class="fa-solid fa-list-ol text-brand-600"></i>
                <span class="font-mono text-[11px]" id="mobileAnsweredCounter">0/0</span>
            </button>

            <div class="flex items-center gap-2 bg-slate-900 text-white px-3 sm:px-4 py-1.5 rounded-xl font-mono text-xs sm:text-sm font-extrabold shadow-inner" id="timerBadge">
                <i class="fa-solid fa-clock text-amber-400"></i>
                <span id="countdownDisplay">--:--:--</span>
            </div>

            <button type="button" onclick="confirmSubmitExam()" class="px-3 sm:px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow transition-colors flex items-center gap-1.5">
                <i class="fa-solid fa-check"></i>
                <span class="hidden sm:inline">Selesai Ujian</span>
                <span class="sm:hidden">Selesai</span>
            </button>
        </div>
    </header>

    <!-- CBT Main Workspace -->
    <div class="flex-1 flex overflow-hidden">

        <!-- Left Area: Question Display -->
        <main class="flex-1 flex flex-col bg-slate-50 overflow-y-auto p-4 sm:p-8">
            <div class="max-w-3xl w-full mx-auto flex-1 flex flex-col justify-between space-y-6">
                
                <!-- Question Card -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6" id="questionCard">
                    <!-- Question Header -->
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Soal Nomor</span>
                            <span class="w-8 h-8 rounded-xl bg-brand-600 text-white font-extrabold font-mono text-sm flex items-center justify-center" id="currentNumberBadge">1</span>
                        </div>
                        <div class="text-xs text-slate-400">
                            Pilihlah salah satu jawaban yang paling tepat
                        </div>
                    </div>

                    <!-- Question Text & Image Stimulus -->
                    <div class="space-y-4">
                        <div class="text-base sm:text-lg font-semibold text-slate-900 leading-relaxed" id="questionText">
                            Memuat butir pertanyaan...
                        </div>
                        <div id="questionImageWrap" class="hidden">
                            <img id="questionImage" src="" alt="Stimulus Soal" onclick="zoomImage(this.src)" class="max-h-72 rounded-2xl border border-slate-200 shadow-sm object-contain bg-slate-50 p-1 cursor-zoom-in hover:opacity-95" title="Klik untuk memperbesar">
                        </div>
                    </div>

                    <!-- Options List -->
                    <div class="space-y-3 pt-2" id="optionsContainer">
                        <!-- Rendered by JS -->
                    </div>
                </div>

                <!-- Navigation Controls -->
                <div class="flex items-center justify-between gap-3 pt-2">
                    <button type="button" id="btnPrev" onclick="navigateQuestion(-1)" class="px-5 py-3 rounded-2xl bg-white border border-slate-200 text-slate-700 font-bold text-xs shadow-sm hover:bg-slate-50 flex items-center gap-2 transition-colors">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Sebelumnya</span>
                    </button>

                    <div class="flex items-center gap-2">
                        <button type="button" id="btnRagu" onclick="toggleRaguRagu()" class="px-4 py-3 rounded-2xl border border-amber-300 bg-amber-50 text-amber-700 font-bold text-xs hover:bg-amber-100 flex items-center gap-1.5 transition-colors">
                            <i class="fa-solid fa-flag"></i>
                            <span id="textRagu">Ragu-Ragu</span>
                        </button>
                    </div>

                    <button type="button" id="btnNext" onclick="navigateQuestion(1)" class="px-5 py-3 rounded-2xl bg-brand-600 text-white font-bold text-xs shadow-md shadow-blue-500/20 hover:bg-brand-700 flex items-center gap-2 transition-colors">
                        <span>Berikutnya</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>

            </div>
        </main>

        <!-- Right Area: Question Number Palette (Collapsible on mobile) -->
        <aside class="w-72 bg-white border-l border-slate-200 p-5 flex flex-col justify-between shrink-0 hidden md:flex">
            <div class="space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Navigasi Butir Soal</h3>
                    <span class="text-xs font-mono font-bold text-brand-600" id="answeredCounter">0 / 0</span>
                </div>

                <!-- Legend -->
                <div class="grid grid-cols-2 gap-2 text-[10px] text-slate-500 pb-2">
                    <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-500"></span> Dijawab</div>
                    <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-200"></span> Belum</div>
                    <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-400"></span> Ragu-Ragu</div>
                    <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded border-2 border-brand-600"></span> Aktif</div>
                </div>

                <!-- Number Grid -->
                <div class="grid grid-cols-5 gap-2 max-h-[calc(100vh-280px)] overflow-y-auto pr-1" id="paletteGrid">
                    <!-- Rendered by JS -->
                </div>
            </div>

            <!-- Identitas Siswa Footer -->
            <div class="pt-4 border-t border-slate-100 text-xs text-slate-500 space-y-1">
                <div class="font-bold text-slate-800">{{ $siswa->nama_lengkap }}</div>
                <div class="font-mono text-[11px]">NIS: {{ $siswa->username }} • Kelas {{ $siswa->kelas }}</div>
            </div>
        </aside>

    </div>

    <!-- Modal Daftar Soal untuk Ponsel & Tablet -->
    <div id="mobilePaletteModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="bg-white rounded-3xl p-6 max-w-sm w-full shadow-2xl space-y-4 my-auto relative animate-fadeIn" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">Navigasi Butir Soal</h3>
                    <span class="text-xs text-brand-600 font-mono font-bold" id="mobilePaletteAnswered">0 / 0 Dijawab</span>
                </div>
                <button type="button" onclick="closeModal('mobilePaletteModal')" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Legend -->
            <div class="grid grid-cols-2 gap-2 text-[10px] text-slate-500 pb-1">
                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-500"></span> Dijawab</div>
                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-200"></span> Belum</div>
                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-400"></span> Ragu-Ragu</div>
                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded border-2 border-brand-600"></span> Aktif</div>
            </div>

            <!-- Mobile Number Grid -->
            <div class="grid grid-cols-5 gap-2 max-h-[50vh] overflow-y-auto pr-1" id="mobilePaletteGrid">
                <!-- Rendered by JS -->
            </div>

            <div class="pt-2">
                <button type="button" onclick="closeModal('mobilePaletteModal')" class="w-full py-2.5 rounded-xl bg-slate-900 text-white font-bold text-xs">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    const examId = {{ $exam->id }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    let questionList = [];
    let currentIndex = 0;
    let studentAnswers = {};
    let raguList = {};
    let remainingSeconds = 0;
    let timerInterval = null;
    let heartbeatInterval = null;
    let cheatViolations = 0;

    // Inisialisasi CBT
    document.addEventListener('DOMContentLoaded', function () {
        loadExamQuestions();
        setupAntiCheat();
    });

    function loadExamQuestions() {
        fetch(`/siswa/exam/${examId}/get-soal`)
            .then(res => res.json())
            .then(data => {
                questionList = data.soal || [];
                remainingSeconds = data.sisa_waktu || 3600;

                // Sync existing answers
                questionList.forEach((q, idx) => {
                    if (q.jawaban_siswa) {
                        studentAnswers[q.id] = q.jawaban_siswa;
                    }
                });

                startTimer();
                renderPalette();
                displayQuestion(currentIndex);
            })
            .catch(err => {
                Swal.fire('Kesalahan', 'Gagal memuat naskah soal. Silakan muat ulang halaman.', 'error');
            });
    }

    function startTimer() {
        updateTimerDisplay();
        timerInterval = setInterval(() => {
            remainingSeconds--;
            updateTimerDisplay();

            if (remainingSeconds <= 0) {
                clearInterval(timerInterval);
                clearInterval(heartbeatInterval);
                autoSubmitExam();
            }
        }, 1000);

        // Heartbeat sinkronisasi waktu ke server setiap 15 detik
        heartbeatInterval = setInterval(() => {
            fetch(`/siswa/exam/${examId}/update-timer`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ sisa_waktu: remainingSeconds })
            });
        }, 15000);
    }

    function updateTimerDisplay() {
        const h = Math.floor(remainingSeconds / 3600);
        const m = Math.floor((remainingSeconds % 3600) / 60);
        const s = remainingSeconds % 60;
        const formatted = [h, m, s].map(v => v.toString().padStart(2, '0')).join(':');
        document.getElementById('countdownDisplay').innerText = formatted;

        if (remainingSeconds < 300) {
            document.getElementById('timerBadge').classList.remove('bg-slate-900');
            document.getElementById('timerBadge').classList.add('bg-rose-600', 'animate-pulse');
        }
    }

    function displayQuestion(index) {
        if (!questionList.length) {
            document.getElementById('questionText').innerHTML = `
                <div class="p-8 text-center text-slate-500 space-y-2">
                    <i class="fa-solid fa-folder-open text-4xl text-slate-300"></i>
                    <p class="font-bold text-slate-800 text-base">Belum Ada Soal yang Terhubung</p>
                    <p class="text-xs text-slate-400">Naskah butir soal untuk mata pelajaran ujian ini belum tersedia atau belum dirilis. Silakan laporkan kepada Proktor / Guru Pengawas.</p>
                </div>
            `;
            document.getElementById('currentNumberBadge').innerText = '0';
            return;
        }

        currentIndex = index;
        const q = questionList[index];

        document.getElementById('currentNumberBadge').innerText = index + 1;
        document.getElementById('questionText').innerHTML = q.pertanyaan.replace(/\n/g, '<br>');

        const imgWrap = document.getElementById('questionImageWrap');
        const img = document.getElementById('questionImage');
        if (q.img_pertanyaan) {
            img.src = q.img_pertanyaan;
            imgWrap.classList.remove('hidden');
        } else {
            imgWrap.classList.add('hidden');
        }

        // Render Options A..E
        const container = document.getElementById('optionsContainer');
        container.innerHTML = '';

        const currentChoice = studentAnswers[q.id] || null;
        const options = [
            { key: 'A', text: q.pil_a, img: q.img_a },
            { key: 'B', text: q.pil_b, img: q.img_b },
            { key: 'C', text: q.pil_c, img: q.img_c },
            { key: 'D', text: q.pil_d, img: q.img_d },
            { key: 'E', text: q.pil_e, img: q.img_e },
        ];

        options.forEach(opt => {
            if (!opt.text && !opt.img) return;

            const isSelected = currentChoice === opt.key;
            const card = document.createElement('div');
            card.className = `p-4 rounded-2xl border-2 cursor-pointer flex items-center gap-4 transition-all ${isSelected ? 'border-brand-600 bg-blue-50/70 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300'}`;
            card.onclick = () => selectAnswer(q.id, opt.key);

            card.innerHTML = `
                <div class="w-8 h-8 rounded-xl font-mono font-extrabold text-sm flex items-center justify-center shrink-0 ${isSelected ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700'}">
                    ${opt.key}
                </div>
                <div class="flex-1 text-sm font-medium text-slate-800">
                    ${opt.text ? opt.text : ''}
                    ${opt.img ? `<img src="${opt.img}" onclick="event.stopPropagation(); zoomImage('${opt.img}')" class="max-h-32 mt-2 rounded-lg border border-slate-200 cursor-zoom-in hover:opacity-95" title="Klik untuk memperbesar">` : ''}
                </div>
            `;
            container.appendChild(card);
        });

        // Update ragu state
        const isRagu = raguList[q.id] || false;
        const btnRagu = document.getElementById('btnRagu');
        if (isRagu) {
            btnRagu.classList.add('bg-amber-400', 'text-slate-900', 'border-amber-500');
            btnRagu.classList.remove('bg-amber-50', 'text-amber-700');
        } else {
            btnRagu.classList.remove('bg-amber-400', 'text-slate-900', 'border-amber-500');
            btnRagu.classList.add('bg-amber-50', 'text-amber-700');
        }

        // Toggle buttons prev/next
        document.getElementById('btnPrev').disabled = (index === 0);
        document.getElementById('btnNext').disabled = (index === questionList.length - 1);

        renderPalette();
    }

    function selectAnswer(questionId, choice) {
        studentAnswers[questionId] = choice;

        // Kirim jawaban secara instan via AJAX ke server
        fetch(`/siswa/exam/${examId}/save-answer`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                soal_id: questionId,
                jawaban: choice,
                sisa_waktu: remainingSeconds
            })
        });

        displayQuestion(currentIndex);
    }

    function toggleRaguRagu() {
        const q = questionList[currentIndex];
        raguList[q.id] = !raguList[q.id];
        displayQuestion(currentIndex);
    }

    function navigateQuestion(offset) {
        const nextIdx = currentIndex + offset;
        if (nextIdx >= 0 && nextIdx < questionList.length) {
            displayQuestion(nextIdx);
        }
    }

    function renderPalette() {
        const grid = document.getElementById('paletteGrid');
        const mobileGrid = document.getElementById('mobilePaletteGrid');
        if (grid) grid.innerHTML = '';
        if (mobileGrid) mobileGrid.innerHTML = '';

        let answeredCount = 0;

        questionList.forEach((q, idx) => {
            const hasAnswer = studentAnswers[q.id];
            const isRagu = raguList[q.id];
            const isCurrent = (idx === currentIndex);

            if (hasAnswer) answeredCount++;

            const btnClass = `h-10 rounded-xl font-bold font-mono text-xs flex items-center justify-center transition-all ${
                isCurrent ? 'ring-2 ring-brand-600 scale-105' : ''
            } ${
                isRagu ? 'bg-amber-400 text-slate-900' : (hasAnswer ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200')
            }`;

            if (grid) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = btnClass;
                btn.innerText = idx + 1;
                btn.onclick = () => displayQuestion(idx);
                grid.appendChild(btn);
            }

            if (mobileGrid) {
                const mBtn = document.createElement('button');
                mBtn.type = 'button';
                mBtn.className = btnClass;
                mBtn.innerText = idx + 1;
                mBtn.onclick = () => {
                    displayQuestion(idx);
                    closeModal('mobilePaletteModal');
                };
                mobileGrid.appendChild(mBtn);
            }
        });

        const counterText = `${answeredCount} / ${questionList.length}`;
        const answeredCounter = document.getElementById('answeredCounter');
        if (answeredCounter) answeredCounter.innerText = counterText;

        const mobileAnsweredCounter = document.getElementById('mobileAnsweredCounter');
        if (mobileAnsweredCounter) mobileAnsweredCounter.innerText = `${answeredCount}/${questionList.length}`;

        const mobilePaletteAnswered = document.getElementById('mobilePaletteAnswered');
        if (mobilePaletteAnswered) mobilePaletteAnswered.innerText = `${counterText} Dijawab`;
    }

    function confirmSubmitExam() {
        // Aturan Khusus: Ujian hanya bisa disubmit ketika waktu kurang dari 15 menit (900 detik)
        const minSubmitSeconds = 15 * 60; // 900 detik = 15 menit
        if (remainingSeconds > minSubmitSeconds) {
            const menitTersisa = Math.ceil(remainingSeconds / 60);
            const menitTunggu = Math.ceil((remainingSeconds - minSubmitSeconds) / 60);
            Swal.fire({
                icon: 'warning',
                title: 'Belum Dapat Diselesaikan',
                html: `Sesuai tata tertib CBT, Anda baru diperbolehkan mengumpulkan ujian ketika <b>sisa waktu kurang dari 15 menit</b>.<br><br>Sisa waktu saat ini: <b>${menitTersisa} Menit</b>.<br>Silakan periksa kembali jawaban Anda (baru dapat dikumpulkan dalam <b>${menitTunggu} menit</b> ke depan).`,
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'Lanjutkan Memeriksa Jawaban'
            });
            return;
        }

        const answeredCount = Object.keys(studentAnswers).length;
        const totalCount = questionList.length;
        const unanswered = totalCount - answeredCount;

        let warningText = `Anda telah menjawab ${answeredCount} dari ${totalCount} butir soal.`;
        if (unanswered > 0) {
            warningText += ` Masih ada ${unanswered} butir soal yang belum Anda jawab!`;
        }

        Swal.fire({
            title: 'Selesaikan Ujian Sekarang?',
            text: warningText,
            icon: unanswered > 0 ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Kumpulkan Jawaban',
            cancelButtonText: 'Kembali Periksa'
        }).then((result) => {
            if (result.isConfirmed) {
                submitExam();
            }
        });
    }

    function submitExam() {
        isExamFinished = true; // Tandai bahwa siswa sudah submit, cegah trigger anti-cheat saat navigasi

        Swal.fire({
            title: 'Memproses Penilaian...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        fetch(`/siswa/exam/${examId}/submit`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = data.redirect;
            } else {
                isExamFinished = false; // Kembalikan jika gagal submit
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Mengumpulkan',
                    text: data.message || 'Terjadi kesalahan saat memproses jawaban ujian.'
                });
            }
        })
        .catch(err => {
            isExamFinished = false;
            Swal.fire('Error', 'Gagal mengirim ujian. Silakan periksa koneksi Anda.', 'error');
        });
    }

    function autoSubmitExam() {
        isExamFinished = true; // Tandai ujian selesai

        Swal.fire({
            title: 'Waktu Ujian Telah Habis!',
            text: 'Jawaban Anda otomatis dikumpulkan oleh sistem.',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false
        }).then(() => {
            // Bypass countdown check on auto submit
            fetch(`/siswa/exam/${examId}/submit`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
            .then(res => res.json())
            .then(data => {
                if (data.redirect) window.location.href = data.redirect;
            })
            .catch(() => {
                window.location.href = '{{ route("siswa.dashboard") }}';
            });
        });
    }

    // Sistem Blokir Tanpa Toleransi (1 Kali Melanggar Langsung Blokir)
    let isBlockedTriggered = false;
    let isExamFinished = false;

    function triggerZeroToleranceBlock(alasan) {
        // Jangan blokir jika ujian sudah selesai atau sedang dalam proses submit
        if (isExamFinished || isBlockedTriggered) return;
        isBlockedTriggered = true;

        clearInterval(timerInterval);
        clearInterval(heartbeatInterval);

        fetch(`/siswa/exam/${examId}/report-cheating`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ alasan: alasan || 'Keluar dari halaman ujian' })
        })
        .then(res => res.json())
        .then(data => {
            Swal.fire({
                icon: 'error',
                title: 'Ujian Terblokir!',
                html: `<b>Kebijakan Toleransi Nol (Zero Tolerance):</b><br>Anda terdeteksi melakukan pelanggaran: <b>${alasan}</b>.<br><br>Sesi ujian Anda telah diblokir secara otomatis oleh sistem (1x pelanggaran langsung terkunci).<br><br>Silakan menghadap ke <b>Proktor / Pengawas Ruang</b> untuk membuka blokir.`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: true,
                confirmButtonText: 'Kembali ke Dashboard',
                confirmButtonColor: '#dc2626'
            }).then(() => {
                window.location.href = '{{ route("siswa.dashboard") }}';
            });
        })
        .catch(() => {
            window.location.href = '{{ route("siswa.dashboard") }}';
        });
    }

    function setupAntiCheat() {
        // Cegah pengiriman laporan kecurangan saat halaman ditutup atau berpindah karena selesai ujian
        window.addEventListener('beforeunload', function () {
            isExamFinished = true;
        });

        // 1. Deteksi perpindahan jendela atau kehilangan fokus (Alt+Tab, klik luar)
        window.addEventListener('blur', function () {
            if (isExamFinished) return;
            triggerZeroToleranceBlock('Berpindah jendela / kehilangan fokus aplikasi');
        });

        // 2. Deteksi tab tersembunyi (buka tab baru, minimize)
        document.addEventListener('visibilitychange', function () {
            if (isExamFinished) return;
            if (document.visibilityState === 'hidden') {
                triggerZeroToleranceBlock('Berpindah tab atau browser diminimize');
            }
        });

        // 3. Blokir klik kanan
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
            return false;
        });

        // 4. Blokir tombol inspeksi element / shortcuts terlarang
        document.addEventListener('keydown', function (e) {
            if (
                e.key === 'F12' ||
                (e.ctrlKey && e.shiftKey && ['I', 'i', 'J', 'j', 'C', 'c'].includes(e.key)) ||
                (e.ctrlKey && ['U', 'u', 'S', 's', 'W', 'w'].includes(e.key))
            ) {
                e.preventDefault();
                triggerZeroToleranceBlock('Menekan kombinasi tombol terlarang (Inspect/Developer Tools)');
                return false;
            }
        });
    }

    function zoomImage(src) {
        if (!src) return;
        Swal.fire({
            imageUrl: src,
            imageAlt: 'Perbesar Gambar',
            showConfirmButton: true,
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#2563eb',
            customClass: { popup: 'rounded-3xl p-4' }
        });
    }
</script>
@endpush
@endsection

