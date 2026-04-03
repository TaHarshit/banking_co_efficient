<?php

namespace App\Classes\Business;

use Illuminate\Support\Facades\Session;
use App\Models\SkillAssessmentQuestion;
use App\Models\SkillAssessmentQuestionOption;
use Exception;

class SkillAssessmentQuestionCls
{
    /**
     * Get all questions for a section
     */
    public function GetQuestionsBySection($sectionId, $businessId)
    {
        try {
            return SkillAssessmentQuestion::where('skill_assessment_section_id', $sectionId)
                ->where('business_id', $businessId)
                ->with('options')
                ->orderBy('order')
                ->get();
        } catch (Exception $e) {
            return collect();
        }
    }

    /**
     * Get a single question by ID
     */
    public function GetQuestion($id, $businessId)
    {
        try {
            return SkillAssessmentQuestion::where('id', $id)
                ->where('business_id', $businessId)
                ->with('options')
                ->first();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get next order number
     */
    public function GetNextOrder($sectionId, $businessId)
    {
        try {
            $maxOrder = SkillAssessmentQuestion::where('skill_assessment_section_id', $sectionId)
                ->where('business_id', $businessId)
                ->max('order');
            return ($maxOrder ?? 0) + 1;
        } catch (Exception $e) {
            return 1;
        }
    }

    /**
     * Get question type options
     */
    public function GetTypeOptions()
    {
        return [
            'radio' => 'Single Choice (Radio)',
            'multi_select' => 'Multiple Choice (Checkbox)',
            'open_text' => 'Open Text',
        ];
    }

    /**
     * Store or update a question
     */
    public function StoreQuestion($request, $sectionId, $businessId, $id = 0)
    {
        try {
            $data = [
                'skill_assessment_section_id' => $sectionId,
                'business_id' => $businessId,
                'question_type' => $request->question_type,
                'question_text' => $request->question_text,
                'helper_text' => $request->helper_text,
                'order' => $request->order ?? $this->GetNextOrder($sectionId, $businessId),
                'is_required' => $request->has('is_required') ? 1 : 0,
                'is_active' => $request->has('is_active') ? 1 : 0,
            ];

            if ($id > 0) {
                $question = SkillAssessmentQuestion::where('id', $id)
                    ->where('business_id', $businessId)
                    ->first();
                if ($question) {
                    $question->update($data);
                }
                $message = __('messages.question_updated');
            } else {
                $question = SkillAssessmentQuestion::create($data);
                $message = __('messages.question_created');
            }

            // Handle options for radio and multi_select
            if (in_array($request->question_type, ['radio', 'multi_select'])) {
                $options = $request->input('options', []);
                $correctAnswerIndex = $request->input('correct_answer');
                if (!empty($options)) {
                    $this->storeOptions($question->id, $options, $correctAnswerIndex);
                }
            }

            Session::flash('message', $message);
            Session::flash('icon', 'success');

            return redirect()->route('business.skill-assessment.questions', $sectionId);
        } catch (Exception $e) {
            Session::flash('message', __('messages.something_went_wrong') . ': ' . $e->getMessage());
            Session::flash('icon', 'danger');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Store options for a question
     */
    private function storeOptions($questionId, $options, $correctAnswerIndex = null)
    {
        // Delete existing options
        SkillAssessmentQuestionOption::where('skill_assessment_question_id', $questionId)->delete();

        // Store new options
        foreach ($options as $index => $optionData) {
            if (empty($optionData['option_text'])) continue;

            $isCorrect = false;
            if ($correctAnswerIndex !== null && $correctAnswerIndex == $index) {
                $isCorrect = true;
            } elseif (isset($optionData['is_correct'])) {
                $isCorrect = true;
            }

            SkillAssessmentQuestionOption::create([
                'skill_assessment_question_id' => $questionId,
                'option_text' => $optionData['option_text'],
                'option_text_fr' => $optionData['option_text_fr'] ?? null,
                'weightage' => $optionData['weightage'] ?? 0,
                'order' => $index + 1,
                'is_active' => true,
                'is_correct' => $isCorrect,
            ]);
        }
    }

    /**
     * Delete a question
     */
    public function DeleteQuestion($id, $sectionId, $businessId)
    {
        try {
            $question = SkillAssessmentQuestion::where('id', $id)
                ->where('business_id', $businessId)
                ->first();

            if ($question) {
                $question->delete();
            }

            Session::flash('message', __('messages.question_deleted'));
            Session::flash('icon', 'success');

            return redirect()->route('business.skill-assessment.questions', $sectionId);
        } catch (Exception $e) {
            Session::flash('message', __('messages.something_went_wrong'));
            Session::flash('icon', 'danger');
            return redirect()->route('business.skill-assessment.sections');
        }
    }

    /**
     * Change question status
     */
    public function ChangeStatus($id, $status, $businessId)
    {
        try {
            $question = SkillAssessmentQuestion::where('id', $id)
                ->where('business_id', $businessId)
                ->first();

            if ($question) {
                $question->is_active = $status;
                $question->save();
            }
            return 1;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Export questions to Excel (.xlsx)
     */
    public function ExportQuestions($sectionId, $businessId, $section)
    {
        try {
            $questions = $this->GetQuestionsBySection($sectionId, $businessId);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Questions');

            // Headers
            $headers = [
                'A1' => 'Question Type',
                'B1' => 'Question Text (EN)',
                'C1' => 'Question Text (FR)',
                'D1' => 'Helper Text (EN)',
                'E1' => 'Helper Text (FR)',
                'F1' => 'Order',
                'G1' => 'Required',
                'H1' => 'Status',
                'I1' => 'Options (EN)',
                'J1' => 'Options (FR)',
            ];

            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }

            // Style headers
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ];
            $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

            // Data rows
            $row = 2;
            foreach ($questions as $question) {
                $optionsEnStr = '';
                $optionsFrStr = '';
                if ($question->options && $question->options->count() > 0) {
                    $enParts = [];
                    $frParts = [];
                    foreach ($question->options as $option) {
                        $enPart = $option->getRawOriginal('option_text') . ':' . $option->weightage;
                        if ($option->is_correct) {
                            $enPart .= ':correct';
                        }
                        $enParts[] = $enPart;
                        $frParts[] = $option->option_text_fr ?? '';
                    }
                    $optionsEnStr = implode('|', $enParts);
                    $optionsFrStr = implode('|', $frParts);
                }

                $sheet->setCellValue('A' . $row, $question->question_type);
                $sheet->setCellValue('B' . $row, $question->getRawOriginal('question_text'));
                $sheet->setCellValue('C' . $row, $question->question_text_fr ?? '');
                $sheet->setCellValue('D' . $row, $question->getRawOriginal('helper_text') ?? '');
                $sheet->setCellValue('E' . $row, $question->helper_text_fr ?? '');
                $sheet->setCellValue('F' . $row, $question->order);
                $sheet->setCellValue('G' . $row, $question->is_required ? 'Yes' : 'No');
                $sheet->setCellValue('H' . $row, $question->is_active ? 'Active' : 'Inactive');
                $sheet->setCellValue('I' . $row, $optionsEnStr);
                $sheet->setCellValue('J' . $row, $optionsFrStr);
                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'J') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = "skill_assessment_" . str_replace(' ', '_', $section->getRawOriginal('title')) . "_" . date('Y-m-d_H-i-s') . ".xlsx";

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            $temp = tempnam(sys_get_temp_dir(), 'export');
            $writer->save($temp);

            return response()->download($temp, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (Exception $e) {
            Session::flash('message', 'Export failed: ' . $e->getMessage());
            Session::flash('icon', 'danger');
            return redirect()->back();
        }
    }

    /**
     * Import questions from Excel/CSV (.xlsx, .xls, .csv)
     */
    public function ImportQuestions($request, $sectionId, $businessId)
    {
        try {
            $file = $request->file('file');
            $filePath = $file->getRealPath();

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            $importedCount = 0;
            $errors = [];
            $isFirstRow = true;

            foreach ($rows as $rowIndex => $row) {
                // Skip header row
                if ($isFirstRow) {
                    $isFirstRow = false;
                    continue;
                }

                try {
                    $questionType = strtolower(trim($row['A'] ?? ''));
                    $questionText = trim($row['B'] ?? '');

                    // Skip empty rows
                    if (empty($questionType) || empty($questionText)) {
                        continue;
                    }

                    $data = [
                        'skill_assessment_section_id' => $sectionId,
                        'business_id' => $businessId,
                        'question_type' => $questionType,
                        'question_text' => $questionText,
                        'question_text_fr' => trim($row['C'] ?? ''),
                        'helper_text' => trim($row['D'] ?? ''),
                        'helper_text_fr' => trim($row['E'] ?? ''),
                        'order' => intval($row['F'] ?? ($importedCount + 1)),
                        'is_required' => strtolower(trim($row['G'] ?? 'No')) === 'yes',
                        'is_active' => strtolower(trim($row['H'] ?? 'Active')) === 'active',
                    ];

                    $question = SkillAssessmentQuestion::create($data);

                    // Handle options if provided (column I = EN, column J = FR)
                    if (!empty($row['I']) && in_array($data['question_type'], ['radio', 'multi_select'])) {
                        $optionsEnStr = trim($row['I']);
                        $optionsFrStr = trim($row['J'] ?? '');
                        $enParts = explode('|', $optionsEnStr);
                        $frParts = $optionsFrStr ? explode('|', $optionsFrStr) : [];

                        $options = [];
                        $correctAnswerIndex = null;

                        foreach ($enParts as $index => $optionPart) {
                            $parts = explode(':', $optionPart);
                            $optionText = trim($parts[0] ?? '');
                            $weightage = floatval($parts[1] ?? 0);
                            $isCorrect = isset($parts[2]) && strtolower(trim($parts[2])) === 'correct';

                            if (!empty($optionText)) {
                                $options[$index] = [
                                    'option_text' => $optionText,
                                    'option_text_fr' => trim($frParts[$index] ?? ''),
                                    'weightage' => $weightage,
                                    'is_correct' => $isCorrect,
                                ];

                                if ($isCorrect && $correctAnswerIndex === null) {
                                    $correctAnswerIndex = $index;
                                }
                            }
                        }

                        if (!empty($options)) {
                            $this->storeOptions($question->id, $options, $correctAnswerIndex);
                        }
                    }

                    $importedCount++;
                } catch (Exception $e) {
                    $errors[] = "Row " . $rowIndex . ": " . $e->getMessage();
                }
            }

            if ($importedCount > 0) {
                Session::flash('message', "Successfully imported {$importedCount} questions!");
                Session::flash('icon', 'success');
            }

            if (!empty($errors)) {
                Session::flash('warning', 'Some rows had errors: ' . implode('; ', array_slice($errors, 0, 3)));
            }

            return redirect()->route('business.skill-assessment.questions', $sectionId);
        } catch (Exception $e) {
            Session::flash('message', 'Import failed: ' . $e->getMessage());
            Session::flash('icon', 'danger');
            return redirect()->back();
        }
    }

    /**
     * Download example Excel file (.xlsx)
     */
    public function DownloadExample()
    {
        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Example Questions');

            // Headers
            $headers = ['Question Type', 'Question Text (EN)', 'Question Text (FR)', 'Helper Text (EN)', 'Helper Text (FR)', 'Order', 'Required', 'Status', 'Options (EN)', 'Options (FR)'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', $header);
                $col++;
            }

            // Style headers
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ];
            $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

            // Example rows
            $sheet->setCellValue('A2', 'radio');
            $sheet->setCellValue('B2', 'What is your experience level?');
            $sheet->setCellValue('C2', 'Quel est votre niveau d\'expérience?');
            $sheet->setCellValue('D2', 'Select one option');
            $sheet->setCellValue('E2', 'Sélectionnez une option');
            $sheet->setCellValue('F2', 1);
            $sheet->setCellValue('G2', 'Yes');
            $sheet->setCellValue('H2', 'Active');
            $sheet->setCellValue('I2', 'Beginner:10|Intermediate:30:correct|Expert:50');
            $sheet->setCellValue('J2', 'Débutant|Intermédiaire|Expert');

            $sheet->setCellValue('A3', 'multi_select');
            $sheet->setCellValue('B3', 'Which skills do you have?');
            $sheet->setCellValue('C3', 'Quelles compétences avez-vous?');
            $sheet->setCellValue('D3', 'Select all that apply');
            $sheet->setCellValue('E3', 'Sélectionnez tout ce qui s\'applique');
            $sheet->setCellValue('F3', 2);
            $sheet->setCellValue('G3', 'Yes');
            $sheet->setCellValue('H3', 'Active');
            $sheet->setCellValue('I3', 'PHP:20:correct|JavaScript:20:correct|Python:20:correct');
            $sheet->setCellValue('J3', 'PHP|JavaScript|Python');

            $sheet->setCellValue('A4', 'open_text');
            $sheet->setCellValue('B4', 'Describe your experience');
            $sheet->setCellValue('C4', 'Décrivez votre expérience');
            $sheet->setCellValue('D4', 'Write a brief description');
            $sheet->setCellValue('E4', 'Rédigez une brève description');
            $sheet->setCellValue('F4', 3);
            $sheet->setCellValue('G4', 'No');
            $sheet->setCellValue('H4', 'Active');
            $sheet->setCellValue('I4', '');
            $sheet->setCellValue('J4', '');

            // Auto-size columns
            foreach (range('A', 'J') as $c) {
                $sheet->getColumnDimension($c)->setAutoSize(true);
            }

            $filename = "skill_assessment_questions_example.xlsx";
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            $temp = tempnam(sys_get_temp_dir(), 'example');
            $writer->save($temp);

            return response()->download($temp, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (Exception $e) {
            Session::flash('message', 'Download failed: ' . $e->getMessage());
            Session::flash('icon', 'danger');
            return redirect()->back();
        }
    }

    /**
     * Delete all questions in a section
     */
    public function DeleteAllQuestions($sectionId, $businessId)
    {
        try {
            $questions = $this->GetQuestionsBySection($sectionId, $businessId);
            $deletedCount = 0;

            foreach ($questions as $question) {
                $question->delete();
                $deletedCount++;
            }

            Session::flash('message', "Successfully deleted {$deletedCount} questions!");
            Session::flash('icon', 'success');
            return redirect()->route('business.skill-assessment.questions', $sectionId);
        } catch (Exception $e) {
            Session::flash('message', 'Delete failed: ' . $e->getMessage());
            Session::flash('icon', 'danger');
            return redirect()->back();
        }
    }
}
