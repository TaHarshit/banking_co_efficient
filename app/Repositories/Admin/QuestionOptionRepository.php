<?php

namespace App\Repositories\Admin;

use App\Models\QuestionOption;
use App\Repositories\BaseRepository;

class QuestionOptionRepository extends BaseRepository
{
    public function model()
    {
        return QuestionOption::class;
    }

    /**
     * Get all options for a question ordered by display order
     */
    public function GetOptionsByQuestion($questionId)
    {
        return $this->model->where('question_id', $questionId)->orderBy('order')->get();
    }

    /**
     * Get all active options for a question
     */
    public function GetActiveOptionsByQuestion($questionId)
    {
        return $this->model
            ->where('question_id', $questionId)
            ->where('is_active', true)
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
            'question_id' => $data['question_id'],
            'option_text_en' => $data['option_text_en'],
            'option_text_fr' => $data['option_text_fr'],
            'option_subtitle_en' => $data['option_subtitle_en'] ?? null,
            'option_subtitle_fr' => $data['option_subtitle_fr'] ?? null,
            'order' => $data['order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ];

        if ($id > 0) {
            $this->model->where('id', $id)->update($optionData);
            return $this->model->find($id);
        } else {
            return $this->model->create($optionData);
        }
    }

    /**
     * Delete an option
     */
    public function DeleteOption($id)
    {
        return $this->model->where('id', $id)->delete();
    }

    /**
     * Delete all options for a question
     */
    public function DeleteOptionsByQuestion($questionId)
    {
        return $this->model->where('question_id', $questionId)->delete();
    }

    /**
     * Bulk store options for a question
     */
    public function StoreOptionsForQuestion($questionId, $options)
    {
        // First delete existing options
        $this->DeleteOptionsByQuestion($questionId);

        // Then create new options
        foreach ($options as $index => $optionData) {
            $optionData['question_id'] = $questionId;
            $optionData['order'] = $index + 1;
            $this->StoreOption($optionData);
        }

        return true;
    }
}
