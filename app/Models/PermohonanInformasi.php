<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PermohonanInformasi extends Model
{
    use HasFactory;

    protected $table = 'permohonan_informasi';

    protected $fillable = [
        'nomor_laporan',
        'nama_lengkap',
        'kontak',
        'subjek',
        'pesan',
        'status', // pending, proses, selesai, ditolak
        'tanggapan',
        'tanggal_tanggapan',
        'petugas_id',
    ];

    protected $casts = [
        'tanggal_tanggapan' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($item) {
            if (empty($item->nomor_laporan)) {
                $item->nomor_laporan = static::generateNomorLaporan();
            }
            if (empty($item->status)) {
                $item->status = 'pending';
            }
        });
    }

    /**
     * Generate unique nomor laporan (Format: PI-YYYYMMDD-XXXXX)
     */
    public static function generateNomorLaporan(): string
    {
        $prefix = 'PI-' . date('Ymd') . '-';
        do {
            $code = $prefix . strtoupper(Str::random(5));
        } while (static::where('nomor_laporan', $code)->exists());

        return $code;
    }

    /**
     * Petugas yang menanggapi
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    /**
     * Label teks status
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Respon',
            'proses' => 'Sedang Diproses',
            'selesai' => 'Selesai Ditanggapi',
            'ditolak' => 'Ditolak',
            default => ucfirst($this->status),
        };
    }

    /**
     * Class Tailwind badge status
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
            'proses' => 'bg-sky-50 text-sky-700 border-sky-200',
            'selesai' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'ditolak' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }
}

