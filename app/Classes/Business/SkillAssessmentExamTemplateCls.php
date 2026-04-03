<?php

namespace App\Classes\Business;

use Illuminate\Support\Facades\Session;
use App\Models\SkillAssessmentExamTemplate;
use Exception;

class SkillAssessmentExamTemplateCls
{

    /**
     * Get all exam templates
     */
    public function GetAllExamTemplates($businessId)
    {
        try {
            return SkillAssessmentExamTemplate::where('business_id', $businessId)
                ->orderBy('order')
                ->get();
        } catch (Exception $e) {
            return collect();
        }
    }

    /**
     * Get a single exam template by ID
     */
    public function GetExamTemplate($id, $businessId)
    {
        try {
            return SkillAssessmentExamTemplate::where('id', $id)
                ->where('business_id', $businessId)
                ->first();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get exam template with sections
     */
    public function GetExamTemplateWithSections($id, $businessId)
    {
        try {
            return SkillAssessmentExamTemplate::where('id', $id)
                ->where('business_id', $businessId)
                ->with(['sections' => function ($query) {
                    $query->orderBy('order');
                }])
                ->first();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Store or update an exam template
     */
    public function StoreExamTemplate($request, $businessId, $id = 0)
    {
        try {
            $data = [
                'business_id' => $businessId,
                'title' => $request->title,
                'description' => $request->description,
                'description_fr' => $request->description_fr,
                'tag' => $request->tag,
                'tag_fr' => $request->tag_fr,
                'duration_minutes' => $request->duration_minutes,
                'passing_percentage' => $request->passing_percentage,
                'order' => $request->order ?? $this->GetNextOrder($businessId),
                'is_active' => $request->has('is_active') ? 1 : 0,
            ];

            if ($id > 0) {
                $exam = SkillAssessmentExamTemplate::where('id', $id)
                    ->where('business_id', $businessId)
                    ->first();
                if ($exam) {
                    $exam->update($data);
                }
                $message = __('messages.exam_template_updated') ?? 'Exam template updated successfully!';
            } else {
                SkillAssessmentExamTemplate::create($data);
                $message = __('messages.exam_template_created') ?? 'Exam template created successfully!';
            }

            Session::flash('message', $message);
            Session::flash('icon', 'success');

            return redirect()->route('business.skill-assessment.exams');
        } catch (Exception $e) {
            Session::flash('message', __('messages.something_went_wrong') ?? 'Something went wrong: ' . $e->getMessage());
            Session::flash('icon', 'danger');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Delete an exam template
     */
    public function DeleteExamTemplate($id, $businessId)
    {
        try {
            $exam = SkillAssessmentExamTemplate::where('id', $id)
                ->where('business_id', $businessId)
                ->first();

            if ($exam) {
                $exam->delete();
            }

            Session::flash('message', __('messages.exam_template_deleted') ?? 'Exam template deleted successfully!');
            Session::flash('icon', 'success');

            return redirect()->route('business.skill-assessment.exams');
        } catch (Exception $e) {
            Session::flash('message', __('messages.something_went_wrong') ?? 'Something went wrong: ' . $e->getMessage());
            Session::flash('icon', 'danger');
            return redirect()->route('business.skill-assessment.exams');
        }
    }

    /**
     * Change exam template status
     */
    public function ChangeStatus($id, $status, $businessId)
    {
        try {
            $exam = SkillAssessmentExamTemplate::where('id', $id)
                ->where('business_id', $businessId)
                ->first();

            if ($exam) {
                $exam->is_active = $status;
                $exam->save();
            }
            return 1;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get next order number
     */
    public function GetNextOrder($businessId)
    {
        try {
            $maxOrder = SkillAssessmentExamTemplate::where('business_id', $businessId)->max('order');
            return ($maxOrder ?? 0) + 1;
        } catch (Exception $e) {
            return 1;
        }
    }
}
