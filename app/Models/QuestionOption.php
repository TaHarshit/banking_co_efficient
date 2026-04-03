<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'option_text_en',
        'option_text_fr',
        'option_subtitle_en',
        'option_subtitle_fr',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the question that owns the option
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get user responses that selected this option
     */
    public function responses()
    {
        return $this->hasMany(UserResponse::class, 'option_id');
    }

    /**
     * Get localized option text based on app locale
     */
    public function getOptionTextAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'fr' ? $this->option_text_fr : $this->option_text_en;
    }

    /**
     * Get localized option subtitle based on app locale
     */
    public function getOptionSubtitleAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'fr' ? $this->option_subtitle_fr : $this->option_subtitle_en;
    }
}
