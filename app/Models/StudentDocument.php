<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    protected $fillable = ['user_id', 'document_type_id', 'original_name', 'path', 'mime_type', 'size_bytes'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(StudentDocumentType::class, 'document_type_id');
    }
}
