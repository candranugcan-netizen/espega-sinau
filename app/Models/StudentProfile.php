<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    protected $fillable = [
        'user_id', 'nik', 'nomor_kk', 'tempat_lahir', 'tanggal_lahir', 'agama', 'anak_ke', 'jumlah_saudara',
        'alamat_detail', 'kode_pos', 'asal_sekolah', 'tanggal_masuk', 'nama_ayah', 'pekerjaan_ayah',
        'telepon_ayah', 'nama_ibu', 'pekerjaan_ibu', 'telepon_ibu', 'nama_wali', 'pekerjaan_wali',
        'telepon_wali', 'alamat_wali', 'catatan',
    ];

    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date', 'tanggal_masuk' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
