<?php

namespace App\Repositories\Admin;

use App\Models\SkillAssessmentExamTemplate;
use App\Repositories\BaseRepository;

class SkillAssessmentExamTemplateRepository extends BaseRepository
{
    public function model()
    {
        return SkillAssessmentExamTemplate::class;
    }

    /**
     * Get all exam templates ordered by display order
     */
    public function GetAllExamTemplates($source = null)
    {
        $query = $this->model->withCount('sections')->orderBy('order');

        if ($source === 'business') {
            $query->whereNotNull('business_id');
        } elseif ($source === 'global') {
            $query->whereNull('business_id');
        }

        return $query->get();
    }

    /**
     * Get all active exam templates
     */
    public function GetActiveExamTemplates()
    {
        return $this->model->where('is_active', true)
            ->withCount('sections')
            ->orderBy('order')
            ->get();
    }

    /**
     * Get a single exam template by ID
     */
    public function GetExamTemplate($id)
    {
        return $this->model->find($id);
    }

    /**
     * Get exam template with sections and questions
     */
    public function GetExamTemplateWithSections($id)
    {
        return $this->model->with(['sections' => function ($query) {
            $query->orderBy('order')->withCount('questions');
        }])->find($id);
    }

    /**
     * Store or update an exam template
     */
    public function StoreExamTemplate($data, $id = 0)
    {
        $isActive = isset($data['is_active']) ? true : false;

        $templateData = [
            'title' => $data['title'],
            'title_fr' => $data['title_fr'] ?? null,
            'description' => $data['description'] ?? null,
            'description_fr' => $data['description_fr'] ?? null,
            'exam_level' => $data['exam_level'] ?? null,
            'exam_level_fr' => $data['exam_level_fr'] ?? null,
            'tags' => ! empty($data['tags']) ? (is_array($data['tags']) ? $data['tags'] : json_decode($data['tags'], true)) : null,
            'tags_fr' => ! empty($data['tags_fr']) ? (is_array($data['tags_fr']) ? $data['tags_fr'] : json_decode($data['tags_fr'], true)) : null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'passing_percentage' => $data['passing_percentage'] ?? null,
            'order' => $data['order'] ?? 0,
            'is_active' => $isActive,
        ];

        if ($id == 0) {
            $template = $this->model->create($templateData);
            if ($template) {
                logAdminActivity('Exam', 'Add', $template->id, "Added new exam: {$templateData['title']}", $templateData);
            }
            return $template;
        } else {
            $template = $this->model->find($id);
            if ($template) {
                $template->update($templateData);
                logAdminActivity('Exam', 'Update', $id, "Updated exam: {$templateData['title']}", $templateData);
                return $template;
            }

            return null;
        }
    }

    /**
     * Delete an exam template
     */
    public function DeleteExamTemplate($id)
    {
        $template = $this->model->find($id);
        if ($template) {
            $title = $template->title;
            $template->delete();
            logAdminActivity('Exam', 'Delete', $id, "Deleted exam: $title");

            return true;
        }

        return false;
    }

    /**
     * Change exam template status
     */
    public function ChangeStatus($id, $status)
    {
        $template = $this->model->find($id);
        if ($template) {
            $template->update(['is_active' => $status]);
            $statusText = $status ? 'Active' : 'Inactive';
            logAdminActivity('Exam', 'Status Change', $id, "Changed exam status to: $statusText");

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
