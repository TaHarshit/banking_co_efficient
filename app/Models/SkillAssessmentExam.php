<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillAssessmentExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'skill_assessment_exam_template_id',
        'skill_assessment_section_id',
        'total_score',
        'max_score',
        'percentage',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $appends = [
        'score_scale_5',
        'average_score_5',
    ];

    protected function casts(): array
    {
        return [
            'total_score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function examTemplate(): BelongsTo
    {
        return $this->belongsTo(SkillAssessmentExamTemplate::class, 'skill_assessment_exam_template_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(SkillAssessmentSection::class, 'skill_assessment_section_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SkillAssessmentExamAnswer::class, 'skill_assessment_exam_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isEvaluated(): bool
    {
        return $this->status === 'evaluated';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Calculate and update the exam score from answers
     */
    public function calculateScore(): void
    {
        $totalScore = (float) $this->answers()->sum('score');
        $maxScore = 0;

        // If exam is linked to an exam template, calculate max score across all sections
        if ($this->skill_assessment_exam_template_id && $this->examTemplate) {
            foreach ($this->examTemplate->sections()->where('is_active', true)->get() as $section) {
                $maxScore += $section->questions()
                    ->where('is_active', true)
                    ->get()
                    ->sum(function ($question) {
                        if ($question->isOpenText()) {
                            return 0;
                        }
                        if ($question->isMultiSelect()) {
                            return $question->activeOptions()->sum('weightage') ?? 0;
                        }
                        return $question->activeOptions()->max('weightage') ?? 0;
                    });
            }
        } elseif ($this->section) {
            // Backward compat: single section exam
            $maxScore = $this->section->questions()
                ->where('is_active', true)
                ->get()
                ->sum(function ($question) {
                    if ($question->isOpenText()) {
                        return 0;
                    }
                    if ($question->isMultiSelect()) {
                        return $question->activeOptions()->sum('weightage') ?? 0;
                    }
                    return $question->activeOptions()->max('weightage') ?? 0;
                });
        }

        $this->total_score = $totalScore;
        $this->max_score = $maxScore;
        $this->percentage = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;
        $this->save();
    }

    /**
     * Get percentage statistics for exams, optionally filtered by business, exam template, and/or exam type
     *
     * Exam type values supported:
     * - 'global' => templates with NULL business_id
     * - 'business' => templates with a non-NULL business_id (optionally restricted to $businessId when provided)
     */
    public static function getPercentageStats(?int $businessId = null, ?int $examTemplateId = null, ?string $examType = null): array
    {
        $query = self::query()
            ->whereIn('status', ['completed', 'evaluated'])
            ->whereIn('id', function($q) {
                $q->selectRaw('MAX(id)')
                   ->from('skill_assessment_exams')
                   ->whereIn('status', ['completed', 'evaluated'])
                   ->groupBy('user_id');
            });

        if ($businessId) {
            $query->whereHas('user', function ($q) use ($businessId) {
                $q->where('business_id', $businessId);
            });
        }

        if ($examTemplateId) {
            $query->where('skill_assessment_exam_template_id', $examTemplateId);
        }

        if ($examType) {
            $query->whereHas('examTemplate', function ($q) use ($examType, $businessId) {
                if ($examType === 'global') {
                    $q->whereNull('business_id');
                } elseif ($examType === 'business') {
                    if ($businessId) {
                        $q->where('business_id', $businessId);
                    } else {
                        $q->whereNotNull('business_id');
                    }
                }
            });
        }

        $exams = $query->get(['percentage']);

        $stats = [
            '0-50' => 0,
            '51-70' => 0,
            '71-90' => 0,
            '91-100' => 0,
        ];

        foreach ($exams as $exam) {
            $p = (float) $exam->percentage;
            if ($p <= 50) {
                $stats['0-50']++;
            } elseif ($p <= 70) {
                $stats['51-70']++;
            } elseif ($p <= 90) {
                $stats['71-90']++;
            } else {
                $stats['91-100']++;
            }
        }

        // dd($query->get());

        return $stats;
    }

    /**
     * Get score mapped on a 1 to 5 scale (1.0 to 5.0 scale where 0% = 1.0 and 100% = 5.0)
     */
    public function getScoreScale5Attribute(): float
    {
        if ((float) $this->max_score <= 0) {
            return 1.00;
        }
        return round(1 + (((float) $this->percentage / 100) * 4), 2);
    }

    /**
     * Get average score on a 0 to 5 scale (proportional score where 100% = 5.0)
     */
    public function getAverageScore5Attribute(): float
    {
        if ((float) $this->max_score <= 0) {
            return 0.00;
        }
        return round((((float) $this->percentage / 100) * 5), 2);
    }
}
