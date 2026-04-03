<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillAssessmentExamTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'title',
        'title_fr',
        'description',
        'description_fr',
        'exam_level',
        'exam_level_fr',
        'tags',
        'tags_fr',
        'duration_minutes',
        'passing_percentage',
        'order',
        'is_active',
    ];

    protected $hidden = [
        'title_fr',
        'description_fr',
        'exam_level_fr',
        'tags_fr',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'duration_minutes' => 'integer',
        'passing_percentage' => 'decimal:2',
        'tags' => 'array',
        'tags_fr' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(BusinessOwner::class, 'business_id');
    }

    public function sections()
    {
        return $this->hasMany(SkillAssessmentSection::class, 'skill_assessment_exam_template_id')
            ->orderBy('order');
    }

    public function activeSections()
    {
        return $this->sections()->where('is_active', true);
    }

    public function userAttempts()
    {
        return $this->hasMany(SkillAssessmentExam::class, 'skill_assessment_exam_template_id');
    }

    /**
     * Get total question count across all sections
     */
    public function getTotalQuestionsAttribute(): int
    {
        return $this->sections->sum(function ($section) {
            return $section->questions->count();
        });
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

    /**
     * Get localized exam level based on app locale
     */
    public function getExamLevelAttribute($value)
    {
        return app()->getLocale() === 'fr' && $this->exam_level_fr ? $this->exam_level_fr : $value;
    }

    /**
     * Get localized tags based on app locale
     */
    public function getTagsAttribute($value)
    {
        return app()->getLocale() === 'fr' && $this->tags_fr ? $this->tags_fr : $value;
    }
}
