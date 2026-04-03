<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Classes\Admin\SkillAssessmentQuestionCls;
use App\Models\SkillAssessmentExamTemplate;
use App\Models\SkillAssessmentSection;
use Illuminate\Http\Request;

class SkillAssessmentQuestionController extends Controller
{
    protected $SkillAssessmentQuestionCls;

    public function __construct(SkillAssessmentQuestionCls $SkillAssessmentQuestionCls)
    {
        $this->SkillAssessmentQuestionCls = $SkillAssessmentQuestionCls;
    }

    /**
     * Display a listing of all skill assessment questions
     */
    public function ManageSkillAssessmentQuestions(Request $request)
    {
        $selectedSectionId = $request->query('section_id');
        $examTemplateId = $request->query('exam_template_id');

        // Determine exam template from section if not directly provided
        if ($selectedSectionId && !$examTemplateId) {
            $section = SkillAssessmentSection::find($selectedSectionId);
            if ($section) {
                $examTemplateId = $section->skill_assessment_exam_template_id;
            }
        }

        $examTemplate = $examTemplateId ? SkillAssessmentExamTemplate::find($examTemplateId) : null;

        // Only show sections belonging to this exam template
        if ($examTemplateId) {
            $sections = $this->SkillAssessmentQuestionCls->GetSectionsByExamTemplate($examTemplateId);
        } else {
            $sections = $this->SkillAssessmentQuestionCls->GetAllSections();
        }

        if ($selectedSectionId) {
            $questions = $this->SkillAssessmentQuestionCls->GetQuestionsBySection($selectedSectionId);
        } else {
            $questions = $this->SkillAssessmentQuestionCls->GetAllQuestions();
        }

        return view('skill-assessment.questions.manage', compact('sections', 'questions', 'selectedSectionId', 'examTemplate', 'examTemplateId'));
    }

    /**
     * Show the form for creating a new skill assessment question
     */
    public function CreateSkillAssessmentQuestion(Request $request)
    {
        $examTemplateId = $request->query('exam_template_id');
        $sectionId = $request->query('section_id');
        $examTemplate = $examTemplateId ? SkillAssessmentExamTemplate::find($examTemplateId) : null;

        // Only show sections belonging to this exam template
        if ($examTemplateId) {
            $sections = $this->SkillAssessmentQuestionCls->GetSectionsByExamTemplate($examTemplateId);
        } else {
            $sections = $this->SkillAssessmentQuestionCls->GetAllSections();
        }

        $nextOrder = 1;
        $questionTypes = $this->SkillAssessmentQuestionCls->GetQuestionTypes();

        return view('skill-assessment.questions.addedit', compact('sections', 'nextOrder', 'questionTypes', 'examTemplate', 'examTemplateId', 'sectionId'));
    }

    /**
     * Show the form for editing a skill assessment question
     */
    public function UpdateSkillAssessmentQuestion($id)
    {
        $data = $this->SkillAssessmentQuestionCls->GetQuestionWithOptions($id);
        if (!$data) {
            return redirect()->route('manageskillassessmentquestions');
        }

        // Get exam template from the question's section
        $section = SkillAssessmentSection::find($data->skill_assessment_section_id);
        $examTemplateId = $section ? $section->skill_assessment_exam_template_id : null;
        $examTemplate = $examTemplateId ? SkillAssessmentExamTemplate::find($examTemplateId) : null;

        // Only show sections belonging to this exam template
        if ($examTemplateId) {
            $sections = $this->SkillAssessmentQuestionCls->GetSectionsByExamTemplate($examTemplateId);
        } else {
            $sections = $this->SkillAssessmentQuestionCls->GetAllSections();
        }

        $questionTypes = $this->SkillAssessmentQuestionCls->GetQuestionTypes();

        return view('skill-assessment.questions.addedit', compact('data', 'sections', 'questionTypes', 'examTemplate', 'examTemplateId'));
    }

    /**
     * Store a newly created or updated skill assessment question
     */
    public function StoreSkillAssessmentQuestion(Request $request)
    {
        $request->validate([
            'skill_assessment_section_id' => 'required|exists:skill_assessment_sections,id',
            'question_type' => 'required|in:radio,multi_select,open_text',
            'question_text' => 'required|string|max:1000',
            'helper_text' => 'nullable|string|max:500',
            'order' => 'required|integer|min:1',
        ]);

        // Validate options if question type requires them
        if (in_array($request->input('question_type'), ['radio', 'multi_select'])) {
            $request->validate([
                'options' => 'required|array|min:2',
                'options.*.option_text' => 'required|string|max:255',
                'options.*.weightage' => 'required|numeric|min:0|max:100',
            ]);
        }

        $id = $request->input('id', 0);
        return $this->SkillAssessmentQuestionCls->StoreQuestion($request, $id);
    }

    /**
     * Remove the specified skill assessment question
     */
    public function DeleteSkillAssessmentQuestion($id)
    {
        return $this->SkillAssessmentQuestionCls->DeleteQuestion($id);
    }

    /**
     * Change question status (AJAX)
     */
    public function ChangeStatus(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        return $this->SkillAssessmentQuestionCls->ChangeStatus($id, $status);
    }

    /**
     * Export questions
     */
    public function ExportQuestions($section_id)
    {
        return $this->SkillAssessmentQuestionCls->ExportQuestions($section_id);
    }

    /**
     * Import questions
     */
    public function ImportQuestions(Request $request)
    {
        $request->validate([
            'skill_assessment_section_id' => 'required|exists:skill_assessment_sections,id',
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        return $this->SkillAssessmentQuestionCls->ImportQuestions($request);
    }

    /**
     * Download example file
     */
    public function DownloadExample()
    {
        return $this->SkillAssessmentQuestionCls->DownloadExample();
    }

    /**
     * Delete all questions
     */
    public function DeleteAllQuestions($section_id)
    {
        return $this->SkillAssessmentQuestionCls->DeleteAllQuestions($section_id);
    }
}
