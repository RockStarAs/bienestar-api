<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateQuestion extends Model
{
    use HasFactory;
    const TYPE_TEXT = 'text';
    const TYPE_DATE = 'date';
    const TYPE_SINGLE_CHOICE = 'single_choice';
    const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    const TYPE_LIKERT = 'likert';
    const TYPE_GROUPED = 'grouped';
    const TYPE_GROUPED_CHILD = 'grouped_child';

    public const TYPES = [
        self::TYPE_TEXT,
        self::TYPE_DATE,
        self::TYPE_SINGLE_CHOICE,
        self::TYPE_MULTIPLE_CHOICE,
        self::TYPE_LIKERT,
        self::TYPE_GROUPED,
        self::TYPE_GROUPED_CHILD,
    ];

    protected $fillable = [
        'template_version_id',
        'section',
        'title',
        'subtitle',
        'text',
        'type',
        'required',
        'parent_question_id',
        'order'
    ];

    protected $casts = [
        'required' => 'boolean',
    ];

    

    public function options()
    {
        return $this->hasMany(TemplateQuestionOption::class, 'question_id')
                    ->orderBy('order');
    }

    public function itsVersionIsPublished(){
        $version = $this->version; // modelo o null
        return $version && $version->status === TestTemplateVersion::STATUS_PUBLISHED;
    }

    //Usar con cuidado
    public function parentQuestion(){
        return $this->belongsTo(TemplateQuestion::class,'parent_question_id');
    }

    public function children(){
        return $this->hasMany(TemplateQuestion::class, 'parent_question_id')->orderBy('order');
    }

    public function isParentQuestion(){
        return $this->type == TemplateQuestion::TYPE_GROUPED;
    }

    public function isChildQuestion(){
        return $this->type == TemplateQuestion::TYPE_GROUPED_CHILD;
    }

}
