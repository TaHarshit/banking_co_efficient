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

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by exam template
        if ($request->has('exam_template_id') && $request->exam_template_id !== '') {
            $query->where('skill_assessment_exam_template_id', $request->exam_template_id);
        }

        // Filter by section
        if ($request->has('section_id') && $request->section_id !== '') {
            $query->where('skill_assessment_section_id', $request->section_id);
        }

        $exams = $query->paginate(20);
        $sections = \App\Models\SkillAssessmentSection::where('is_active', true)->get();
        $examTemplates = SkillAssessmentExamTemplate::where('is_active', true)->get();

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
                'status' => $exam->status,
            ],
        ]);
    }
}
