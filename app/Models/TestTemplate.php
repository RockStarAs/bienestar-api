<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestTemplate extends Model
{
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';

    use HasFactory;

    protected $fillable = [
        "name",
        "description",
        "status",
        "created_by",
    ];

    public function versions()
    {
        return $this->hasMany(TestTemplateVersion::class, 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
