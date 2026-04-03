<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseStudyQuestionOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_study_question_id',
        'option_en',
        'option_fr',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(CaseStudyQuestion::class, 'case_study_question_id');
    }

    /**
     * Get localized option based on app locale
     */
    public function getOptionAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'fr' ? $this->option_fr : $this->option_en;
    }
}
