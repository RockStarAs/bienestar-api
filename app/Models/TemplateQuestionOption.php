<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateQuestionOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'label',
        'value',
        'order'
    ];

    public function question()
    {
        return $this->belongsTo(TemplateQuestion::class, 'question_id');
    }
}
