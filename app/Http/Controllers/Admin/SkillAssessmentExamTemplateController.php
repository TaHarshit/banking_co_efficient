<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Classes\Admin\SkillAssessmentExamTemplateCls;
use Illuminate\Http\Request;

class SkillAssessmentExamTemplateController extends Controller
{
    protected $ExamTemplateCls;

    public function __construct(SkillAssessmentExamTemplateCls $ExamTemplateCls)
    {
        $this->ExamTemplateCls = $ExamTemplateCls;
    }

    /**
     * Display a listing of exam templates
     */
    public function ManageExamTemplates()
    {
        $source = request()->query('source');
        $examTemplates = $this->ExamTemplateCls->GetAllExamTemplates($source);
        return view('skill-assessment.exams.manage', compact('examTemplates', 'source'));
    }

    /**
     * Show the form for creating a new exam template
     */
    public function CreateExamTemplate()
    {
        $nextOrder = $this->ExamTemplateCls->GetNextOrder();
        return view('skill-assessment.exams.addedit', compact('nextOrder'));
    }

    /**
     * Show the form for editing an exam template
     */
    public function UpdateExamTemplate($id)
    {
        $data = $this->ExamTemplateCls->GetExamTemplate($id);
        if (!$data) {
            return redirect()->route('manageskillassessmentexamtemplates');
        }
        return view('skill-assessment.exams.addedit', compact('data'));
    }

    /**
     * Store a newly created or updated exam template
     */
    public function StoreExamTemplate(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'title_fr' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'description_fr' => 'nullable|string|max:1000',
            'exam_level' => 'nullable|string|in:beginner,intermediate,advanced,expert',
            'exam_level_fr' => 'nullable|string|in:débutant,intermédiaire,avancé,expert',
            'tags' => 'nullable|string',
            'tags_fr' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:1',
            'passing_percentage' => 'nullable|numeric|min:0|max:100',
            'order' => 'required|integer|min:1',
        ]);

        $id = $request->input('id', 0);
        return $this->ExamTemplateCls->StoreExamTemplate($request, $id);
    }

    /**
     * Remove the specified exam template
     */
    public function DeleteExamTemplate($id)
    {
        return $this->ExamTemplateCls->DeleteExamTemplate($id);
    }

    /**
     * Change exam template status (AJAX)
     */
    public function ChangeStatus(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        return $this->ExamTemplateCls->ChangeStatus($id, $status);
    }
}
