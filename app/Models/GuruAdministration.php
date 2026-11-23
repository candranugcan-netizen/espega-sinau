<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuruAdministration extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'category',
        'description',
        'original_name',
        'path',
        'mime_type',
        'size_bytes',
    ];
}
