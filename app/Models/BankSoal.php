<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankSoal extends Model
{
    protected $table = 'bank_soal';

    protected $fillable = [
        'academic_year_id',
        'nama_bank',
        'subject_id',
        'guru_id',
        'kelas_rilis',
        'status_rilis',
    ];

    protected static function booted()
    {
        static::creating(function ($bank) {
            if (empty($bank->academic_year_id)) {
                $bank->academic_year_id = AcademicYear::getActive()?->id;
            }
        });
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'bank_soal_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'bank_soal_id');
    }

    public function isReleased(): bool
    {
        return $this->status_rilis === 'rilis';
    }

    /**
     * Ambil array daftar kelas tempat bank soal ini dirilis
     */
    public function getReleasedClassesList(): array
    {
        if (empty($this->kelas_rilis)) {
            return [];
        }

        $parts = preg_split('/[,\s;]+/', strtoupper(trim($this->kelas_rilis)));
        return array_values(array_filter(array_map('trim', $parts)));
    }

    /**
     * Periksa apakah bank soal dirilis untuk kelas tertentu
     */
    public function matchesClass(?string $kelas): bool
    {
        if (empty($kelas) || empty($this->kelas_rilis)) {
            return false;
        }

        $upperTarget = strtoupper(trim($this->kelas_rilis));
        $upperKelas = strtoupper(trim($kelas));

        if (in_array($upperTarget, ['ALL', 'SEMUA', 'SEMUA KELAS', '*'])) {
            return true;
        }

        if ($upperTarget === $upperKelas) {
            return true;
        }

        $classes = $this->getReleasedClassesList();
        if (in_array($upperKelas, $classes)) {
            return true;
        }

        return str_contains($upperTarget, $upperKelas);
    }
}

