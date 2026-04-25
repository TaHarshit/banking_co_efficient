<?php

namespace App\Classes\Admin;

use Illuminate\Support\Facades\Session;
use App\Repositories\Admin\SkillAssessmentQuestionRepository;
use App\Repositories\Admin\SkillAssessmentQuestionOptionRepository;
use App\Repositories\Admin\SkillAssessmentSectionRepository;
use Exception;

class SkillAssessmentQuestionCls
{
    protected $QuestionRep;
    protected $OptionRep;
    protected $SectionRep;

    public function __construct(
        SkillAssessmentQuestionRepository $QuestionRep,
        SkillAssessmentQuestionOptionRepository $OptionRep,
        SkillAssessmentSectionRepository $SectionRep
    ) {
        $this->QuestionRep = $QuestionRep;
        $this->OptionRep = $OptionRep;
        $this->SectionRep = $SectionRep;
    }

    /**
     * Get all questions across all sections
     */
    public function GetAllQuestions($source = null)
    {
        try {
            return $this->QuestionRep->GetAllQuestions($source);
        } catch (Exception $e) {
            return collect();
        }
    }

    /**
     * Get all sections for dropdown
     */
    public function GetAllSections()
    {
        try {
            return $this->SectionRep->GetAllSections();
        } catch (Exception $e) {
            return collect();
        }
    }

    /**
     * Get sections filtered by exam template
     */
    public function GetSectionsByExamTemplate($examTemplateId)
    {
        try {
            return $this->SectionRep->GetSectionsByExamTemplate($examTemplateId);
        } catch (Exception $e) {
            return collect();
        }
    }

    /**
     * Get questions by section
     */
    public function GetQuestionsBySection($sectionId, $source = null)
    {
        try {
            return $this->QuestionRep->GetQuestionsBySection($sectionId, $source);
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a single question by ID
     */
    public function GetQuestion($id)
    {
        try {
            return $this->QuestionRep->GetQuestion($id);
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get question with options
     */
    public function GetQuestionWithOptions($id)
    {
        try {
            return $this->QuestionRep->GetQuestionWithOptions($id);
        } catch (Exception $e) {
            return response()->view('error.500', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store or update a question
     */
    public function StoreQuestion($request, $id = 0)
    {
        try {
            $data = $request->all();
            $data['is_required'] = $request->has('is_required') ? 1 : 0;
            $data['is_active'] = $request->has('is_active') ? 1 : 0;

            if ($id == 0) {
                $question = $this->QuestionRep->StoreQuestion($data);
                Session::flash('success', 'Skill assessment question created successfully!');
            } else {
                $question = $this->QuestionRep->StoreQuestion($data, $id);
                if ($question) {
                    Session::flash('success', 'Skill assessment question updated successfully!');
                } else {
                    Session::flash('error', 'Skill assessment question not found!');
                    return redirect()->route('manageskillassessmentquestions');
                }
            }

            // Handle options if question type requires them
            if (in_array($data['question_type'], ['radio', 'multi_select'])) {
                $options = $request->input('options', []);
                if (!empty($options)) {
                    // Get the correct answer index for radio type
                    $correctAnswerIndex = null;
                    if ($data['question_type'] === 'radio') {
                        $correctAnswerIndex = $request->input('correct_answer');
                    }
                    $this->OptionRep->StoreOptionsForQuestion($question->id, $options, $correctAnswerIndex);
                }
            }

            // Redirect back with exam context
            $sectionId = $data['skill_assessment_section_id'] ?? null;
            $redirectParams = [];
            if ($sectionId) {
                $redirectParams['section_id'] = $sectionId;
                $section = $this->SectionRep->GetSection($sectionId);
                if ($section && $section->skill_assessment_exam_template_id) {
                    $redirectParams['exam_template_id'] = $section->skill_assessment_exam_template_id;
                }
            }
            return redirect()->route('manageskillassessmentquestions', $redirectParams);
        } catch (Exception $e) {
            Session::flash('error', 'Something went wrong: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Delete a question
     */
    public function DeleteQuestion($id)
    {
        try {
            $question = $this->QuestionRep->GetQuestion($id);
            $sectionId = $question ? $question->skill_assessment_section_id : null;

            // Get exam context before deleting
            $redirectParams = [];
            if ($sectionId) {
                $redirectParams['section_id'] = $sectionId;
                $section = $this->SectionRep->GetSection($sectionId);
                if ($section && $section->skill_assessment_exam_template_id) {
                    $redirectParams['exam_template_id'] = $section->skill_assessment_exam_template_id;
                }
            }

            $result = $this->QuestionRep->DeleteQuestion($id);
            if ($result) {
                Session::flash('success', 'Skill assessment question deleted successfully!');
            } else {
                Session::flash('error', 'Skill assessment question not found!');
            }

            return redirect()->route('manageskillassessmentquestions', $redirectParams);
        } catch (Exception $e) {
            Session::flash('error', 'Cannot delete question: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Change question status
     */
    public function ChangeStatus($id, $status)
    {
        try {
            $result = $this->QuestionRep->ChangeStatus($id, $status);
            return $result ? 1 : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get next order number for section
     */
    public function GetNextOrder($sectionId)
    {
        try {
            return $this->QuestionRep->GetNextOrder($sectionId);
        } catch (Exception $e) {
            return 1;
        }
    }

    /**
     * Get question types
     */
    public function GetQuestionTypes()
    {
        try {
            return $this->QuestionRep->GetQuestionTypes();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get section for question
     */
    public function GetSection($sectionId)
    {
        try {
            return $this->QuestionRep->GetSection($sectionId);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Export questions to Excel (.xlsx)
     */
    public function ExportQuestions($sectionId)
    {
        try {
            $questions = $this->QuestionRep->GetQuestionsBySection($sectionId);
            $section = $this->SectionRep->GetSection($sectionId);

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
                // Options EN
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

            $filename = "skill_assessment_questions_" . str_replace(' ', '_', $section->getRawOriginal('title')) . "_" . date('Y-m-d_H-i-s') . ".xlsx";

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            $temp = tempnam(sys_get_temp_dir(), 'export');
            $writer->save($temp);

            return response()->download($temp, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (Exception $e) {
            Session::flash('error', 'Export failed: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Import questions from Excel/CSV (.xlsx, .xls, .csv)
     */
    public function ImportQuestions($request)
    {
        try {
            $sectionId = $request->input('skill_assessment_section_id');
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
                        'question_type' => $questionType,
                        'question_text' => $questionText,
                        'question_text_fr' => trim($row['C'] ?? ''),
                        'helper_text' => trim($row['D'] ?? ''),
                        'helper_text_fr' => trim($row['E'] ?? ''),
                        'order' => intval($row['F'] ?? ($importedCount + 1)),
                        'is_required' => strtolower(trim($row['G'] ?? 'No')) === 'yes',
                        'is_active' => strtolower(trim($row['H'] ?? 'Active')) === 'active',
                    ];

                    $question = $this->QuestionRep->StoreQuestion($data);

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

                                if ($isCorrect && $correctAnswerIndex === null && $data['question_type'] === 'radio') {
                                    $correctAnswerIndex = $index;
                                }
                            }
                        }

                        if (!empty($options)) {
                            $this->OptionRep->StoreOptionsForQuestion($question->id, $options, $correctAnswerIndex);
                        }
                    }

                    $importedCount++;
                } catch (Exception $e) {
                    $errors[] = "Row " . $rowIndex . ": " . $e->getMessage();
                }
            }

            if ($importedCount > 0) {
                Session::flash('success', "Successfully imported {$importedCount} questions!");
            }

            if (!empty($errors)) {
                Session::flash('warning', 'Some rows had errors: ' . implode('; ', array_slice($errors, 0, 3)));
            }

            return redirect()->route('manageskillassessmentquestions', ['section_id' => $sectionId]);
        } catch (Exception $e) {
            Session::flash('error', 'Import failed: ' . $e->getMessage());
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
            $sheet->setCellValue('D2', 'Select your current experience');
            $sheet->setCellValue('E2', 'Sélectionnez votre expérience actuelle');
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
            $sheet->setCellValue('I3', 'PHP:20:correct|JavaScript:20:correct|Python:20:correct|Others:10');
            $sheet->setCellValue('J3', 'PHP|JavaScript|Python|Autres');

            $sheet->setCellValue('A4', 'open_text');
            $sheet->setCellValue('B4', 'Tell us about yourself');
            $sheet->setCellValue('C4', 'Parlez-nous de vous');
            $sheet->setCellValue('D4', 'Provide a brief description');
            $sheet->setCellValue('E4', 'Fournissez une brève description');
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
            Session::flash('error', 'Download failed: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Delete all questions in a section
     */
    public function DeleteAllQuestions($sectionId)
    {
        try {
            $questions = $this->QuestionRep->GetQuestionsBySection($sectionId);
            $deletedCount = 0;

            foreach ($questions as $question) {
                $this->QuestionRep->DeleteQuestion($question->id);
                $deletedCount++;
            }

            Session::flash('success', "Successfully deleted {$deletedCount} questions!");
            return redirect()->route('manageskillassessmentquestions', ['section_id' => $sectionId]);
        } catch (Exception $e) {
            Session::flash('error', 'Delete failed: ' . $e->getMessage());
            return redirect()->back();
        }
    }
}
