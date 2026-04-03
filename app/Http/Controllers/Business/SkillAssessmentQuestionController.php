<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Classes\Business\SkillAssessmentQuestionCls;
use App\Classes\Business\SkillAssessmentSectionCls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SkillAssessmentQuestionController extends Controller
{
    protected $QuestionCls;
    protected $SectionCls;

    public function __construct(SkillAssessmentQuestionCls $QuestionCls, SkillAssessmentSectionCls $SectionCls)
    {
        $this->QuestionCls = $QuestionCls;
        $this->SectionCls = $SectionCls;
    }

    /**
     * Get current business ID
     */
    private function getBusinessId()
    {
        return Auth::guard('business')->id();
    }

    /**
     * Display a listing of questions for a section
     */
    public function Index($sectionId)
    {
        $section = $this->SectionCls->GetSection($sectionId, $this->getBusinessId());
        if (!$section) {
            return redirect()->route('business.skill-assessment.sections');
        }

        $questions = $this->QuestionCls->GetQuestionsBySection($sectionId, $this->getBusinessId());
        return view('business.skill-assessments.questions.manage', compact('section', 'questions'));
    }

    /**
     * Show the form for creating a new question
     */
    public function Create($sectionId)
    {
        $section = $this->SectionCls->GetSection($sectionId, $this->getBusinessId());
        if (!$section) {
            return redirect()->route('business.skill-assessment.sections');
        }

        $nextOrder = $this->QuestionCls->GetNextOrder($sectionId, $this->getBusinessId());
        $typeOptions = $this->QuestionCls->GetTypeOptions();
        return view('business.skill-assessments.questions.addedit', compact('section', 'nextOrder', 'typeOptions'));
    }

    /**
     * Show the form for editing a question
     */
    public function Edit($sectionId, $id)
    {
        $section = $this->SectionCls->GetSection($sectionId, $this->getBusinessId());
        if (!$section) {
            return redirect()->route('business.skill-assessment.sections');
        }

        $data = $this->QuestionCls->GetQuestion($id, $this->getBusinessId());
        if (!$data) {
            return redirect()->route('business.skill-assessment.questions', $sectionId);
        }

        $typeOptions = $this->QuestionCls->GetTypeOptions();
        return view('business.skill-assessments.questions.addedit', compact('section', 'data', 'typeOptions'));
    }

    /**
     * Store a newly created or updated question
     */
    public function Store(Request $request, $sectionId)
    {
        $request->validate([
            'question_type' => 'required|string',
            'question_text' => 'required|string|max:1000',
        ]);

        $id = $request->input('id', 0);
        return $this->QuestionCls->StoreQuestion($request, $sectionId, $this->getBusinessId(), $id);
    }

    /**
     * Remove the specified question
     */
    public function Delete($sectionId, $id)
    {
        return $this->QuestionCls->DeleteQuestion($id, $sectionId, $this->getBusinessId());
    }

    /**
     * Change question status (AJAX)
     */
    public function ChangeStatus(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        return $this->QuestionCls->ChangeStatus($id, $status, $this->getBusinessId());
    }

    /**
     * Export questions to CSV
     */
    public function Export($sectionId)
    {
        $section = $this->SectionCls->GetSection($sectionId, $this->getBusinessId());
        if (!$section) {
            return redirect()->route('business.skill-assessment.sections');
        }

        return $this->QuestionCls->ExportQuestions($sectionId, $this->getBusinessId(), $section);
    }

    /**
     * Import questions from CSV
     */
    public function Import(Request $request, $sectionId)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        $section = $this->SectionCls->GetSection($sectionId, $this->getBusinessId());
        if (!$section) {
            return redirect()->route('business.skill-assessment.sections');
        }

        return $this->QuestionCls->ImportQuestions($request, $sectionId, $this->getBusinessId());
    }

    /**
     * Download example CSV file
     */
    public function DownloadExample()
    {
        return $this->QuestionCls->DownloadExample();
    }

    /**
     * Delete all questions in a section
     */
    public function DeleteAll($sectionId)
    {
        $section = $this->SectionCls->GetSection($sectionId, $this->getBusinessId());
        if (!$section) {
            return redirect()->route('business.skill-assessment.sections');
        }

        return $this->QuestionCls->DeleteAllQuestions($sectionId, $this->getBusinessId());
    }
}
