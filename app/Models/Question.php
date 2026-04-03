<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'business_id',
        'question_type',
        'question_text_en',
        'question_text_fr',
        'helper_text_en',
        'helper_text_fr',
        'order',
        'is_required',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_required' => 'boolean',
        'order' => 'integer',
        'settings' => 'array',
    ];

    /**
     * Get the business that owns the question
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Question type constants
     */
    const TYPE_SINGLE_SELECT = 'single_select';
    const TYPE_MULTI_SELECT = 'multi_select';
    const TYPE_RATING_SCALE = 'rating_scale';
    const TYPE_TEXT_INPUT = 'text_input';

    /**
     * Get the section that owns the question
     */
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get options for this question
     */
    public function options()
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    /**
     * Get active options for this question
     */
    public function activeOptions()
    {
        return $this->hasMany(QuestionOption::class)->where('is_active', true)->orderBy('order');
    }

    /**
     * Get user responses for this question
     */
    public function responses()
    {
        return $this->hasMany(UserResponse::class);
    }

    /**
     * Get localized question text based on app locale
     */
    public function getQuestionTextAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'fr' ? $this->question_text_fr : $this->question_text_en;
    }

    /**
     * Get localized helper text based on app locale
     */
    public function getHelperTextAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'fr' ? $this->helper_text_fr : $this->helper_text_en;
    }

    /**
     * Check if question requires options (single/multi select)
     */
    public function requiresOptions()
    {
        return in_array($this->question_type, [self::TYPE_SINGLE_SELECT, self::TYPE_MULTI_SELECT]);
    }

    /**
     * Check if question is a rating scale
     */
    public function isRatingScale()
    {
        return $this->question_type === self::TYPE_RATING_SCALE;
    }

    /**
     * Check if question is text input
     */
    public function isTextInput()
    {
        return $this->question_type === self::TYPE_TEXT_INPUT;
    }

    /**
     * Get question type label for display
     */
    public static function getTypeOptions()
    {
        return [
            self::TYPE_SINGLE_SELECT => __('messages.single_select'),
            self::TYPE_MULTI_SELECT => __('messages.multi_select'),
            self::TYPE_RATING_SCALE => __('messages.rating_scale'),
            self::TYPE_TEXT_INPUT => __('messages.text_input'),
        ];
    }
}
