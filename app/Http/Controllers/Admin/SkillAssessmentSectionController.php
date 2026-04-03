<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Classes\Admin\SkillAssessmentSectionCls;
use App\Models\SkillAssessmentExamTemplate;
use Illuminate\Http\Request;

class SkillAssessmentSectionController extends Controller
{
    protected $SkillAssessmentSectionCls;

    public function __construct(SkillAssessmentSectionCls $SkillAssessmentSectionCls)
    {
        $this->SkillAssessmentSectionCls = $SkillAssessmentSectionCls;
    }

    /**
     * Display a listing of skill assessment sections
     */
    public function ManageSkillAssessmentSections(Request $request)
    {
        $examTemplateId = $request->query('exam_template_id');
        $examTemplate = null;

        if ($examTemplateId) {
            $examTemplate = SkillAssessmentExamTemplate::find($examTemplateId);
            $sections = $this->SkillAssessmentSectionCls->GetSectionsByExamTemplate($examTemplateId);
        } else {
            $sections = $this->SkillAssessmentSectionCls->GetAllSections();
        }

        return view('skill-assessment.sections.manage', compact('sections', 'examTemplate', 'examTemplateId'));
    }

    /**
     * Show the form for creating a new skill assessment section
     */
    public function CreateSkillAssessmentSection(Request $request)
    {
        $examTemplateId = $request->query('exam_template_id');
        $examTemplate = $examTemplateId ? SkillAssessmentExamTemplate::find($examTemplateId) : null;
        $nextOrder = $this->SkillAssessmentSectionCls->GetNextOrder();
        return view('skill-assessment.sections.addedit', compact('nextOrder', 'examTemplate', 'examTemplateId'));
    }

    /**
     * Show the form for editing a skill assessment section
     */
    public function UpdateSkillAssessmentSection($id)
    {
        $data = $this->SkillAssessmentSectionCls->GetSection($id);
        if (!$data) {
            return redirect()->route('manageskillassessmentsections');
        }
        $examTemplateId = $data->skill_assessment_exam_template_id;
        $examTemplate = $examTemplateId ? SkillAssessmentExamTemplate::find($examTemplateId) : null;
        return view('skill-assessment.sections.addedit', compact('data', 'examTemplate', 'examTemplateId'));
    }

    /**
     * Store a newly created or updated skill assessment section
     */
    public function StoreSkillAssessmentSection(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'order' => 'required|integer|min:1',
        ]);

        $id = $request->input('id', 0);
        return $this->SkillAssessmentSectionCls->StoreSection($request, $id);
    }

    /**
     * Remove the specified skill assessment section
     */
    public function DeleteSkillAssessmentSection($id)
    {
        return $this->SkillAssessmentSectionCls->DeleteSection($id);
    }

    /**
     * Update section order (AJAX)
     */
    public function UpdateOrder(Request $request)
    {
        $orderedIds = $request->input('order', []);
        $result = $this->SkillAssessmentSectionCls->UpdateOrder($orderedIds);
        return response()->json(['success' => $result]);
    }

    /**
     * Change section status (AJAX)
     */
    public function ChangeStatus(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        return $this->SkillAssessmentSectionCls->ChangeStatus($id, $status);
    }

    /**
     * Export skill assessment sections
     */
    public function ExportSections()
    {
        return $this->SkillAssessmentSectionCls->ExportSections();
    }

    /**
     * Import skill assessment sections
     */
    public function ImportSections(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        return $this->SkillAssessmentSectionCls->ImportSections($request);
    }
}
