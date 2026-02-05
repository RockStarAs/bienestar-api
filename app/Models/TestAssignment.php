<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'student_id',
        'status',
        'score',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(){
        return $this->hasMany(TestAnswer::class);
    }
}
