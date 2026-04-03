<?php

namespace App\Repositories\Admin;

use App\Models\SkillAssessmentQuestionOption;
use App\Repositories\BaseRepository;

class SkillAssessmentQuestionOptionRepository extends BaseRepository
{
    public function model()
    {
        return SkillAssessmentQuestionOption::class;
    }

    /**
     * Get options by question
     */
    public function GetOptionsByQuestion($questionId)
    {
        return $this->model->where('skill_assessment_question_id', $questionId)
            ->orderBy('order')
            ->get();
    }

    /**
     * Get a single option by ID
     */
    public function GetOption($id)
    {
        return $this->model->find($id);
    }

    /**
     * Store or update an option
     */
    public function StoreOption($data, $id = 0)
    {
        $optionData = [
            'skill_assessment_question_id' => $data['skill_assessment_question_id'],
            'option_text' => $data['option_text'],
            'option_text_fr' => $data['option_text_fr'] ?? null,
            'weightage' => $data['weightage'] ?? 0.00,
            'order' => $data['order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'is_correct' => $data['is_correct'] ?? false,
        ];

        if ($id == 0) {
            return $this->model->create($optionData);
        } else {
            $option = $this->model->find($id);
            if ($option) {
                $option->update($optionData);
                return $option;
            }
            return null;
        }
    }

    /**
     * Delete an option
     */
    public function DeleteOption($id)
    {
        $option = $this->model->find($id);
        if ($option) {
            $option->delete();
            return true;
        }
        return false;
    }

    /**
     * Change option status
     */
    public function ChangeStatus($id, $status)
    {
        $option = $this->model->find($id);
        if ($option) {
            $option->update(['is_active' => $status]);
            return true;
        }
        return false;
    }

    /**
     * Get next order number for question
     */
    public function GetNextOrder($questionId)
    {
        $maxOrder = $this->model->where('skill_assessment_question_id', $questionId)->max('order');
        return $maxOrder ? $maxOrder + 1 : 1;
    }

    /**
     * Store multiple options for a question
     */
    public function StoreOptionsForQuestion($questionId, $options, $correctAnswerIndex = null)
    {
        // Delete existing options first
        $this->model->where('skill_assessment_question_id', $questionId)->delete();

        // Store new options
        foreach ($options as $index => $optionData) {
            // Determine if this option is correct
            $isCorrect = false;
            if ($correctAnswerIndex !== null && $correctAnswerIndex == $index) {
                // For radio type - single correct answer
                $isCorrect = true;
            } elseif (isset($optionData['is_correct'])) {
                // For multi-select type - multiple correct answers
                $isCorrect = true;
            }

            $this->model->create([
                'skill_assessment_question_id' => $questionId,
                'option_text' => $optionData['option_text'],
                'option_text_fr' => $optionData['option_text_fr'] ?? null,
                'weightage' => $optionData['weightage'] ?? 0.00,
                'order' => $index + 1,
                'is_active' => true,
                'is_correct' => $isCorrect,
            ]);
        }

        return true;
    }
}
