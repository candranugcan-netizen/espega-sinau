<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentClassHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'academic_year_id',
        'kelas',
        'status', // aktif, naik_kelas, tinggal_kelas, lulus, mutasi
        'catatan',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}

