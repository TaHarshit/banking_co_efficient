<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SkillAssessmentExam;
use App\Models\SkillAssessmentExamAnswer;
use App\Models\SkillAssessmentExamTemplate;
use Illuminate\Http\Request;

class SkillAssessmentExamController extends Controller
{
    /**
     * Display a listing of all skill assessment exams
     */
    public function ManageExams(Request $request)
    {
        $query = SkillAssessmentExam::with(['user', 'section', 'examTemplate'])
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
        $sections = \App\Models\SkillAssessmentSection::with('examTemplate')
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
        $examTemplates = SkillAssessmentExamTemplate::where('is_active', true)
            ->orderBy('title')
            ->get();

        return view('skill-assessment.exam-results.manage', compact('exams', 'sections', 'examTemplates'));
    }

    /**
     * View a specific exam with all answers
     */
    public function ViewExam($id)
    {
        $exam = SkillAssessmentExam::with([
            'user',
            'section',
            'examTemplate',
            'answers.question.options'
        ])->findOrFail($id);

        return view('skill-assessment.exam-results.view', compact('exam'));
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

        $answer = SkillAssessmentExamAnswer::findOrFail($request->answer_id);
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
            'message' => 'Score updated successfully',
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
