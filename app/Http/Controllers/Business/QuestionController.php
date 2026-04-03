<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Classes\Business\QuestionCls;
use App\Classes\Business\SectionCls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionController extends Controller
{
    protected $QuestionCls;
    protected $SectionCls;

    public function __construct(QuestionCls $QuestionCls, SectionCls $SectionCls)
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
            return redirect()->route('business.sections');
        }

        $questions = $this->QuestionCls->GetQuestionsBySection($sectionId, $this->getBusinessId());
        return view('business.questions.manage', compact('section', 'questions'));
    }

    /**
     * Show the form for creating a new question
     */
    public function Create($sectionId)
    {
        $section = $this->SectionCls->GetSection($sectionId, $this->getBusinessId());
        if (!$section) {
            return redirect()->route('business.sections');
        }

        $nextOrder = $this->QuestionCls->GetNextOrder($sectionId, $this->getBusinessId());
        $typeOptions = $this->QuestionCls->GetTypeOptions();
        return view('business.questions.addedit', compact('section', 'nextOrder', 'typeOptions'));
    }

    /**
     * Show the form for editing a question
     */
    public function Edit($sectionId, $id)
    {
        $section = $this->SectionCls->GetSection($sectionId, $this->getBusinessId());
        if (!$section) {
            return redirect()->route('business.sections');
        }

        $data = $this->QuestionCls->GetQuestion($id, $this->getBusinessId());
        if (!$data) {
            return redirect()->route('business.questions', $sectionId);
        }

        $typeOptions = $this->QuestionCls->GetTypeOptions();
        return view('business.questions.addedit', compact('section', 'data', 'typeOptions'));
    }

    /**
     * Store a newly created or updated question
     */
    public function Store(Request $request, $sectionId)
    {
        $request->validate([
            'question_type' => 'required|string',
            'question_text_en' => 'required|string|max:500',
            'question_text_fr' => 'required|string|max:500',
        ]);

        $id = $request->input('id', 0);
        return $this->QuestionCls->StoreQuestion($request, $sectionId, $this->getBusinessId(), $id);
    }

    /**
     * Remove the specified question
     */
    public function Delete($sectionId, $id)
    {
        return $this->QuestionCls->DeleteQuestion($id, $this->getBusinessId());
    }

    /**
     * Update question order (AJAX)
     */
    public function UpdateOrder(Request $request)
    {
        $orderedIds = $request->input('order', []);
        $result = $this->QuestionCls->UpdateOrder($orderedIds, $this->getBusinessId());
        return response()->json(['success' => $result]);
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
            return redirect()->route('business.sections');
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
            return redirect()->route('business.sections');
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
            return redirect()->route('business.sections');
        }

        return $this->QuestionCls->DeleteAllQuestions($sectionId, $this->getBusinessId());
    }
}
