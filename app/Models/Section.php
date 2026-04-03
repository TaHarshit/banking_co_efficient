<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'title_en',
        'title_fr',
        'subtitle_en',
        'subtitle_fr',
        'header_en',
        'header_fr',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the business that owns the section
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get questions for this section
     */
    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    /**
     * Get active questions for this section
     */
    public function activeQuestions()
    {
        return $this->hasMany(Question::class)->where('is_active', true)->orderBy('order');
    }

    /**
     * Get localized title based on app locale
     */
    public function getTitleAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'fr' ? $this->title_fr : $this->title_en;
    }

    /**
     * Get localized subtitle based on app locale
     */
    public function getSubtitleAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'fr' ? $this->subtitle_fr : $this->subtitle_en;
    }

    /**
     * Get localized header based on app locale
     */
    public function getHeaderAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'fr' ? $this->header_fr : $this->header_en;
    }
}
