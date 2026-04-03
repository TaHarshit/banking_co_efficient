<?php

namespace App\Classes\Admin;

use Illuminate\Support\Facades\Session;
use App\Repositories\Admin\SectionRepository;
use App\Repositories\Admin\QuestionRepository;
use Exception;

class SectionCls
{
    protected $SectionRep;
    protected $QuestionRep;

    public function __construct(SectionRepository $SectionRep, QuestionRepository $QuestionRep)
    {
        $this->SectionRep = $SectionRep;
        $this->QuestionRep = $QuestionRep;
    }

    /**
     * Get all sections
     */
    public function GetAllSections()
    {
        try {
            return $this->SectionRep->GetAllSections();
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a single section by ID
     */
    public function GetSection($id)
    {
        try {
            return $this->SectionRep->GetSection($id);
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get section with questions
     */
    public function GetSectionWithQuestions($id)
    {
        try {
            return $this->SectionRep->GetSectionWithQuestions($id);
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get next order number
     */
    public function GetNextOrder()
    {
        try {
            return $this->SectionRep->GetNextOrder();
        } catch (Exception $e) {
            return 1;
        }
    }

    /**
     * Store or update a section
     */
    public function StoreSection($request, $id = 0)
    {
        try {
            $data = [
                'title_en' => $request->title_en,
                'title_fr' => $request->title_fr,
                'subtitle_en' => $request->subtitle_en,
                'subtitle_fr' => $request->subtitle_fr,
                'header_en' => $request->header_en,
                'header_fr' => $request->header_fr,
                'order' => $request->order ?? 0,
                'is_active' => $request->has('is_active') ? 1 : 0,
            ];

            $this->SectionRep->StoreSection($data, $id);

            $message = ($id > 0)
                ? __('messages.section_updated')
                : __('messages.section_created');

            Session::flash('message', $message);
            Session::flash('icon', 'success');

            return redirect()->route('managesections');
        } catch (Exception $e) {
            Session::flash('message', __('messages.something_went_wrong'));
            Session::flash('icon', 'danger');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Delete a section
     */
    public function DeleteSection($id)
    {
        try {
            $this->SectionRep->DeleteSection($id);

            Session::flash('message', __('messages.section_deleted'));
            Session::flash('icon', 'success');

            return redirect()->route('managesections');
        } catch (Exception $e) {
            Session::flash('message', __('messages.something_went_wrong'));
            Session::flash('icon', 'danger');
            return redirect()->route('managesections');
        }
    }

    /**
     * Update section order
     */
    public function UpdateOrder($orderedIds)
    {
        try {
            return $this->SectionRep->UpdateOrder($orderedIds);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Change section status
     */
    public function ChangeStatus($id, $status)
    {
        try {
            $this->SectionRep->ChangeStatus($id, $status);
            return 1;
        } catch (Exception $e) {
            return 0;
        }
    }
}
