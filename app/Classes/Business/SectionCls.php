<?php

namespace App\Classes\Business;

use Illuminate\Support\Facades\Session;
use App\Repositories\Business\SectionRepository;
use Exception;

class SectionCls
{
    protected $SectionRep;

    public function __construct(SectionRepository $SectionRep)
    {
        $this->SectionRep = $SectionRep;
    }

    /**
     * Get all sections for a business
     */
    public function GetAllSections($businessId)
    {
        try {
            return $this->SectionRep->GetAllSections($businessId);
        } catch (Exception $e) {
            return collect();
        }
    }

    /**
     * Get a single section by ID
     */
    public function GetSection($id, $businessId)
    {
        try {
            return $this->SectionRep->GetSection($id, $businessId);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get section with questions
     */
    public function GetSectionWithQuestions($id, $businessId)
    {
        try {
            return $this->SectionRep->GetSectionWithQuestions($id, $businessId);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get next order number
     */
    public function GetNextOrder($businessId)
    {
        try {
            return $this->SectionRep->GetNextOrder($businessId);
        } catch (Exception $e) {
            return 1;
        }
    }

    /**
     * Store or update a section
     */
    public function StoreSection($request, $businessId, $id = 0)
    {
        try {
            $data = [
                'business_id' => $businessId,
                'title_en' => $request->title_en,
                'title_fr' => $request->title_fr,
                'subtitle_en' => $request->subtitle_en,
                'subtitle_fr' => $request->subtitle_fr,
                'header_en' => $request->header_en,
                'header_fr' => $request->header_fr,
                'order' => $request->order ?? $this->GetNextOrder($businessId),
                'is_active' => $request->has('is_active') ? 1 : 0,
            ];

            $this->SectionRep->StoreSection($data, $id);

            $message = ($id > 0)
                ? __('messages.section_updated')
                : __('messages.section_created');

            Session::flash('message', $message);
            Session::flash('icon', 'success');

            return redirect()->route('business.sections');
        } catch (Exception $e) {
            Session::flash('message', __('messages.something_went_wrong'));
            Session::flash('icon', 'danger');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Delete a section
     */
    public function DeleteSection($id, $businessId)
    {
        try {
            $this->SectionRep->DeleteSection($id, $businessId);

            Session::flash('message', __('messages.section_deleted'));
            Session::flash('icon', 'success');

            return redirect()->route('business.sections');
        } catch (Exception $e) {
            Session::flash('message', __('messages.something_went_wrong'));
            Session::flash('icon', 'danger');
            return redirect()->route('business.sections');
        }
    }

    /**
     * Update section order
     */
    public function UpdateOrder($orderedIds, $businessId)
    {
        try {
            return $this->SectionRep->UpdateOrder($orderedIds, $businessId);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Change section status
     */
    public function ChangeStatus($id, $status, $businessId)
    {
        try {
            $this->SectionRep->ChangeStatus($id, $status, $businessId);
            return 1;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Count sections for a business
     */
    public function CountSections($businessId)
    {
        try {
            return $this->SectionRep->CountSections($businessId);
        } catch (Exception $e) {
            return 0;
        }
    }
}
