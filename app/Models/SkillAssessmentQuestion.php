<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillAssessmentQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'skill_assessment_section_id',
        'business_id',
        'question_type',
        'question_text',
        'question_text_fr',
        'helper_text',
        'helper_text_fr',
        'order',
        'is_required',
        'is_active',
    ];

    protected $hidden = [
        'question_text_fr',
        'helper_text_fr',
    ];

    public function business()
    {
        return $this->belongsTo(BusinessOwner::class, 'business_id');
    }

    protected $casts = [
        'is_active' => 'boolean',
        'is_required' => 'boolean',
        'order' => 'integer',
    ];

    public function section()
    {
        return $this->belongsTo(SkillAssessmentSection::class, 'skill_assessment_section_id');
    }

    public function options()
    {
        return $this->hasMany(SkillAssessmentQuestionOption::class, 'skill_assessment_question_id')
            ->orderBy('order');
    }

    public function activeOptions()
    {
        return $this->options()->where('is_active', true);
    }

    public function hasOptions()
    {
        return in_array($this->question_type, ['radio', 'multi_select']);
    }

    public function isMultiSelect()
    {
        return $this->question_type === 'multi_select';
    }

    public function isRadio()
    {
        return $this->question_type === 'radio';
    }

    public function isOpenText()
    {
        return $this->question_type === 'open_text';
    }

    /**
     * Get localized question text based on app locale
     */
    public function getQuestionTextAttribute($value)
    {
        return app()->getLocale() === 'fr' && $this->question_text_fr ? $this->question_text_fr : $value;
    }

    /**
     * Get localized helper text based on app locale
     */
    public function getHelperTextAttribute($value)
    {
        return app()->getLocale() === 'fr' && $this->helper_text_fr ? $this->helper_text_fr : $value;
    }
}
