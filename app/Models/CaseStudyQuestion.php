<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseStudyQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_name',
        'section_name_en',
        'section_name_fr',
        'question_en',
        'question_fr',
    ];

    public function options()
    {
        return $this->hasMany(CaseStudyQuestionOption::class);
    }

    /**
     * Get localized section name based on app locale
     */
    public function getSectionNameAttribute($value)
    {
        $locale = app()->getLocale();
        if ($locale === 'fr') {
            return $this->section_name_fr ?: ($this->section_name_en ?: ($value ?: ''));
        }
        return $this->section_name_en ?: ($this->section_name_fr ?: ($value ?: ''));
    }

    /**
     * Get localized question based on app locale
     */
    public function getQuestionAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'fr' ? $this->question_fr : $this->question_en;
    }
}
