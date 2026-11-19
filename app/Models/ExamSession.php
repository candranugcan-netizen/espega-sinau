<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    protected $fillable = [
        'exam_id',
        'user_id',
        'skor',
        'status_siswa',
        'jumlah_blokir',
        'mulai_ujian',
        'selesai_ujian',
        'sisa_waktu',
    ];

    protected $casts = [
        'mulai_ujian' => 'datetime',
        'selesai_ujian' => 'datetime',
        'skor' => 'decimal:2',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'session_id');
    }

    /**
     * Hitung skor dan selesaikan ujian
     */
    public function finalize(): bool
    {
        if ($this->status_siswa === 'selesai') {
            return false;
        }

        $exam = $this->exam;
        $user = $this->user;
        $kelas = $user->kelas;

        // Ambil semua soal terkait ujian ini secara presisi dan konsisten
        $questions = $exam->getQuestions($kelas);

        $totalSoal = $questions->count();
        $studentAnswers = $this->answers()->pluck('jawaban_siswa', 'question_id')->toArray();

        $benar = 0;
        foreach ($questions as $q) {
            $kunci = strtoupper(trim($q->kunci_jawaban ?? ''));
            $jawab = isset($studentAnswers[$q->id]) ? strtoupper(trim($studentAnswers[$q->id])) : '';

            $isCorrect = ($jawab !== '' && $jawab === $kunci);
            if ($isCorrect) {
                $benar++;
            }

            ExamAnswer::updateOrCreate(
                ['session_id' => $this->id, 'question_id' => $q->id],
                ['jawaban_siswa' => $jawab ?: null, 'is_correct' => $isCorrect]
            );
        }

        $skor = ($totalSoal > 0) ? round(($benar / $totalSoal) * 100, 2) : 0;

        $this->update([
            'skor' => $skor,
            'status_siswa' => 'selesai',
            'selesai_ujian' => now(),
            'sisa_waktu' => 0,
        ]);

        return true;
    }
}

