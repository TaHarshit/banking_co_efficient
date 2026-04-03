<?php

namespace App\Classes\Business;

use Illuminate\Support\Facades\Session;
use App\Models\SkillAssessmentSection;
use Exception;

class SkillAssessmentSectionCls
{
    /**
     * Get all sections for a business
     */
    public function GetAllSections($businessId)
    {
        try {
            return SkillAssessmentSection::where('business_id', $businessId)
                ->orderBy('order')
                ->get();
        } catch (Exception $e) {
            return collect();
        }
    }

    /**
     * Get all sections for a business by exam template
     */
    public function GetSectionsByExamTemplate($examTemplateId, $businessId)
    {
        try {
            return SkillAssessmentSection::where('business_id', $businessId)
                ->where('skill_assessment_exam_template_id', $examTemplateId)
                ->orderBy('order')
                ->get();
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
            return SkillAssessmentSection::where('id', $id)
                ->where('business_id', $businessId)
                ->first();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get next order number
     */
    public function GetNextOrder($businessId, $examTemplateId = null)
    {
        try {
            $query = SkillAssessmentSection::where('business_id', $businessId);
            if ($examTemplateId) {
                $query->where('skill_assessment_exam_template_id', $examTemplateId);
            }
            $maxOrder = $query->max('order');
            return ($maxOrder ?? 0) + 1;
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
                'skill_assessment_exam_template_id' => $request->skill_assessment_exam_template_id ?? null,
                'title' => $request->title,
                'description' => $request->description,
                'order' => $request->order ?? $this->GetNextOrder($businessId),
                'is_active' => $request->has('is_active') ? 1 : 0,
            ];

            if ($id > 0) {
                $section = SkillAssessmentSection::where('id', $id)
                    ->where('business_id', $businessId)
                    ->first();
                if ($section) {
                    $section->update($data);
                }
                $message = __('messages.section_updated');
            } else {
                SkillAssessmentSection::create($data);
                $message = __('messages.section_created');
            }

            Session::flash('message', $message);
            Session::flash('icon', 'success');

            return redirect()->route('business.skill-assessment.sections');
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
            $section = SkillAssessmentSection::where('id', $id)
                ->where('business_id', $businessId)
                ->first();

            if ($section) {
                $section->delete();
            }

            Session::flash('message', __('messages.section_deleted'));
            Session::flash('icon', 'success');

            return redirect()->route('business.skill-assessment.sections');
        } catch (Exception $e) {
            Session::flash('message', __('messages.something_went_wrong'));
            Session::flash('icon', 'danger');
            return redirect()->route('business.skill-assessment.sections');
        }
    }

    /**
     * Change section status
     */
    public function ChangeStatus($id, $status, $businessId)
    {
        try {
            $section = SkillAssessmentSection::where('id', $id)
                ->where('business_id', $businessId)
                ->first();

            if ($section) {
                $section->is_active = $status;
                $section->save();
            }
            return 1;
        } catch (Exception $e) {
            return 0;
        }
    }
}
