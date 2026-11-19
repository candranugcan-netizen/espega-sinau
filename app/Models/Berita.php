<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Berita extends Model
{
    use HasFactory;

    protected $table = 'berita';

    protected $fillable = [
        'academic_year_id',
        'user_id',
        'judul',
        'slug',
        'kategori', // 'berita', 'buku_harian', 'pengumuman', 'prestasi'
        'ringkasan',
        'konten',
        'gambar',
        'tanggal',
        'tampil_di_web',
        'dibaca',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tampil_di_web' => 'boolean',
        'dibaca' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($berita) {
            if (empty($berita->academic_year_id)) {
                $berita->academic_year_id = AcademicYear::getActive()?->id;
            }
            if (empty($berita->slug)) {
                $slug = Str::slug($berita->judul);
                $count = static::where('slug', 'LIKE', "{$slug}%")->count();
                $berita->slug = $count > 0 ? "{$slug}-" . ($count + 1) : $slug;
            }
            if (empty($berita->tanggal)) {
                $berita->tanggal = now()->toDateString();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Scope untuk berita yang dipublikasikan ke Website
     */
    public function scopePublicWeb($query)
    {
        return $query->where('tampil_di_web', true);
    }

    /**
     * URL Gambar
     */
    public function getGambarUrlAttribute(): ?string
    {
        if (empty($this->gambar)) {
            return null;
        }
        if (str_starts_with($this->gambar, 'http')) {
            return $this->gambar;
        }
        return asset($this->gambar);
    }
}

