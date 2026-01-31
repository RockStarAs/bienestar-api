<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_version_id',
        'section',
        'text',
        'type',
        'required',
        'order'
    ];

    protected $casts = [
        'required' => 'boolean',
    ];

    public function version()
    {
        return $this->belongsTo(TestTemplateVersion::class, 'template_version_id');
    }

    public function options()
    {
        return $this->hasMany(TemplateQuestionOption::class, 'question_id')
                    ->orderBy('order');
    }
}
