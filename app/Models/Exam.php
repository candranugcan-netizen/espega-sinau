<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = [
        'academic_year_id',
        'nama_ujian',
        'target_kelas',
        'subject_id',
        'bank_soal_id',
        'waktu_mulai',
        'waktu_selesai',
        'durasi',
        'token_ujian',
    ];

    protected static function booted()
    {
        static::creating(function ($exam) {
            if (empty($exam->academic_year_id)) {
                $exam->academic_year_id = AcademicYear::getActive()?->id;
            }
        });
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class, 'exam_id');
    }

    /**
     * Hitung waktu akhir ujian yang presisi
     */
    public function getEndTimeAttribute(): Carbon
    {
        if ($this->waktu_selesai && $this->waktu_selesai->year > 2000) {
            return $this->waktu_selesai;
        }

        $mulai = $this->waktu_mulai ?: now();
        $durasi = $this->durasi ?: 90;
        return (clone $mulai)->addMinutes($durasi);
    }

    /**
     * Memeriksa apakah ujian ini ditujukan untuk kelas tertentu
     */
    public function matchesClass(?string $kelas): bool
    {
        if (empty($kelas)) {
            return false;
        }

        $target = trim($this->target_kelas ?? '');
        $kelas = trim($kelas);

        if (empty($target)) {
            return true;
        }

        $upperTarget = strtoupper($target);
        $upperKelas = strtoupper($kelas);

        // Jika ujian untuk semua kelas
        if (in_array($upperTarget, ['ALL', 'SEMUA', 'SEMUA KELAS', '*'])) {
            return true;
        }

        if ($upperTarget === $upperKelas) {
            return true;
        }

        // Jika multiple kelas dipisah koma atau spasi atau titik koma (misal: "7A, 7B, 7C")
        $parts = array_filter(array_map('trim', preg_split('/[,\s;]+/', $upperTarget)));
        if (in_array($upperKelas, $parts)) {
            return true;
        }

        // Substring match misal target "KELAS 7A" dengan kelas "7A"
        if (str_contains($upperTarget, $upperKelas)) {
            return true;
        }

        return false;
    }

    public function isActive(): bool
    {
        $now = now();
        $start = $this->waktu_mulai;
        $end = $this->end_time;

        if (!$start) return false;
        return $now->greaterThanOrEqualTo($start) && $now->lessThan($end);
    }

    public function isUpcoming(): bool
    {
        $now = now();
        return $this->waktu_mulai ? $now->lessThan($this->waktu_mulai) : false;
    }

    public function isFinished(): bool
    {
        $now = now();
        return $now->greaterThanOrEqualTo($this->end_time);
    }

    /**
     * Ambil butir soal untuk ujian ini secara presisi tanpa duplikasi antar-bank
     */
    public function getQuestions(?string $kelas = null)
    {
        $targetKelas = $this->target_kelas;
        $cleanKelas = trim($kelas ?? '');
        $evalKelas = !empty($cleanKelas) ? $cleanKelas : $targetKelas;

        // 1. Jika ujian terikat langsung ke paket Bank Soal tertentu, gunakan soal dari bank tersebut
        if ($this->bank_soal_id) {
            $bankQuestions = Question::where('bank_soal_id', $this->bank_soal_id)
                ->orderByRaw('(CASE WHEN urutan > 0 THEN urutan ELSE 9999999 END) ASC, id ASC')
                ->get();

            if ($bankQuestions->isNotEmpty()) {
                return $bankQuestions;
            }
        }

        // 2. Jika bank_soal_id belum terisi (legacy), cari 1 paket Bank Soal yang rilis untuk kelas ini
        $subjectId = $this->subject_id;
        $candidateBanks = BankSoal::where('subject_id', $subjectId)
            ->where('status_rilis', 'rilis')
            ->get();

        $matchedBank = $candidateBanks->first(function ($b) use ($evalKelas) {
            return $b->matchesClass($evalKelas);
        });

        if ($matchedBank) {
            return Question::where('bank_soal_id', $matchedBank->id)
                ->orderByRaw('(CASE WHEN urutan > 0 THEN urutan ELSE 9999999 END) ASC, id ASC')
                ->get();
        }

        // 3. Jika tidak ada yang rilis spesifik, cari paket pertama yang relevan untuk mapel ini
        // Mencegah penggabungan seluruh soal dari guru yang berbeda (misal 30 + 30 = 60)
        $firstBank = BankSoal::where('subject_id', $subjectId)
            ->withCount('questions')
            ->having('questions_count', '>', 0)
            ->first();

        if ($firstBank) {
            return Question::where('bank_soal_id', $firstBank->id)
                ->orderByRaw('(CASE WHEN urutan > 0 THEN urutan ELSE 9999999 END) ASC, id ASC')
                ->get();
        }

        // 4. Fallback jika soal tidak dikelompokkan ke bank_soal_id
        return Question::where('subject_id', $subjectId)
            ->whereNull('bank_soal_id')
            ->orderByRaw('(CASE WHEN urutan > 0 THEN urutan ELSE 9999999 END) ASC, id ASC')
            ->get();
    }
}

