<?php

namespace App\Classes\Admin;

use Illuminate\Support\Facades\Session;
use App\Repositories\Admin\SkillAssessmentSectionRepository;
use App\Repositories\Admin\SkillAssessmentQuestionRepository;
use Exception;

class SkillAssessmentSectionCls
{
    protected $SectionRep;
    protected $QuestionRep;

    public function __construct(SkillAssessmentSectionRepository $SectionRep, SkillAssessmentQuestionRepository $QuestionRep)
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
     * Get sections by exam template
     */
    public function GetSectionsByExamTemplate($examTemplateId)
    {
        try {
            return $this->SectionRep->GetSectionsByExamTemplate($examTemplateId);
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
     * Store or update a section
     */
    public function StoreSection($request, $id = 0)
    {
        try {
            $data = $request->all();

            if ($id == 0) {
                $section = $this->SectionRep->StoreSection($data);
                Session::flash('success', 'Skill assessment section created successfully!');
            } else {
                $section = $this->SectionRep->StoreSection($data, $id);
                if ($section) {
                    Session::flash('success', 'Skill assessment section updated successfully!');
                } else {
                    Session::flash('error', 'Skill assessment section not found!');
                    return redirect()->route('manageskillassessmentsections');
                }
            }

            // Redirect back to sections filtered by exam template if applicable
            $examTemplateId = $data['skill_assessment_exam_template_id'] ?? null;
            if ($examTemplateId) {
                return redirect()->route('manageskillassessmentsections', ['exam_template_id' => $examTemplateId]);
            }
            return redirect()->route('manageskillassessmentsections');
        } catch (Exception $e) {
            Session::flash('error', 'Something went wrong: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Delete a section
     */
    public function DeleteSection($id)
    {
        try {
            $result = $this->SectionRep->DeleteSection($id);
            if ($result) {
                Session::flash('success', 'Skill assessment section deleted successfully!');
            } else {
                Session::flash('error', 'Skill assessment section not found!');
            }
            return redirect()->route('manageskillassessmentsections');
        } catch (Exception $e) {
            Session::flash('error', 'Cannot delete section: ' . $e->getMessage());
            return redirect()->route('manageskillassessmentsections');
        }
    }

    /**
     * Update section order
     */
    public function UpdateOrder($orderedIds)
    {
        try {
            $result = $this->SectionRep->UpdateOrder($orderedIds);
            return $result;
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
            $result = $this->SectionRep->ChangeStatus($id, $status);
            return $result ? 1 : 0;
        } catch (Exception $e) {
            return 0;
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
     * Export sections to Excel
     */
    public function ExportSections()
    {
        try {
            $sections = $this->SectionRep->GetAllSections();

            $filename = "skill_assessment_sections_" . date('Y-m-d_H-i-s') . ".xlsx";

            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            // Create CSV content
            $csvContent = "ID,Title,Description,Order,Status,Created At\n";

            foreach ($sections as $section) {
                $csvContent .= sprintf(
                    "%d,\"%s\",\"%s\",%d,%s,%s\n",
                    $section->id,
                    str_replace('"', '""', $section->title),
                    str_replace('"', '""', $section->description ?? ''),
                    $section->order,
                    $section->is_active ? 'Active' : 'Inactive',
                    $section->created_at->format('Y-m-d H:i:s')
                );
            }

            return response()->make($csvContent, 200, $headers);
        } catch (Exception $e) {
            Session::flash('error', 'Export failed: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Import sections from Excel/CSV
     */
    public function ImportSections($request)
    {
        try {
            $file = $request->file('file');
            $filePath = $file->getRealPath();

            $fileHandle = fopen($filePath, 'r');
            $header = fgetcsv($fileHandle);

            $importedCount = 0;
            $errors = [];

            while (($row = fgetcsv($fileHandle)) !== FALSE) {
                try {
                    $data = [
                        'title' => $row[1] ?? '',
                        'description' => $row[2] ?? '',
                        'order' => $row[3] ?? 1,
                        'is_active' => ($row[4] ?? 'Active') === 'Active',
                    ];

                    $this->SectionRep->StoreSection($data);
                    $importedCount++;
                } catch (Exception $e) {
                    $errors[] = "Row " . ($importedCount + 2) . ": " . $e->getMessage();
                }
            }

            fclose($fileHandle);

            if ($importedCount > 0) {
                Session::flash('success', "Successfully imported {$importedCount} sections!");
            }

            if (!empty($errors)) {
                Session::flash('warning', 'Some rows had errors: ' . implode('; ', array_slice($errors, 0, 3)));
            }

            return redirect()->route('manageskillassessmentsections');
        } catch (Exception $e) {
            Session::flash('error', 'Import failed: ' . $e->getMessage());
            return redirect()->back();
        }
    }
}
