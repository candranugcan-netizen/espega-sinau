<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = [
        'nama_mapel',
        'kode_mapel',
        'kelompok', // Umum (A), Keahlian (B), Muatan Lokal
        'kkm',
        'kelas',
        'guru_id',
        'keterangan',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function bankSoals(): HasMany
    {
        return $this->hasMany(BankSoal::class, 'subject_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'subject_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'subject_id');
    }
}
