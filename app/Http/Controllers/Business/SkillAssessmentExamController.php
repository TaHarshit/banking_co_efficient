<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\SkillAssessmentExam;
use App\Models\SkillAssessmentExamAnswer;
use App\Models\SkillAssessmentExamTemplate;
use App\Models\SkillAssessmentSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SkillAssessmentExamController extends Controller
{
    private function getBusinessId(): int
    {
        return (int) Auth::guard('business')->id();
    }

    /**
     * Display a listing of skill assessment exams for the business
     */
    public function ManageExams(Request $request)
    {
        $businessId = $this->getBusinessId();

        $query = SkillAssessmentExam::with(['user', 'section', 'examTemplate'])
            ->where(function ($q) use ($businessId) {
                $q->whereHas('user', function ($sub) use ($businessId) {
                    $sub->where('business_id', $businessId);
                })->orWhereHas('examTemplate', function ($sub) use ($businessId) {
                    $sub->where('business_id', $businessId);
                });
            })
            ->orderBy('created_at', 'desc');

        // Search by candidate name, surname, or email
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('user', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(COALESCE(name, ''), ' ', COALESCE(surname, '')) LIKE ?", ["%{$search}%"]);
                });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by exam template
        if ($request->filled('exam_template_id')) {
            $query->where('skill_assessment_exam_template_id', $request->exam_template_id);
        }

        // Filter by section (supports template sections, exam section, and answered questions section)
        if ($request->filled('section_id')) {
            $sectionId = $request->section_id;
            $query->where(function ($q) use ($sectionId) {
                $q->where('skill_assessment_section_id', $sectionId)
                    ->orWhereHas('examTemplate.sections', function ($sub) use ($sectionId) {
                        $sub->where('skill_assessment_sections.id', $sectionId);
                    })
                    ->orWhereHas('answers.question', function ($sub) use ($sectionId) {
                        $sub->where('skill_assessment_section_id', $sectionId);
                    });
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $exams = $query->paginate(20)->appends($request->all());

        $sections = SkillAssessmentSection::with('examTemplate')
            ->where(function ($q) use ($businessId) {
                $q->where('business_id', $businessId)
                    ->orWhereNull('business_id');
            })
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        $examTemplates = SkillAssessmentExamTemplate::where(function ($q) use ($businessId) {
                $q->where('business_id', $businessId)
                    ->orWhereNull('business_id');
            })
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        return view('business.skill-assessments.exam-results.manage', compact('exams', 'sections', 'examTemplates'));
    }

    /**
     * View a specific exam with all answers
     */
    public function ViewExam($id)
    {
        $businessId = $this->getBusinessId();

        $exam = SkillAssessmentExam::with([
            'user',
            'section',
            'examTemplate',
            'answers.question.options'
        ])
        ->where(function ($q) use ($businessId) {
            $q->whereHas('user', function ($sub) use ($businessId) {
                $sub->where('business_id', $businessId);
            })->orWhereHas('examTemplate', function ($sub) use ($businessId) {
                $sub->where('business_id', $businessId);
            });
        })
        ->findOrFail($id);

        return view('business.skill-assessments.exam-results.view', compact('exam'));
    }

    /**
     * Score an open text answer (AJAX)
     */
    public function ScoreAnswer(Request $request)
    {
        $request->validate([
            'answer_id' => 'required|exists:skill_assessment_exam_answers,id',
            'score' => 'required|numeric|min:0',
        ]);

        $businessId = $this->getBusinessId();

        $answer = SkillAssessmentExamAnswer::whereHas('exam', function ($q) use ($businessId) {
            $q->where(function ($sub) use ($businessId) {
                $sub->whereHas('user', function ($u) use ($businessId) {
                    $u->where('business_id', $businessId);
                })->orWhereHas('examTemplate', function ($t) use ($businessId) {
                    $t->where('business_id', $businessId);
                });
            });
        })->findOrFail($request->answer_id);

        $answer->score = $request->score;
        $answer->save();

        // Recalculate exam total score
        $exam = $answer->exam;
        $exam->calculateScore();

        // If all open text answers have been scored, mark as evaluated
        $unevaluatedOpenText = $exam->answers()
            ->whereHas('question', function ($q) {
                $q->where('question_type', 'open_text');
            })
            ->where('score', 0)
            ->count();

        if ($unevaluatedOpenText === 0 && $exam->status === 'completed') {
            $exam->status = 'evaluated';
            $exam->save();
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.score_updated_successfully') ?? 'Score updated successfully',
            'exam' => [
                'total_score' => $exam->total_score,
                'max_score' => $exam->max_score,
                'percentage' => $exam->percentage,
                'score_scale_5' => $exam->score_scale_5,
                'average_score_5' => $exam->average_score_5,
                'section_scores' => $exam->section_scores,
                'status' => $exam->status,
            ],
        ]);
    }
}
