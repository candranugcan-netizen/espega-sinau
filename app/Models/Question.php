<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'subject_id',
        'bank_soal_id',
        'urutan',
        'kelas',
        'pertanyaan',
        'img_pertanyaan',
        'pil_a',
        'img_a',
        'pil_b',
        'img_b',
        'pil_c',
        'img_c',
        'pil_d',
        'img_d',
        'pil_e',
        'img_e',
        'kunci_jawaban',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id');
    }

    public function examAnswers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'question_id');
    }

    /**
     * Helper to resolve image URL with backward compatibility
     */
    public function getImageUrl(?string $filename): ?string
    {
        if (empty($filename)) return null;

        if (str_starts_with($filename, 'http://') || str_starts_with($filename, 'https://')) {
            return $filename;
        }

        // Check if file exists in public/uploads/soal or espega_old/assets/img/soal
        return asset('assets/img/soal/' . $filename);
    }
}

