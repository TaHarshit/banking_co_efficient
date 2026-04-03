<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillAssessmentQuestionOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'skill_assessment_question_id',
        'option_text',
        'option_text_fr',
        'weightage',
        'order',
        'is_active',
        'is_correct',
    ];

    protected $hidden = [
        'option_text_fr',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_correct' => 'boolean',
        'order' => 'integer',
        'weightage' => 'decimal:2',
    ];

    public function question()
    {
        return $this->belongsTo(SkillAssessmentQuestion::class, 'skill_assessment_question_id');
    }

    /**
     * Get localized option text based on app locale
     */
    public function getOptionTextAttribute($value)
    {
        return app()->getLocale() === 'fr' && $this->option_text_fr ? $this->option_text_fr : $value;
    }
}
