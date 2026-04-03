<?php

namespace App\Classes\Admin;

use Illuminate\Support\Facades\Session;
use App\Repositories\Admin\SkillAssessmentExamTemplateRepository;
use Exception;

class SkillAssessmentExamTemplateCls
{
    protected $ExamTemplateRep;

    public function __construct(SkillAssessmentExamTemplateRepository $ExamTemplateRep)
    {
        $this->ExamTemplateRep = $ExamTemplateRep;
    }

    /**
     * Get all exam templates
     */
    public function GetAllExamTemplates()
    {
        try {
            return $this->ExamTemplateRep->GetAllExamTemplates();
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a single exam template by ID
     */
    public function GetExamTemplate($id)
    {
        try {
            return $this->ExamTemplateRep->GetExamTemplate($id);
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get exam template with sections
     */
    public function GetExamTemplateWithSections($id)
    {
        try {
            return $this->ExamTemplateRep->GetExamTemplateWithSections($id);
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store or update an exam template
     */
    public function StoreExamTemplate($request, $id = 0)
    {
        try {
            $data = $request->all();

            if ($id == 0) {
                $this->ExamTemplateRep->StoreExamTemplate($data);
                Session::flash('success', 'Exam template created successfully!');
            } else {
                $template = $this->ExamTemplateRep->StoreExamTemplate($data, $id);
                if ($template) {
                    Session::flash('success', 'Exam template updated successfully!');
                } else {
                    Session::flash('error', 'Exam template not found!');
                    return redirect()->route('manageskillassessmentexamtemplates');
                }
            }

            return redirect()->route('manageskillassessmentexamtemplates');
        } catch (Exception $e) {
            Session::flash('error', 'Something went wrong: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Delete an exam template
     */
    public function DeleteExamTemplate($id)
    {
        try {
            $result = $this->ExamTemplateRep->DeleteExamTemplate($id);
            if ($result) {
                Session::flash('success', 'Exam template deleted successfully!');
            } else {
                Session::flash('error', 'Exam template not found!');
            }
            return redirect()->route('manageskillassessmentexamtemplates');
        } catch (Exception $e) {
            Session::flash('error', 'Cannot delete exam template: ' . $e->getMessage());
            return redirect()->route('manageskillassessmentexamtemplates');
        }
    }

    /**
     * Change exam template status
     */
    public function ChangeStatus($id, $status)
    {
        try {
            $result = $this->ExamTemplateRep->ChangeStatus($id, $status);
            return $result ? 1 : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get next order number
     */
    public function GetNextOrder()
    {
        try {
            return $this->ExamTemplateRep->GetNextOrder();
        } catch (Exception $e) {
            return 1;
        }
    }
}
