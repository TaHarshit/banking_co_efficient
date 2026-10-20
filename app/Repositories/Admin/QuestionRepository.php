<?php

namespace App\Repositories\Admin;

use App\Models\Question;
use App\Repositories\BaseRepository;

class QuestionRepository extends BaseRepository
{
    public function model()
    {
        return Question::class;
    }

    /**
     * Get all questions for a section ordered by display order (admin only)
     */
    public function GetQuestionsBySection($sectionId)
    {
        return $this->model
            ->where('section_id', $sectionId)
            ->whereNull('business_id')
            ->orderBy('order')
            ->get();
    }

    /**
     * Get all active questions for a section (admin only)
     */
    public function GetActiveQuestionsBySection($sectionId)
    {
        return $this->model
            ->where('section_id', $sectionId)
            ->whereNull('business_id')
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    /**
     * Get a single question by ID (admin only)
     */
    public function GetQuestion($id)
    {
        return $this->model->whereNull('business_id')->find($id);
    }

    /**
     * Get question with options (admin only)
     */
    public function GetQuestionWithOptions($id)
    {
        return $this->model->whereNull('business_id')->with('options')->find($id);
    }

    /**
     * Store or update a question
     */
    public function StoreQuestion($data, $id = 0)
    {
        $questionData = [
            'section_id' => $data['section_id'],
            'business_id' => null,
            'question_type' => $data['question_type'],
            'question_text_en' => $data['question_text_en'],
            'question_text_fr' => $data['question_text_fr'],
            'helper_text_en' => $data['helper_text_en'] ?? null,
            'helper_text_fr' => $data['helper_text_fr'] ?? null,
            'order' => $data['order'] ?? 0,
            'is_required' => $data['is_required'] ?? false,
            'settings' => $data['settings'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ];

        if ($id > 0) {
            $this->model->where('id', $id)->whereNull('business_id')->update($questionData);
            $question = $this->model->whereNull('business_id')->find($id);
            if ($question) {
                logAdminActivity('Personalized Experience', 'Update Question', $id, "Updated question in section ID: {$questionData['section_id']}", $questionData);
            }
            return $question;
        } else {
            // Set order to max + 1 for this section if not provided
            if (!isset($data['order']) || $data['order'] == 0) {
                $maxOrder = $this->model->where('section_id', $data['section_id'])->whereNull('business_id')->max('order') ?? 0;
                $questionData['order'] = $maxOrder + 1;
            }
            $question = $this->model->create($questionData);
            if ($question) {
                logAdminActivity('Personalized Experience', 'Add Question', $question->id, "Added new question in section ID: {$questionData['section_id']}", $questionData);
            }
            return $question;
        }
    }

    /**
     * Delete a question
     */
    public function DeleteQuestion($id)
    {
        $question = $this->model->whereNull('business_id')->find($id);
        $sectionId = $question ? $question->section_id : "N/A";
        $delete = $this->model->where('id', $id)->whereNull('business_id')->delete();
        if ($delete) {
            logAdminActivity('Personalized Experience', 'Delete Question', $id, "Deleted question from section ID: $sectionId");
        }
        return $delete;
    }

    /**
     * Update question order within a section
     */
    public function UpdateOrder($orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            $this->model->where('id', $id)->whereNull('business_id')->update(['order' => $index + 1]);
        }
        return true;
    }

    /**
     * Change question status
     */
    public function ChangeStatus($id, $status)
    {
        $update = $this->model->where('id', $id)->whereNull('business_id')->update(['is_active' => $status]);
        if ($update) {
            $statusText = $status ? 'Active' : 'Inactive';
            logAdminActivity('Personalized Experience', 'Status Change Question', $id, "Changed question status to: $statusText");
        }
        return $update;
    }

    /**
     * Get next order number for a section
     */
    public function GetNextOrder($sectionId)
    {
        $maxOrder = $this->model->where('section_id', $sectionId)->whereNull('business_id')->max('order') ?? 0;
        return $maxOrder + 1;
    }
    /**
     * Delete all questions for a section
     */
    public function DeleteAllQuestions($sectionId)
    {
        $questions = $this->model->where('section_id', $sectionId)->whereNull('business_id')->get();

        foreach ($questions as $question) {
            // Delete related options
            $question->options()->delete();

            // Delete related responses
            if (method_exists($question, 'responses')) {
                $question->responses()->delete();
            }

            // Delete the question
            $question->delete();
        }

        logAdminActivity('Personalized Experience', 'Delete All Questions', null, "Deleted all questions for section ID: $sectionId");
        return true;
    }
}
