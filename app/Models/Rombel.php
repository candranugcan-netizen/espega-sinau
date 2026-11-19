<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rombel extends Model
{
    use HasFactory;

    protected $table = 'rombels';

    protected $fillable = [
        'nama_rombel',
        'tingkat', // 7, 8, 9
        'wali_kelas_id',
        'academic_year_id',
        'ruang',
        'keterangan',
    ];

    /**
     * Wali Kelas (Guru)
     */
    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    /**
     * Tahun Ajaran
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Seluruh Siswa Aktif yang tergabung dalam Rombel ini
     */
    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'kelas', 'nama_rombel')
            ->where('role_id', 3)
            ->where(function ($q) {
                $q->where('status_siswa', 'aktif')->orWhereNull('status_siswa');
            });
    }

    /**
     * Seluruh Siswa (termasuk mutasi/alumni jika ada di rombel ini)
     */
    public function allStudents(): HasMany
    {
        return $this->hasMany(User::class, 'kelas', 'nama_rombel')->where('role_id', 3);
    }

    /**
     * Ujian yang ditargetkan untuk rombel ini
     */
    public function getExamsAttribute()
    {
        return Exam::all()->filter(function ($exam) {
            return $exam->matchesClass($this->nama_rombel);
        });
    }

    /**
     * Jumlah Siswa Laki-Laki
     */
    public function getJumlahLakiAttribute(): int
    {
        return $this->students()->where('jenis_kelamin', 'L')->count();
    }

    /**
     * Jumlah Siswa Perempuan
     */
    public function getJumlahPerempuanAttribute(): int
    {
        return $this->students()->where('jenis_kelamin', 'P')->count();
    }
}

