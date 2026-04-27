<?php

namespace App\Repositories\Admin;

use App\Models\Section;
use App\Repositories\BaseRepository;

class SectionRepository extends BaseRepository
{
    public function model()
    {
        return Section::class;
    }

    /**
     * Get all sections ordered by display order
     */
    public function GetAllSections()
    {
        return $this->model->orderBy('order')->get();
    }

    /**
     * Get all active sections ordered by display order
     */
    public function GetActiveSections()
    {
        return $this->model->where('is_active', true)->orderBy('order')->get();
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
        return $this->model->with('questions')->find($id);
    }

    /**
     * Store or update a section
     */
    public function StoreSection($data, $id = 0)
    {
        $sectionData = [
            'title_en' => $data['title_en'],
            'title_fr' => $data['title_fr'],
            'subtitle_en' => $data['subtitle_en'] ?? null,
            'subtitle_fr' => $data['subtitle_fr'] ?? null,
            'header_en' => $data['header_en'] ?? null,
            'header_fr' => $data['header_fr'] ?? null,
            'order' => $data['order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ];

        if ($id > 0) {
            $update = $this->model->where('id', $id)->update($sectionData);
            if ($update) {
                logAdminActivity('Personalized Experience', 'Update Section', $id, "Updated section: {$sectionData['title_en']}", $sectionData);
            }
            return $update;
        } else {
            // Set order to max + 1 if not provided
            if (!isset($data['order']) || $data['order'] == 0) {
                $maxOrder = $this->model->max('order') ?? 0;
                $sectionData['order'] = $maxOrder + 1;
            }
            $section = $this->model->create($sectionData);
            if ($section) {
                logAdminActivity('Personalized Experience', 'Add Section', $section->id, "Added new section: {$sectionData['title_en']}", $sectionData);
            }
            return $section;
        }
    }

    /**
     * Delete a section
     */
    public function DeleteSection($id)
    {
        $section = $this->model->find($id);
        $title = $section ? $section->title_en : "ID: $id";
        $delete = $this->model->where('id', $id)->delete();
        if ($delete) {
            logAdminActivity('Personalized Experience', 'Delete Section', $id, "Deleted section: $title");
        }
        return $delete;
    }

    /**
     * Update section order
     */
    public function UpdateOrder($orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            $this->model->where('id', $id)->update(['order' => $index + 1]);
        }
        logAdminActivity('Personalized Experience', 'Update Order', null, "Updated order of sections");
        return true;
    }

    /**
     * Change section status
     */
    public function ChangeStatus($id, $status)
    {
        $update = $this->model->where('id', $id)->update(['is_active' => $status]);
        if ($update) {
            $statusText = $status ? 'Active' : 'Inactive';
            logAdminActivity('Personalized Experience', 'Status Change', $id, "Changed section status to: $statusText");
        }
        return $update;
    }

    /**
     * Get next order number
     */
    public function GetNextOrder()
    {
        $maxOrder = $this->model->max('order') ?? 0;
        return $maxOrder + 1;
    }

    /**
     * Get active sections for a specific business
     * Returns business-specific sections if available, otherwise falls back to admin sections
     */
    public function GetActiveSectionsByBusiness($businessId = null)
    {
        if ($businessId) {
            // Check if business has any active sections
            $businessSections = $this->model
                ->where('business_id', $businessId)
                ->where('is_active', true)
                ->orderBy('order')
                ->get();

            // If business has sections, return them
            if ($businessSections->count() > 0) {
                return $businessSections;
            }
        }

        // Fallback to admin sections (where business_id is null)
        return $this->model
            ->whereNull('business_id')
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    /**
     * Count sections for a specific business
     */
    public function CountSectionsByBusiness($businessId)
    {
        return $this->model->where('business_id', $businessId)->count();
    }
}
