<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseStudyQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_name',
        'question_en',
        'question_fr',
    ];

    public function options()
    {
        return $this->hasMany(CaseStudyQuestionOption::class);
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
