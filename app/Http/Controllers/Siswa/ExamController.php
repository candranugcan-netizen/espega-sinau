<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamSession;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamController extends Controller
{
    /**
     * Verifikasi Token Ujian sebelum masuk
     */
    public function verifyToken(Request $request, Exam $exam)
    {
        $siswa = Auth::user();

        if (!$exam->matchesClass($siswa->kelas)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ujian ini ditujukan khusus untuk kelas ' . $exam->target_kelas . ' (Kelas Anda: ' . ($siswa->kelas ?: '-') . ').',
            ], 403);
        }

        $inputToken = strtoupper(trim($request->input('token', '')));

        if ($inputToken !== strtoupper(trim($exam->token_ujian))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token ujian tidak valid! Pastikan Anda memasukkan token yang benar dari pengawas.',
            ], 422);
        }

        $now = now();
        // Beri toleransi masuk 5 menit sebelum waktu mulai resmi
        if ($exam->waktu_mulai && $now->lessThan($exam->waktu_mulai->copy()->subMinutes(5))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ujian belum dimulai! Jadwal ujian dibuka pukul ' . $exam->waktu_mulai->format('H:i') . ' WIB.',
            ], 422);
        }

        if ($now->greaterThanOrEqualTo($exam->end_time)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Waktu pelaksanaan ujian ini telah berakhir.',
            ], 422);
        }

        // Cari atau buat sesi pengerjaan siswa
        $session = ExamSession::firstOrCreate(
            ['exam_id' => $exam->id, 'user_id' => $siswa->id],
            [
                'status_siswa' => 'pengerjaan',
                'mulai_ujian' => now(),
                'sisa_waktu' => ($exam->durasi ?: 90) * 60,
                'skor' => 0,
            ]
        );

        if ($session->status_siswa === 'selesai') {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda telah menyelesaikan ujian ini.',
                'redirect' => route('siswa.exam.result', $exam),
            ]);
        }

        if ($session->status_siswa === 'terblokir') {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi ujian Anda terkunci karena terindikasi keluar dari aplikasi. Hubungi pengawas/proktor untuk membuka blokir.',
            ]);
        }

        // Set status pengerjaan jika masih 'belum'
        if ($session->status_siswa === 'belum') {
            $session->update([
                'status_siswa' => 'pengerjaan',
                'mulai_ujian' => now(),
                'sisa_waktu' => ($exam->durasi ?: 90) * 60,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'redirect' => route('siswa.exam.show', $exam),
        ]);
    }

    /**
     * Halaman Utama Antarmuka CBT
     */
    public function show(Exam $exam)
    {
        $siswa = Auth::user();

        $session = ExamSession::where('exam_id', $exam->id)
            ->where('user_id', $siswa->id)
            ->first();

        if (!$session || $session->status_siswa === 'belum') {
            return redirect()->route('siswa.dashboard')->with('error', 'Masukkan token ujian terlebih dahulu.');
        }

        if ($session->status_siswa === 'selesai') {
            return redirect()->route('siswa.exam.result', $exam);
        }

        if ($session->status_siswa === 'terblokir') {
            return redirect()->route('siswa.dashboard')->with('error', 'Sesi Anda terblokir. Hubungi Pengawas untuk membukanya.');
        }

        // Cek jika sisa durasi siswa sudah habis
        if ($session->sisa_waktu !== null && (int)$session->sisa_waktu <= 0) {
            $session->finalize();
            return redirect()->route('siswa.exam.result', $exam)->with('info', 'Waktu pengerjaan telah habis. Jawaban Anda otomatis tersimpan.');
        }

        return view('siswa.exam.cbt', compact('exam', 'session', 'siswa'));
    }

    /**
     * Endpoint API: Ambil Butir Soal dan Jawaban Siswa
     */
    public function getSoal(Exam $exam)
    {
        $siswa = Auth::user();
        $kelas = $siswa->kelas;

        $session = ExamSession::where('exam_id', $exam->id)
            ->where('user_id', $siswa->id)
            ->firstOrFail();

        // Ambil soal yang sesuai untuk ujian dan kelas siswa menggunakan helper model yang cerdas
        $questions = $exam->getQuestions($kelas);

        $savedAnswers = ExamAnswer::where('session_id', $session->id)
            ->pluck('jawaban_siswa', 'question_id')
            ->toArray();

        $payload = $questions->map(function ($q) use ($savedAnswers) {
            return [
                'id' => $q->id,
                'urutan' => $q->urutan,
                'pertanyaan' => $q->pertanyaan,
                'img_pertanyaan' => $q->getImageUrl($q->img_pertanyaan),
                'pil_a' => $q->pil_a,
                'img_a' => $q->getImageUrl($q->img_a),
                'pil_b' => $q->pil_b,
                'img_b' => $q->getImageUrl($q->img_b),
                'pil_c' => $q->pil_c,
                'img_c' => $q->getImageUrl($q->img_c),
                'pil_d' => $q->pil_d,
                'img_d' => $q->getImageUrl($q->img_d),
                'pil_e' => $q->pil_e,
                'img_e' => $q->getImageUrl($q->img_e),
                'jawaban_siswa' => $savedAnswers[$q->id] ?? null,
            ];
        });

        return response()->json([
            'soal' => $payload,
            'sisa_waktu' => $session->sisa_waktu ?? (($exam->durasi ?: 90) * 60),
            'total_soal' => $questions->count(),
        ]);
    }

    /**
     * Endpoint API: Simpan Jawaban Pilihan Siswa
     */
    public function saveAnswer(Request $request, Exam $exam)
    {
        $siswa = Auth::user();
        $session = ExamSession::where('exam_id', $exam->id)
            ->where('user_id', $siswa->id)
            ->firstOrFail();

        if ($session->status_siswa === 'selesai' || $session->status_siswa === 'terblokir') {
            return response()->json(['status' => 'error', 'message' => 'Sesi tidak aktif.'], 403);
        }

        $soalId = $request->input('soal_id');
        $jawaban = strtoupper(trim($request->input('jawaban', '')));
        $sisaWaktu = $request->input('sisa_waktu');

        ExamAnswer::updateOrCreate(
            ['session_id' => $session->id, 'question_id' => $soalId],
            ['jawaban_siswa' => $jawaban ?: null]
        );

        if ($sisaWaktu !== null) {
            $session->update(['sisa_waktu' => (int)$sisaWaktu]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Endpoint API: Update Heartbeat Timer
     */
    public function updateTimer(Request $request, Exam $exam)
    {
        $siswa = Auth::user();
        $session = ExamSession::where('exam_id', $exam->id)
            ->where('user_id', $siswa->id)
            ->firstOrFail();

        $sisaWaktu = (int)$request->input('sisa_waktu', 0);
        $session->update(['sisa_waktu' => $sisaWaktu]);

        if ($sisaWaktu <= 0) {
            $session->finalize();
            return response()->json(['status' => 'auto_submitted', 'redirect' => route('siswa.exam.result', $exam)]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Selesaikan Ujian & Hitung Skor
     */
    public function submit(Request $request, Exam $exam)
    {
        $siswa = Auth::user();
        $session = ExamSession::where('exam_id', $exam->id)
            ->where('user_id', $siswa->id)
            ->firstOrFail();

        // Validasi aturan: ujian hanya bisa disubmit ketika sisa waktu kurang dari 15 menit (900 detik)
        $minSubmitSeconds = 15 * 60;
        if ($session->sisa_waktu !== null && (int)$session->sisa_waktu > $minSubmitSeconds) {
            $menitTersisa = ceil((int)$session->sisa_waktu / 60);
            return response()->json([
                'status' => 'error',
                'message' => "Ujian belum dapat diselesaikan. Pengumpulan jawaban baru diperbolehkan ketika sisa waktu ujian kurang dari 15 menit (Sisa waktu Anda saat ini: {$menitTersisa} menit).",
            ], 422);
        }

        $session->finalize();

        // Pastikan akun siswa tetap aktif (tidak terblokir) setelah ujian selesai dikumpulkan
        if ($siswa && $siswa->status_blokir === '1') {
            $siswa->update(['status_blokir' => '0']);
        }

        return response()->json([
            'status' => 'success',
            'redirect' => route('siswa.exam.result', $exam),
        ]);
    }

    /**
     * Halaman Konfirmasi Penyelesaian Ujian (Skor Disembunyikan Sesuai Kebijakan)
     */
    public function result(Exam $exam)
    {
        $siswa = Auth::user();
        $session = ExamSession::with('answers')
            ->where('exam_id', $exam->id)
            ->where('user_id', $siswa->id)
            ->firstOrFail();

        // Pulihkan status blokir siswa jika sebelumnya sempat terpicu saat submit
        if ($siswa && $siswa->status_blokir === '1') {
            $siswa->update(['status_blokir' => '0']);
        }

        $questions = $exam->getQuestions($siswa->kelas);
        $totalSoal = $questions->count();
        $totalDijawab = $session->answers()->whereNotNull('jawaban_siswa')->count();

        return view('siswa.exam.result', compact('exam', 'session', 'siswa', 'totalSoal', 'totalDijawab'));
    }

    /**
     * Laporkan Pelanggaran Anti-Cheating (Toleransi Nol: 1x Langsung Blokir)
     */
    public function reportCheating(Request $request, Exam $exam)
    {
        $siswa = Auth::user();
        $session = ExamSession::where('exam_id', $exam->id)
            ->where('user_id', $siswa->id)
            ->first();

        if (!$session) {
            return response()->json(['status' => 'ignored'], 200);
        }

        // PENTING: Jika status ujian sudah selesai, jangan pernah memblokir siswa
        if ($session->status_siswa === 'selesai') {
            return response()->json([
                'status' => 'ignored',
                'message' => 'Ujian telah selesai.',
            ]);
        }

        // Hanya proses pemblokiran jika sesi masih dalam proses pengerjaan
        if ($session->status_siswa === 'pengerjaan') {
            $session->increment('jumlah_blokir');
            $session->update(['status_siswa' => 'terblokir']);

            if ($siswa) {
                $siswa->update(['status_blokir' => '1']);
            }

            return response()->json([
                'status' => 'blocked',
                'jumlah_blokir' => $session->jumlah_blokir,
                'message' => 'Layar ujian terkunci otomatis karena terindikasi meninggalkan halaman CBT (Sistem Toleransi Nol). Silakan hubungi proktor.',
            ]);
        }

        return response()->json(['status' => 'ignored']);
    }
}

