<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillAssessmentExamAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'skill_assessment_exam_id',
        'skill_assessment_question_id',
        'text_answer',
        'selected_option_ids',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'selected_option_ids' => 'array',
            'score' => 'decimal:2',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(SkillAssessmentExam::class, 'skill_assessment_exam_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SkillAssessmentQuestion::class, 'skill_assessment_question_id');
    }

    /**
     * Calculate score based on selected options
     */
    public function calculateScore(): float
    {
        $question = $this->question;

        // Open text questions are scored manually
        if ($question->isOpenText()) {
            return 0;
        }

        // No options selected
        if (empty($this->selected_option_ids)) {
            return 0;
        }

        // Get weightage for selected options
        $score = SkillAssessmentQuestionOption::whereIn('id', $this->selected_option_ids)
            ->where('skill_assessment_question_id', $question->id)
            ->sum('weightage');

        return (float) $score;
    }

    /**
     * Calculate and save score
     */
    public function updateScore(): void
    {
        $this->score = $this->calculateScore();
        $this->save();
    }
}
