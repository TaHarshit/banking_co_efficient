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
            return $this->model->where('id', $id)->update($sectionData);
        } else {
            // Set order to max + 1 if not provided
            if (!isset($data['order']) || $data['order'] == 0) {
                $maxOrder = $this->model->max('order') ?? 0;
                $sectionData['order'] = $maxOrder + 1;
            }
            return $this->model->create($sectionData);
        }
    }

    /**
     * Delete a section
     */
    public function DeleteSection($id)
    {
        return $this->model->where('id', $id)->delete();
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
        return $this->model->where('id', $id)->update(['is_active' => $status]);
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
