<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_assignment_id',
        'question_id',
        'option_id',
        'text_value'
    ];

    public function assignment()
    {
        return $this->belongsTo(TestAssignment::class, 'test_assignment_id');
    }

    public function question()
    {
        return $this->belongsTo(TemplateQuestion::class, 'question_id');
    }

    public function option()
    {
        return $this->belongsTo(TemplateQuestionOption::class, 'option_id');
    }
}
