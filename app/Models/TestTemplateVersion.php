<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestTemplateVersion extends Model
{
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    
    use HasFactory;

    protected $fillable = [
        'template_id',
        'version',
        'status',
        'published_at',
        'created_by'
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(TestTemplate::class, 'template_id');
    }

    public function questions(){
        return $this->hasMany(TemplateQuestion::class,'template_version_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
