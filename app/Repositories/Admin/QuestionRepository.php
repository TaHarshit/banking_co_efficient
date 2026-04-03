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
     * Get all questions for a section ordered by display order
     */
    public function GetQuestionsBySection($sectionId)
    {
        return $this->model->where('section_id', $sectionId)->orderBy('order')->get();
    }

    /**
     * Get all active questions for a section
     */
    public function GetActiveQuestionsBySection($sectionId)
    {
        return $this->model
            ->where('section_id', $sectionId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    /**
     * Get a single question by ID
     */
    public function GetQuestion($id)
    {
        return $this->model->find($id);
    }

    /**
     * Get question with options
     */
    public function GetQuestionWithOptions($id)
    {
        return $this->model->with('options')->find($id);
    }

    /**
     * Store or update a question
     */
    public function StoreQuestion($data, $id = 0)
    {
        $questionData = [
            'section_id' => $data['section_id'],
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
            $this->model->where('id', $id)->update($questionData);
            return $this->model->find($id);
        } else {
            // Set order to max + 1 for this section if not provided
            if (!isset($data['order']) || $data['order'] == 0) {
                $maxOrder = $this->model->where('section_id', $data['section_id'])->max('order') ?? 0;
                $questionData['order'] = $maxOrder + 1;
            }
            return $this->model->create($questionData);
        }
    }

    /**
     * Delete a question
     */
    public function DeleteQuestion($id)
    {
        return $this->model->where('id', $id)->delete();
    }

    /**
     * Update question order within a section
     */
    public function UpdateOrder($orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            $this->model->where('id', $id)->update(['order' => $index + 1]);
        }
        return true;
    }

    /**
     * Change question status
     */
    public function ChangeStatus($id, $status)
    {
        return $this->model->where('id', $id)->update(['is_active' => $status]);
    }

    /**
     * Get next order number for a section
     */
    public function GetNextOrder($sectionId)
    {
        $maxOrder = $this->model->where('section_id', $sectionId)->max('order') ?? 0;
        return $maxOrder + 1;
    }
    /**
     * Delete all questions for a section
     */
    public function DeleteAllQuestions($sectionId)
    {
        $questions = $this->model->where('section_id', $sectionId)->get();

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

        return true;
    }
}
