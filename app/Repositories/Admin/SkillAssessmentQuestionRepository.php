<?php

namespace App\Repositories\Admin;

use App\Models\SkillAssessmentQuestion;
use App\Repositories\BaseRepository;

class SkillAssessmentQuestionRepository extends BaseRepository
{
    public function model()
    {
        return SkillAssessmentQuestion::class;
    }

    /**
     * Get all questions across all sections
     */
    public function GetAllQuestions($source = null)
    {
        $query = $this->model->with(['section', 'options' => function ($query) {
            $query->orderBy('order');
        }])
            ->orderBy('skill_assessment_section_id')
            ->orderBy('order');

        if ($source === 'business') {
            $query->whereNotNull('business_id');
        } elseif ($source === 'global') {
            $query->whereNull('business_id');
        }

        return $query->get();
    }

    /**
     * Get questions by section
     */
    public function GetQuestionsBySection($sectionId, $source = null)
    {
        $query = $this->model->where('skill_assessment_section_id', $sectionId)
            ->with(['options' => function ($query) {
                $query->orderBy('order');
            }])
            ->orderBy('order');

        if ($source === 'business') {
            $query->whereNotNull('business_id');
        } elseif ($source === 'global') {
            $query->whereNull('business_id');
        }

        return $query->get();
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
        return $this->model->with(['options' => function ($query) {
            $query->orderBy('order');
        }])->find($id);
    }

    /**
     * Store or update a question
     */
    public function StoreQuestion($data, $id = 0)
    {
        $questionData = [
            'skill_assessment_section_id' => $data['skill_assessment_section_id'],
            'question_type' => $data['question_type'],
            'question_text' => $data['question_text'],
            'question_text_fr' => $data['question_text_fr'] ?? null,
            'helper_text' => $data['helper_text'] ?? null,
            'helper_text_fr' => $data['helper_text_fr'] ?? null,
            'order' => $data['order'] ?? 0,
            'is_required' => $data['is_required'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ];

        if ($id == 0) {
            return $this->model->create($questionData);
        } else {
            $question = $this->model->find($id);
            if ($question) {
                $question->update($questionData);
                return $question;
            }
            return null;
        }
    }

    /**
     * Delete a question
     */
    public function DeleteQuestion($id)
    {
        $question = $this->model->find($id);
        if ($question) {
            $question->delete();
            return true;
        }
        return false;
    }

    /**
     * Change question status
     */
    public function ChangeStatus($id, $status)
    {
        $question = $this->model->find($id);
        if ($question) {
            $question->update(['is_active' => $status]);
            return true;
        }
        return false;
    }

    /**
     * Get next order number for section
     */
    public function GetNextOrder($sectionId)
    {
        $maxOrder = $this->model->where('skill_assessment_section_id', $sectionId)->max('order');
        return $maxOrder ? $maxOrder + 1 : 1;
    }

    /**
     * Get question types
     */
    public function GetQuestionTypes()
    {
        return [
            'radio' => 'Radio (Single Select)',
            'multi_select' => 'Multi Select (With Weightage)',
            'open_text' => 'Open Text',
        ];
    }

    /**
     * Get section for question
     */
    public function GetSection($sectionId)
    {
        return \App\Models\SkillAssessmentSection::find($sectionId);
    }
}
