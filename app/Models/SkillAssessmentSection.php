<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillAssessmentSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'skill_assessment_exam_template_id',
        'business_id',
        'title',
        'title_fr',
        'description',
        'description_fr',
        'order',
        'is_active',
    ];

    protected $hidden = [
        'title_fr',
        'description_fr',
    ];

    public function examTemplate()
    {
        return $this->belongsTo(SkillAssessmentExamTemplate::class, 'skill_assessment_exam_template_id');
    }

    public function business()
    {
        return $this->belongsTo(BusinessOwner::class, 'business_id');
    }

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function questions()
    {
        return $this->hasMany(SkillAssessmentQuestion::class, 'skill_assessment_section_id')
            ->orderBy('order');
    }

    public function activeQuestions()
    {
        return $this->questions()->where('is_active', true);
    }

    /**
     * Get localized title based on app locale
     */
    public function getTitleAttribute($value)
    {
        return app()->getLocale() === 'fr' && $this->title_fr ? $this->title_fr : $value;
    }

    /**
     * Get localized description based on app locale
     */
    public function getDescriptionAttribute($value)
    {
        return app()->getLocale() === 'fr' && $this->description_fr ? $this->description_fr : $value;
    }
}
