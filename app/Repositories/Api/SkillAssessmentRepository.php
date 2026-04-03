<?php

namespace App\Repositories\Api;

use App\Models\SkillAssessmentExamTemplate;
use App\Models\SkillAssessmentSection;
use App\Models\SkillAssessmentExam;
use App\Models\SkillAssessmentExamAnswer;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class SkillAssessmentRepository extends BaseRepository
{
    /**
     * Specify the model class name
     */
    public function model(): string
    {
        return SkillAssessmentExam::class;
    }

    /**
     * Get all active exam templates (without sections)
     * If businessId is provided, returns business templates if available, otherwise admin templates
     */
    public function getActiveExamTemplates(?int $businessId = null): Collection
    {
        if ($businessId) {
            $businessTemplates = SkillAssessmentExamTemplate::where('is_active', true)
                ->where('business_id', $businessId)
                ->orderBy('order')
                ->get();

            if ($businessTemplates->count() > 0) {
                return $businessTemplates;
            }
        }

        return SkillAssessmentExamTemplate::where('is_active', true)
            ->whereNull('business_id')
            ->orderBy('order')
            ->get();
    }

    /**
     * Get all active sections with their active questions and options for a specific exam template
     */
    public function getActiveSectionsWithQuestionsForTemplate(int $examTemplateId, ?int $businessId = null): Collection
    {
        // First get the template to check ownership
        $template = SkillAssessmentExamTemplate::where('id', $examTemplateId)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        // Define the target business ID based on the template's ownership
        $targetBusinessId = $template->business_id;

        return SkillAssessmentSection::where('is_active', true)
            ->where('skill_assessment_exam_template_id', $examTemplateId)
            ->where('business_id', $targetBusinessId) // match the template's business layer
            ->orderBy('order')
            ->with(['questions' => function ($query) use ($targetBusinessId) {
                $query->where('is_active', true)
                    ->where('business_id', $targetBusinessId)
                    ->orderBy('order')
                    ->with(['options' => function ($q) {
                        $q->where('is_active', true)->orderBy('order');
                    }]);
            }])
            ->get();
    }

    /**
     * Get an in-progress exam for user and section
     */
    public function getInProgressExam(int $userId, int $sectionId): ?SkillAssessmentExam
    {
        return SkillAssessmentExam::where('user_id', $userId)
            ->where('skill_assessment_section_id', $sectionId)
            ->where('status', 'in_progress')
            ->first();
    }

    /**
     * Create a new exam
     */
    public function createExam(array $data): SkillAssessmentExam
    {
        return SkillAssessmentExam::create($data);
    }

    /**
     * Save an exam answer
     */
    public function saveAnswer(array $data): SkillAssessmentExamAnswer
    {
        return SkillAssessmentExamAnswer::updateOrCreate(
            [
                'skill_assessment_exam_id' => $data['skill_assessment_exam_id'],
                'skill_assessment_question_id' => $data['skill_assessment_question_id'],
            ],
            [
                'text_answer' => $data['text_answer'],
                'selected_option_ids' => $data['selected_option_ids'],
            ]
        );
    }

    /**
     * Get user's exam history
     */
    public function getUserExamHistory(int $userId): Collection
    {
        return SkillAssessmentExam::where('user_id', $userId)
            ->with(['examTemplate', 'section'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get exam with answers
     */
    public function getExamWithAnswers(int $examId): ?SkillAssessmentExam
    {
        return SkillAssessmentExam::with(['section', 'answers.question'])
            ->find($examId);
    }
}
