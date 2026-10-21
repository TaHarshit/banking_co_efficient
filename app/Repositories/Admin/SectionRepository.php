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
     * Get all sections ordered by display order (admin sections only)
     */
    public function GetAllSections()
    {
        return $this->model->whereNull('business_id')->orderBy('order')->get();
    }

    /**
     * Get all active sections ordered by display order (admin sections only)
     */
    public function GetActiveSections()
    {
        return $this->model->whereNull('business_id')->where('is_active', true)->orderBy('order')->get();
    }

    /**
     * Get a single section by ID (admin section only)
     */
    public function GetSection($id)
    {
        return $this->model->whereNull('business_id')->find($id);
    }

    /**
     * Get section with questions (admin section only)
     */
    public function GetSectionWithQuestions($id)
    {
        return $this->model->whereNull('business_id')->with(['questions' => function ($query) {
            $query->whereNull('business_id')->orderBy('order');
        }])->find($id);
    }

    /**
     * Store or update a section
     */
    public function StoreSection($data, $id = 0)
    {
        $sectionData = [
            'business_id' => null,
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
            $update = $this->model->where('id', $id)->whereNull('business_id')->update($sectionData);
            if ($update) {
                logAdminActivity('Personalized Experience', 'Update Section', $id, "Updated section: {$sectionData['title_en']}", $sectionData);
            }
            return $update;
        } else {
            // Set order to max + 1 if not provided
            if (!isset($data['order']) || $data['order'] == 0) {
                $maxOrder = $this->model->whereNull('business_id')->max('order') ?? 0;
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
        $section = $this->model->whereNull('business_id')->find($id);
        $title = $section ? $section->title_en : "ID: $id";
        $delete = $this->model->where('id', $id)->whereNull('business_id')->delete();
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
            $this->model->where('id', $id)->whereNull('business_id')->update(['order' => $index + 1]);
        }
        logAdminActivity('Personalized Experience', 'Update Order', null, "Updated order of sections");
        return true;
    }

    /**
     * Change section status
     */
    public function ChangeStatus($id, $status)
    {
        $update = $this->model->where('id', $id)->whereNull('business_id')->update(['is_active' => $status]);
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
        $maxOrder = $this->model->whereNull('business_id')->max('order') ?? 0;
        return $maxOrder + 1;
    }

    /**
     * Get active sections for a specific business
     * Returns business-specific sections if available with active questions, otherwise falls back to admin sections
     */
    public function GetActiveSectionsByBusiness($businessId = null)
    {
        if ($businessId) {
            // Check if business has any active sections that contain active questions
            $businessSections = $this->model
                ->where('business_id', $businessId)
                ->where('is_active', true)
                ->whereHas('questions', function ($q) {
                    $q->where('is_active', true);
                })
                ->orderBy('order')
                ->get();

            // If business has sections with questions, return them
            if ($businessSections->count() > 0) {
                return $businessSections;
            }
        }

        // Fallback to admin sections (where business_id is null) that have active questions
        return $this->model
            ->whereNull('business_id')
            ->where('is_active', true)
            ->whereHas('questions', function ($q) {
                $q->where('is_active', true);
            })
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
