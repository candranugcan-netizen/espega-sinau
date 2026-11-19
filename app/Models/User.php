<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'token',
        'nama_lengkap',
        'nip',
        'kelas',
        'role_id',
        'status_blokir',
        'nisn',
        'nis',
        'jenis_kelamin', // 'L' atau 'P'
        'status_siswa', // 'aktif', 'alumni', 'mutasi', 'keluar'
        'academic_year_id',
        'tahun_masuk_id',
        'tahun_lulus_id',
        'telepon',
        'alamat',
    ];

    /**
     * Mutator agar password SELALU di-hash secara otomatis
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = \Illuminate\Support\Facades\Hash::needsRehash($value)
                ? \Illuminate\Support\Facades\Hash::make($value)
                : $value;
        }
    }

    protected static function booted()
    {
        static::saving(function ($user) {
            // Pastikan password selalu terisi dan ter-hash
            if (empty($user->password)) {
                $secret = !empty($user->token) ? $user->token : ($user->username ?: '123456');
                $user->password = $secret; // Melewati setPasswordAttribute sehingga otomatis di-hash
            }
        });
    }

    protected $hidden = [
        'password',
        'token',
        'remember_token',
    ];

    /**
     * Relationship to Role
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Subjects taught (for teachers)
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'guru_id');
    }

    /**
     * Bank Soal created (for teachers)
     */
    public function bankSoals(): HasMany
    {
        return $this->hasMany(BankSoal::class, 'guru_id');
    }

    /**
     * Rombel di mana guru menjadi wali kelas
     */
    public function rombelWali(): HasMany
    {
        return $this->hasMany(Rombel::class, 'wali_kelas_id');
    }

    /**
     * Exam sessions (for students)
     */
    public function examSessions(): HasMany
    {
        return $this->hasMany(ExamSession::class, 'user_id');
    }

    /**
     * Tahun Ajaran aktif saat ini
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Tahun Ajaran Masuk
     */
    public function tahunMasuk(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'tahun_masuk_id');
    }

    /**
     * Tahun Ajaran Lulus (Alumni)
     */
    public function tahunLulus(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'tahun_lulus_id');
    }

    /**
     * Riwayat Kenaikan / Rombel Kelas Siswa
     */
    public function classHistories(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class, 'user_id')->orderBy('id', 'desc');
    }

    public function studentProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StudentProfile::class, 'user_id');
    }

    public function studentDocuments(): HasMany
    {
        return $this->hasMany(StudentDocument::class, 'user_id');
    }

    /**
     * Accessor for Display Name
     */
    public function getNamaAttribute(): string
    {
        return $this->nama_lengkap ?: ($this->name ?: ($this->username ?: 'Pengguna'));
    }

    /**
     * Role checking helpers
     */
    public function isAdmin(): bool
    {
        return $this->role_id === 1 || ($this->role && strtolower($this->role->nama_role) === 'admin');
    }

    public function isGuru(): bool
    {
        return $this->role_id === 2 || ($this->role && strtolower($this->role->nama_role) === 'guru');
    }

    public function isSiswa(): bool
    {
        return $this->role_id === 3 || ($this->role && strtolower($this->role->nama_role) === 'siswa');
    }

    public function isAlumni(): bool
    {
        return $this->isSiswa() && ($this->status_siswa === 'alumni' || $this->kelas === 'Alumni' || !empty($this->tahun_lulus_id));
    }

    public function isSiswaAktif(): bool
    {
        return $this->isSiswa() && !$this->isAlumni() && ($this->status_siswa === 'aktif' || empty($this->status_siswa));
    }

    public function isBlocked(): bool
    {
        return (string)$this->status_blokir === '1';
    }
}
