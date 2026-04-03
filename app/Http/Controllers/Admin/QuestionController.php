<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Classes\Admin\QuestionCls;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    protected $QuestionCls;

    public function __construct(QuestionCls $QuestionCls)
    {
        $this->QuestionCls = $QuestionCls;
    }

    /**
     * Display a listing of questions for a section
     */
    public function ManageQuestions($section_id)
    {
        $section = $this->QuestionCls->GetSection($section_id);
        if (!$section) {
            return redirect()->route('managesections');
        }

        $questions = $this->QuestionCls->GetQuestionsBySection($section_id);
        return view('questions.manage', compact('section', 'questions'));
    }

    /**
     * Show the form for creating a new question
     */
    public function CreateQuestion($section_id)
    {
        $section = $this->QuestionCls->GetSection($section_id);
        if (!$section) {
            return redirect()->route('managesections');
        }

        $nextOrder = $this->QuestionCls->GetNextOrder($section_id);
        $questionTypes = $this->QuestionCls->GetQuestionTypes();

        return view('questions.addedit', compact('section', 'nextOrder', 'questionTypes'));
    }

    /**
     * Show the form for editing a question
     */
    public function UpdateQuestion($id)
    {
        $data = $this->QuestionCls->GetQuestionWithOptions($id);
        if (!$data) {
            return redirect()->route('managesections');
        }

        $section = $this->QuestionCls->GetSection($data->section_id);
        $questionTypes = $this->QuestionCls->GetQuestionTypes();

        return view('questions.addedit', compact('data', 'section', 'questionTypes'));
    }

    /**
     * Store a newly created or updated question
     */
    public function StoreQuestion(Request $request)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'question_type' => 'required|in:single_select,multi_select,rating_scale,text_input',
            'question_text_en' => 'required|string|max:500',
            'question_text_fr' => 'required|string|max:500',
        ]);

        $id = $request->input('id', 0);
        return $this->QuestionCls->StoreQuestion($request, $id);
    }

    /**
     * Remove the specified question
     */
    public function DeleteQuestion($id)
    {
        return $this->QuestionCls->DeleteQuestion($id);
    }

    /**
     * Change question status (AJAX)
     */
    public function ChangeStatus(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        return $this->QuestionCls->ChangeStatus($id, $status);
    }
    /**
     * Export questions
     */
    public function ExportQuestions($section_id)
    {
        return $this->QuestionCls->ExportQuestions($section_id);
    }

    /**
     * Import questions
     */
    public function ImportQuestions(Request $request)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        return $this->QuestionCls->ImportQuestions($request);
    }
    /**
     * Download example file
     */
    public function DownloadExample()
    {
        return $this->QuestionCls->DownloadExample();
    }

    /**
     * Delete all questions
     */
    public function DeleteAllQuestions($section_id)
    {
        return $this->QuestionCls->DeleteAllQuestions($section_id);
    }
}
