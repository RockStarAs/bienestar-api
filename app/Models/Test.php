<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    const STATUS_ACTIVE = 'active';
    const STATUS_CLOSED = 'closed';

    use HasFactory;

    protected $fillable = [
        'template_version_id',
        'title',
        'period',
        'status',
        'created_by'
    ];

    public function templateVersion()
    {
        return $this->belongsTo(TestTemplateVersion::class, 'template_version_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments()
    {
        return $this->hasMany(TestAssignment::class);
    }
}
