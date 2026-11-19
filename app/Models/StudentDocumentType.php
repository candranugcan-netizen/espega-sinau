<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentDocumentType extends Model
{
    protected $fillable = ['name', 'slug', 'is_required', 'is_active'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'is_active' => 'boolean'];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class, 'document_type_id');
    }
}
