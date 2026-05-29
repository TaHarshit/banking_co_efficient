<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Classes\Api\SkillAssessmentCls;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SkillAssessmentController extends Controller
{
    protected SkillAssessmentCls $skillAssessmentCls;

    public function __construct(SkillAssessmentCls $skillAssessmentCls)
    {
        $this->skillAssessmentCls = $skillAssessmentCls;
    }

    /**
     * Get all active exam templates with sections and questions
     * GET /api/skill-assessment/exams
     */
    public function getExams(Request $request): JsonResponse
    {
        $locale = $request->header('Accept-Language', 'en');

        // Normalize locale
        if (str_starts_with($locale, 'fr')) {
            $locale = 'fr';
        } else {
            $locale = 'en';
        }

        return $this->skillAssessmentCls->getExams($locale);
    }

    /**
     * Get all active skill assessment sections with questions for a specific exam template
     * GET /api/skill-assessment/sections/{exam_template_id}
     */
    public function getSections(Request $request, int $examTemplateId): JsonResponse
    {
        $locale = $request->header('Accept-Language', 'en');

        // Normalize locale
        if (str_starts_with($locale, 'fr')) {
            $locale = 'fr';
        } else {
            $locale = 'en';
        }

        return $this->skillAssessmentCls->getSections($examTemplateId, $locale);
    }

    /**
     * Start a new exam for an exam template
     * POST /api/skill-assessment/start/{exam_template_id}
     */
    public function startExam(Request $request, int $examTemplateId): JsonResponse
    {
        $locale = $request->header('Accept-Language', 'en');
        if (str_starts_with($locale, 'fr')) {
            $locale = 'fr';
        } else {
            $locale = 'en';
        }

        return $this->skillAssessmentCls->startExam($examTemplateId, $locale);
    }

    /**
     * Submit exam answers
     * POST /api/skill-assessment/submit/{exam_id}
     */
    public function submitExam(Request $request, int $examId): JsonResponse
    {
        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:skill_assessment_questions,id',
            'answers.*.text_answer' => 'nullable|string',
            'answers.*.selected_option_ids' => 'nullable|array',
            'answers.*.selected_option_ids.*' => 'integer|exists:skill_assessment_question_options,id',
        ]);

        return $this->skillAssessmentCls->submitExam($examId, $request->input('answers'));
    }

    /**
     * Get exam result
     * GET /api/skill-assessment/result/{exam_id}
     */
    public function getResult(Request $request, int $examId): JsonResponse
    {
        $locale = $request->header('Accept-Language', 'en');
        if (str_starts_with($locale, 'fr')) {
            $locale = 'fr';
        } else {
            $locale = 'en';
        }

        return $this->skillAssessmentCls->getResult($examId, $locale);
    }

    /**
     * Get user's exam history
     * GET /api/skill-assessment/history
     */
    public function getHistory(Request $request): JsonResponse
    {
        $locale = $request->header('Accept-Language', 'en');
        if (str_starts_with($locale, 'fr')) {
            $locale = 'fr';
        } else {
            $locale = 'en';
        }

        return $this->skillAssessmentCls->getHistory($locale);
    }
}
