<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Classes\Business\SkillAssessmentExamTemplateCls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SkillAssessmentExamTemplateController extends Controller
{
    protected $SkillAssessmentExamTemplateCls;

    public function __construct(SkillAssessmentExamTemplateCls $SkillAssessmentExamTemplateCls)
    {
        $this->SkillAssessmentExamTemplateCls = $SkillAssessmentExamTemplateCls;
    }

    /**
     * Get current business ID
     */
    private function getBusinessId()
    {
        return Auth::guard('business')->id();
    }

    /**
     * Display a listing of exam templates
     */
    public function ManageExamTemplates(Request $request)
    {
        $examTemplates = $this->SkillAssessmentExamTemplateCls->GetAllExamTemplates($this->getBusinessId());
        return view('business.skill-assessments.exams.manage', compact('examTemplates'));
    }

    /**
     * Show the form for creating a new exam template
     */
    public function CreateExamTemplate()
    {
        $nextOrder = $this->SkillAssessmentExamTemplateCls->GetNextOrder($this->getBusinessId());
        return view('business.skill-assessments.exams.addedit', compact('nextOrder'));
    }

    /**
     * Show the form for editing an exam template
     */
    public function UpdateExamTemplate($id)
    {
        $data = $this->SkillAssessmentExamTemplateCls->GetExamTemplate($id, $this->getBusinessId());
        if (!$data) {
            return redirect()->route('business.skill-assessment.exams');
        }
        return view('business.skill-assessments.exams.addedit', compact('data'));
    }

    /**
     * Store a newly created or updated exam template
     */
    public function StoreExamTemplate(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:1',
            'passing_percentage' => 'nullable|numeric|min:0|max:100',
            'order' => 'required|integer|min:1',
        ]);

        $id = $request->input('id', 0);
        return $this->SkillAssessmentExamTemplateCls->StoreExamTemplate($request, $this->getBusinessId(), $id);
    }

    /**
     * Remove the specified exam template
     */
    public function DeleteExamTemplate($id)
    {
        return $this->SkillAssessmentExamTemplateCls->DeleteExamTemplate($id, $this->getBusinessId());
    }

    /**
     * Change exam template status (AJAX)
     */
    public function ChangeStatus(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        return $this->SkillAssessmentExamTemplateCls->ChangeStatus($id, $status, $this->getBusinessId());
    }
}
