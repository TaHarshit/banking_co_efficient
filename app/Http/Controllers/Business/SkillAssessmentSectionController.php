<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Classes\Business\SkillAssessmentSectionCls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SkillAssessmentSectionController extends Controller
{
    protected $SectionCls;

    public function __construct(SkillAssessmentSectionCls $SectionCls)
    {
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
     * Display a listing of sections
     */
    public function Index(Request $request)
    {
        $examTemplateId = $request->query('exam_template_id');
        $examTemplate = null;

        if ($examTemplateId) {
            $examTemplate = \App\Models\SkillAssessmentExamTemplate::where('business_id', $this->getBusinessId())
                ->where('id', $examTemplateId)
                ->first();
            $sections = $this->SectionCls->GetSectionsByExamTemplate($examTemplateId, $this->getBusinessId());
        } else {
            $sections = $this->SectionCls->GetAllSections($this->getBusinessId());
        }

        return view('business.skill-assessments.sections.manage', compact('sections', 'examTemplate', 'examTemplateId'));
    }

    /**
     * Show the form for creating a new section
     */
    public function Create(Request $request)
    {
        $examTemplateId = $request->query('exam_template_id');
        $examTemplate = null;
        if ($examTemplateId) {
            $examTemplate = \App\Models\SkillAssessmentExamTemplate::where('business_id', $this->getBusinessId())
                ->where('id', $examTemplateId)
                ->first();
        }

        $nextOrder = $this->SectionCls->GetNextOrder($this->getBusinessId(), $examTemplateId);
        return view('business.skill-assessments.sections.addedit', compact('nextOrder', 'examTemplate', 'examTemplateId'));
    }

    /**
     * Show the form for editing a section
     */
    public function Edit($id)
    {
        $data = $this->SectionCls->GetSection($id, $this->getBusinessId());
        if (!$data) {
            return redirect()->route('business.skill-assessment.sections');
        }

        $examTemplateId = $data->skill_assessment_exam_template_id;
        $examTemplate = null;
        if ($examTemplateId) {
            $examTemplate = \App\Models\SkillAssessmentExamTemplate::where('business_id', $this->getBusinessId())
                ->where('id', $examTemplateId)
                ->first();
        }

        return view('business.skill-assessments.sections.addedit', compact('data', 'examTemplate', 'examTemplateId'));
    }

    /**
     * Store a newly created or updated section
     */
    public function Store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $id = $request->input('id', 0);
        return $this->SectionCls->StoreSection($request, $this->getBusinessId(), $id);
    }

    /**
     * Remove the specified section
     */
    public function Delete($id)
    {
        return $this->SectionCls->DeleteSection($id, $this->getBusinessId());
    }

    /**
     * Change section status (AJAX)
     */
    public function ChangeStatus(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        return $this->SectionCls->ChangeStatus($id, $status, $this->getBusinessId());
    }
}
