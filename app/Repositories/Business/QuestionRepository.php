<?php

namespace App\Repositories\Business;

use App\Models\Question;
use App\Models\QuestionOption;

class QuestionRepository
{
    /**
     * Get all questions for a section
     */
    public function GetQuestionsBySection($sectionId, $businessId)
    {
        return Question::where('section_id', $sectionId)
            ->where('business_id', $businessId)
            ->orderBy('order')
            ->get();
    }

    /**
     * Get a single question by ID
     */
    public function GetQuestion($id, $businessId)
    {
        return Question::where('id', $id)
            ->where('business_id', $businessId)
            ->with('options')
            ->first();
    }

    /**
     * Get next order number for a section
     */
    public function GetNextOrder($sectionId, $businessId)
    {
        $maxOrder = Question::where('section_id', $sectionId)
            ->where('business_id', $businessId)
            ->max('order');
        return ($maxOrder ?? 0) + 1;
    }

    /**
     * Store or update a question
     */
    public function StoreQuestion($data, $id = 0)
    {
        if ($id > 0) {
            $question = Question::find($id);
            if ($question) {
                $question->update($data);
                return $question;
            }
        }
        return Question::create($data);
    }

    /**
     * Delete a question
     */
    public function DeleteQuestion($id, $businessId)
    {
        return Question::where('id', $id)
            ->where('business_id', $businessId)
            ->delete();
    }

    /**
     * Update question order
     */
    public function UpdateOrder($orderedIds, $businessId)
    {
        foreach ($orderedIds as $order => $id) {
            Question::where('id', $id)
                ->where('business_id', $businessId)
                ->update(['order' => $order + 1]);
        }
        return true;
    }

    /**
     * Change question status
     */
    public function ChangeStatus($id, $status, $businessId)
    {
        return Question::where('id', $id)
            ->where('business_id', $businessId)
            ->update(['is_active' => $status]);
    }

    /**
     * Store or update question options
     */
    public function StoreOptions($questionId, $options)
    {
        // Delete existing options
        QuestionOption::where('question_id', $questionId)->delete();

        // Create new options
        foreach ($options as $order => $option) {
            QuestionOption::create([
                'question_id' => $questionId,
                'option_text_en' => $option['text_en'] ?? '',
                'option_text_fr' => $option['text_fr'] ?? '',
                'option_subtitle_en' => $option['subtitle_en'] ?? null,
                'option_subtitle_fr' => $option['subtitle_fr'] ?? null,
                'order' => $order + 1,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Get options for a question
     */
    public function GetOptionsByQuestion($questionId)
    {
        return QuestionOption::where('question_id', $questionId)
            ->orderBy('order')
            ->get();
    }
}
