<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'semester',
        'is_active',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    /**
     * Relasi ke Ujian yang dilaksanakan pada tahun ajaran ini
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'academic_year_id');
    }

    /**
     * Relasi ke Bank Soal yang dibuat pada tahun ajaran ini
     */
    public function bankSoals(): HasMany
    {
        return $this->hasMany(BankSoal::class, 'academic_year_id');
    }

    /**
     * Relasi ke Riwayat Rombel / Kelas Siswa pada tahun ajaran ini
     */
    public function classHistories(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class, 'academic_year_id');
    }

    /**
     * Relasi ke Siswa yang masuk pada tahun ajaran ini
     */
    public function studentsIn(): HasMany
    {
        return $this->hasMany(User::class, 'tahun_masuk_id');
    }

    /**
     * Relasi ke Siswa yang lulus (Alumni) pada tahun ajaran ini
     */
    public function alumni(): HasMany
    {
        return $this->hasMany(User::class, 'tahun_lulus_id');
    }

    /**
     * Relasi ke Berita Harian / Buku Harian pada tahun ajaran ini
     */
    public function beritas(): HasMany
    {
        return $this->hasMany(Berita::class, 'academic_year_id');
    }

    /**
     * Ambil Tahun Ajaran yang sedang Aktif / Berjalan
     */
    public static function getActive(): ?self
    {
        try {
            $active = self::where('is_active', true)->first();
            if (!$active) {
                $active = self::orderByDesc('id')->first();
            }
            return $active;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Aktifkan tahun ajaran ini dan nonaktifkan yang lain
     */
    public function activate(): void
    {
        self::where('id', '!=', $this->id)->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }

    /**
     * Label lengkap: e.g. "2026/2027 - Ganjil"
     */
    public function getFullLabelAttribute(): string
    {
        $sem = ucfirst($this->semester ?: 'Ganjil');
        return "{$this->nama} ({$sem})";
    }
}

