<?php

namespace App\Repositories\Business;

use App\Models\Section;

class SectionRepository
{
    /**
     * Get all sections for a specific business
     */
    public function GetAllSections($businessId)
    {
        return Section::where('business_id', $businessId)
            ->orderBy('order')
            ->get();
    }

    /**
     * Get a single section by ID for a specific business
     */
    public function GetSection($id, $businessId)
    {
        return Section::where('id', $id)
            ->where('business_id', $businessId)
            ->first();
    }

    /**
     * Get section with questions
     */
    public function GetSectionWithQuestions($id, $businessId)
    {
        return Section::where('id', $id)
            ->where('business_id', $businessId)
            ->with(['questions' => function ($query) {
                $query->orderBy('order');
            }])
            ->first();
    }

    /**
     * Get next order number for a business
     */
    public function GetNextOrder($businessId)
    {
        $maxOrder = Section::where('business_id', $businessId)->max('order');
        return ($maxOrder ?? 0) + 1;
    }

    /**
     * Store or update a section
     */
    public function StoreSection($data, $id = 0)
    {
        if ($id > 0) {
            $section = Section::find($id);
            if ($section) {
                $section->update($data);
                return $section;
            }
        }
        return Section::create($data);
    }

    /**
     * Delete a section
     */
    public function DeleteSection($id, $businessId)
    {
        return Section::where('id', $id)
            ->where('business_id', $businessId)
            ->delete();
    }

    /**
     * Update section order
     */
    public function UpdateOrder($orderedIds, $businessId)
    {
        foreach ($orderedIds as $order => $id) {
            Section::where('id', $id)
                ->where('business_id', $businessId)
                ->update(['order' => $order + 1]);
        }
        return true;
    }

    /**
     * Change section status
     */
    public function ChangeStatus($id, $status, $businessId)
    {
        return Section::where('id', $id)
            ->where('business_id', $businessId)
            ->update(['is_active' => $status]);
    }

    /**
     * Count sections for a business
     */
    public function CountSections($businessId)
    {
        return Section::where('business_id', $businessId)->count();
    }
}
