<?php

namespace App\Repositories\Admin;

use App\Models\SkillAssessmentSection;
use App\Repositories\BaseRepository;

class SkillAssessmentSectionRepository extends BaseRepository
{
    public function model()
    {
        return SkillAssessmentSection::class;
    }

    /**
     * Get all sections ordered by display order
     */
    public function GetAllSections()
    {
        return $this->model->withCount('questions')->orderBy('order')->get();
    }

    /**
     * Get sections by exam template ID
     */
    public function GetSectionsByExamTemplate($examTemplateId)
    {
        return $this->model->where('skill_assessment_exam_template_id', $examTemplateId)
            ->withCount('questions')
            ->orderBy('order')
            ->get();
    }

    /**
     * Get all active sections ordered by display order
     */
    public function GetActiveSections()
    {
        return $this->model->where('is_active', true)
            ->withCount('questions')
            ->orderBy('order')
            ->get();
    }

    /**
     * Get a single section by ID
     */
    public function GetSection($id)
    {
        return $this->model->find($id);
    }

    /**
     * Get section with questions
     */
    public function GetSectionWithQuestions($id)
    {
        return $this->model->with(['questions' => function ($query) {
            $query->orderBy('order');
        }])->find($id);
    }

    /**
     * Store or update a section
     */
    public function StoreSection($data, $id = 0)
    {
        // Handle checkbox - it sends 'on' when checked or is not present when unchecked
        $isActive = isset($data['is_active']) ? true : false;

        $sectionData = [
            'skill_assessment_exam_template_id' => $data['skill_assessment_exam_template_id'] ?? null,
            'title' => $data['title'],
            'title_fr' => $data['title_fr'] ?? null,
            'description' => $data['description'] ?? null,
            'description_fr' => $data['description_fr'] ?? null,
            'order' => $data['order'] ?? 0,
            'is_active' => $isActive,
        ];

        if ($id == 0) {
            return $this->model->create($sectionData);
        } else {
            $section = $this->model->find($id);
            if ($section) {
                $section->update($sectionData);
                return $section;
            }
            return null;
        }
    }

    /**
     * Delete a section
     */
    public function DeleteSection($id)
    {
        $section = $this->model->find($id);
        if ($section) {
            $section->delete();
            return true;
        }
        return false;
    }

    /**
     * Update section order
     */
    public function UpdateOrder($orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            $this->model->where('id', $id)->update(['order' => $index + 1]);
        }
        return true;
    }

    /**
     * Change section status
     */
    public function ChangeStatus($id, $status)
    {
        $section = $this->model->find($id);
        if ($section) {
            $section->update(['is_active' => $status]);
            return true;
        }
        return false;
    }

    /**
     * Get next order number
     */
    public function GetNextOrder()
    {
        $maxOrder = $this->model->max('order');
        return $maxOrder ? $maxOrder + 1 : 1;
    }
}
