<?php

namespace App\Classes\Api;

use App\Models\SkillAssessmentExamTemplate;
use App\Models\SkillAssessmentSection;
use App\Models\SkillAssessmentExam;
use App\Models\SkillAssessmentExamAnswer;
use App\Repositories\Api\SkillAssessmentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SkillAssessmentCls
{
    protected SkillAssessmentRepository $repository;

    public function __construct(SkillAssessmentRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all active exam templates (without sections)
     * If user belongs to a business with custom exam templates, show those; otherwise show admin templates
     */
    public function getExams(string $locale = 'en'): JsonResponse
    {
        app()->setLocale($locale);

        $user = Auth::user();
        $businessId = $user->business_id ?? null;

        $examTemplates = $this->repository->getActiveExamTemplates($businessId);

        return response()->json([
            'status' => true,
            'message' => 'Exam templates retrieved successfully',
            'data' => $examTemplates,
            'source' => $businessId && $examTemplates->isNotEmpty() && $examTemplates->first()->business_id ? 'business' : 'admin',
        ]);
    }

    /**
     * Get all active skill assessment sections with questions for a specific exam template
     * If user belongs to a business with custom sections, show those; otherwise show admin sections
     */
    public function getSections(int $examTemplateId, string $locale = 'en'): JsonResponse
    {
        app()->setLocale($locale);

        $user = Auth::user();
        $businessId = $user->business_id ?? null;

        $sections = $this->repository->getActiveSectionsWithQuestionsForTemplate($examTemplateId, $businessId);

        return response()->json([
            'status' => true,
            'message' => 'Sections retrieved successfully',
            'data' => $sections,
            'source' => $businessId && $sections->isNotEmpty() && $sections->first()->business_id ? 'business' : 'admin',
        ]);
    }

    /**
     * Start a new exam for an exam template
     */
    public function startExam(int $examTemplateId): JsonResponse
    {
        $user = Auth::user();
        $businessId = $user->business_id ?? null;

        // Get exam template with appropriate business_id filter
        $examTemplate = SkillAssessmentExamTemplate::where('is_active', true)
            ->where('id', $examTemplateId)
            ->where(function ($query) use ($businessId) {
                if ($businessId) {
                    $query->where('business_id', $businessId)
                        ->orWhereNull('business_id');
                } else {
                    $query->whereNull('business_id');
                }
            })
            ->first();

        if (!$examTemplate) {
            return response()->json([
                'status' => false,
                'message' => 'Exam not found or inactive',
            ], 404);
        }

        $sectionBusinessId = $examTemplate->business_id;

        // Check if user has an in-progress exam for this template
        $existingExam = SkillAssessmentExam::where('user_id', Auth::id())
            ->where('skill_assessment_exam_template_id', $examTemplateId)
            ->where('status', 'in_progress')
            ->first();

        if ($existingExam) {
            return response()->json([
                'status' => true,
                'message' => 'Continuing existing exam',
                'data' => [
                    'exam' => $existingExam,
                    'exam_template' => $examTemplate->load(['sections' => function ($query) use ($sectionBusinessId) {
                        $query->where('is_active', true)
                            ->orderBy('order')
                            ->with(['questions' => function ($q) use ($sectionBusinessId) {
                                $q->where('is_active', true)
                                    ->where(function ($qInner) use ($sectionBusinessId) {
                                        if ($sectionBusinessId) {
                                            $qInner->where('business_id', $sectionBusinessId);
                                        } else {
                                            $qInner->whereNull('business_id');
                                        }
                                    })
                                    ->orderBy('order')
                                    ->with(['options' => function ($opt) {
                                        $opt->where('is_active', true)->orderBy('order');
                                    }]);
                            }]);
                    }]),
                ],
            ]);
        }

        // Create new exam
        $exam = SkillAssessmentExam::create([
            'user_id' => Auth::id(),
            'skill_assessment_exam_template_id' => $examTemplateId,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Exam started successfully',
            'data' => [
                'exam' => $exam,
                'exam_template' => $examTemplate->load(['sections' => function ($query) use ($sectionBusinessId) {
                    $query->where('is_active', true)
                        ->orderBy('order')
                        ->with(['questions' => function ($q) use ($sectionBusinessId) {
                            $q->where('is_active', true)
                                ->where(function ($qInner) use ($sectionBusinessId) {
                                    if ($sectionBusinessId) {
                                        $qInner->where('business_id', $sectionBusinessId);
                                    } else {
                                        $qInner->whereNull('business_id');
                                    }
                                })
                                ->orderBy('order')
                                ->with(['options' => function ($opt) {
                                    $opt->where('is_active', true)->orderBy('order');
                                }]);
                        }]);
                }]),
            ],
        ]);
    }

    /**
     * Submit exam answers
     */
    public function submitExam(int $examId, array $answers): JsonResponse
    {
        $exam = SkillAssessmentExam::where('user_id', Auth::id())->find($examId);

        if (!$exam) {
            return response()->json([
                'status' => false,
                'message' => 'Exam not found',
            ], 404);
        }

        if ($exam->isCompleted() || $exam->isEvaluated()) {
            return response()->json([
                'status' => false,
                'message' => 'Exam already submitted',
            ], 400);
        }

        // Save each answer
        foreach ($answers as $answer) {
            $questionId = $answer['question_id'];
            $textAnswer = $answer['text_answer'] ?? null;
            $selectedOptionIds = $answer['selected_option_ids'] ?? [];

            // Create or update answer
            $examAnswer = $this->repository->saveAnswer([
                'skill_assessment_exam_id' => $examId,
                'skill_assessment_question_id' => $questionId,
                'text_answer' => $textAnswer,
                'selected_option_ids' => $selectedOptionIds,
            ]);

            // Calculate score for this answer
            $examAnswer->updateScore();
        }

        // Mark exam as completed
        $exam->status = 'completed';
        $exam->completed_at = now();
        $exam->save();

        // Calculate total score
        $exam->calculateScore();

        return response()->json([
            'status' => true,
            'message' => 'Exam submitted successfully',
            'data' => [
                'exam' => $exam->fresh()->load('answers'),
                'total_score' => $exam->total_score,
                'max_score' => $exam->max_score,
                'percentage' => $exam->percentage,
            ],
        ]);
    }

    /**
     * Get exam result
     */
    public function getResult(int $examId): JsonResponse
    {
        $exam = SkillAssessmentExam::where('user_id', Auth::id())
            ->with(['examTemplate', 'answers.question'])
            ->find($examId);

        if (!$exam) {
            return response()->json([
                'status' => false,
                'message' => 'Exam not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Result retrieved successfully',
            'data' => [
                'exam' => $exam,
                'total_score' => $exam->total_score,
                'max_score' => $exam->max_score,
                'percentage' => $exam->percentage,
                'status' => $exam->status,
            ],
        ]);
    }

    /**
     * Get user's exam history grouped by template
     */
    public function getHistory(): JsonResponse
    {
        $user = Auth::user();
        $businessId = $user->business_id ?? null;

        // Get all available templates for this user (both global and business-specific)
        $templates = $this->repository->getActiveExamTemplates($businessId);

        // Get user's exam history
        $exams = $this->repository->getUserExamHistory($user->id);

        // Group exams by template ID (latest first order preserved)
        $groupedByTemplate = $exams->groupBy('skill_assessment_exam_template_id');

        $result = [];

        foreach ($templates as $template) {
            $templateId = $template->id;
            $templateExams = $groupedByTemplate->get($templateId, collect());

            // Load sections with active questions for total count
            $template->load(['sections.questions' => function ($query) {
                $query->where('is_active', true);
            }]);

            // Calculate total questions across all sections
            $totalQuestions = $template->sections->sum(function ($section) {
                return $section->questions->count();
            });

            // Remove sections from template data in response
            unset($template->sections);

            // Calculate average percentage from completed/evaluated exams
            $completedExams = $templateExams->filter(function ($exam) {
                return $exam->status === 'completed' || $exam->status === 'evaluated';
            });

            $averagePercentage = 0;
            if ($completedExams->isNotEmpty()) {
                $averagePercentage = round($completedExams->avg('percentage'), 2);
            }

            // Prepare exam list (already ordered last to first from repository)
            $examList = $templateExams->map(function ($exam) {
                return [
                    'id' => $exam->id,
                    'total_score' => $exam->total_score,
                    'max_score' => $exam->max_score,
                    'percentage' => $exam->percentage,
                    'status' => $exam->status,
                    'started_at' => $exam->started_at,
                    'completed_at' => $exam->completed_at,
                    'created_at' => $exam->created_at,
                ];
            })->values();

            $result[] = [
                'template_id' => $templateId,
                'template' => $template,
                'total_questions' => $totalQuestions,
                'average_percentage' => $averagePercentage,
                'exams' => $examList,
                'attempted' => $templateExams->isNotEmpty(),
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'History retrieved successfully',
            'data' => $result,
        ]);
    }
}
