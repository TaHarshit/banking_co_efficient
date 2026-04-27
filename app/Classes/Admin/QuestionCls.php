<?php

namespace App\Classes\Admin;

use Illuminate\Support\Facades\Session;
use App\Repositories\Admin\QuestionRepository;
use App\Repositories\Admin\QuestionOptionRepository;
use App\Repositories\Admin\SectionRepository;
use App\Models\Question;
use Exception;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\IOFactory;


class QuestionCls
{
    protected $QuestionRep;
    protected $OptionRep;
    protected $SectionRep;

    public function __construct(
        QuestionRepository $QuestionRep,
        QuestionOptionRepository $OptionRep,
        SectionRepository $SectionRep
    ) {
        $this->QuestionRep = $QuestionRep;
        $this->OptionRep = $OptionRep;
        $this->SectionRep = $SectionRep;
    }

    /**
     * Get all questions for a section
     */
    public function GetQuestionsBySection($sectionId)
    {
        try {
            return $this->QuestionRep->GetQuestionsBySection($sectionId);
        } catch (Exception $e) {
            return collect();
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
            return null;
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
            return null;
        }
    }

    /**
     * Get section by ID
     */
    public function GetSection($id)
    {
        try {
            return $this->SectionRep->GetSection($id);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get next order number for a section
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
     * Store or update a question with options
     */
    public function StoreQuestion($request, $id = 0)
    {
        try {
            // Prepare question data
            $questionData = [
                'section_id' => $request->section_id,
                'question_type' => $request->question_type,
                'question_text_en' => $request->question_text_en,
                'question_text_fr' => $request->question_text_fr,
                'helper_text_en' => $request->helper_text_en,
                'helper_text_fr' => $request->helper_text_fr,
                'order' => $request->order ?? 0,
                'is_required' => $request->has('is_required') ? 1 : 0,
                'is_active' => $request->has('is_active') ? 1 : 0,
                'settings' => $this->buildSettings($request),
            ];

            // Store/update question
            $question = $this->QuestionRep->StoreQuestion($questionData, $id);
            $questionId = $id > 0 ? $id : $question->id;

            // Handle options for select-type questions
            if (in_array($request->question_type, [Question::TYPE_SINGLE_SELECT, Question::TYPE_MULTI_SELECT, Question::TYPE_RATING_SCALE])) {
                $this->storeOptions($questionId, $request);
            } else {
                // Remove options if question type changed to non-select
                $this->OptionRep->DeleteOptionsByQuestion($questionId);
            }

            $message = ($id > 0)
                ? __('messages.question_updated')
                : __('messages.question_created');

            Session::flash('message', $message);
            Session::flash('icon', 'success');

            return redirect()->route('managequestions', ['section_id' => $request->section_id]);
        } catch (Exception $e) {
            Session::flash('message', __('messages.something_went_wrong') . ': ' . $e->getMessage());
            Session::flash('icon', 'danger');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Build settings JSON based on question type
     */
    private function buildSettings($request)
    {
        $settings = [];

        switch ($request->question_type) {
            case Question::TYPE_MULTI_SELECT:
                if ($request->max_selections) {
                    $settings['max_selections'] = (int) $request->max_selections;
                }
                break;

            case Question::TYPE_RATING_SCALE:
                $settings['min_value'] = (int) ($request->min_value ?? 1);
                $settings['max_value'] = (int) ($request->max_value ?? 5);
                break;

            case Question::TYPE_TEXT_INPUT:
                if ($request->min_characters) {
                    $settings['min_characters'] = (int) $request->min_characters;
                }
                if ($request->max_characters) {
                    $settings['max_characters'] = (int) $request->max_characters;
                }
                if ($request->placeholder_en) {
                    $settings['placeholder_en'] = $request->placeholder_en;
                }
                if ($request->placeholder_fr) {
                    $settings['placeholder_fr'] = $request->placeholder_fr;
                }
                break;
        }

        return !empty($settings) ? $settings : null;
    }

    /**
     * Store options for a question
     */
    private function storeOptions($questionId, $request)
    {
        $options = [];

        if ($request->has('options')) {
            foreach ($request->options as $index => $optionData) {
                if (!empty($optionData['option_text_en']) && !empty($optionData['option_text_fr'])) {
                    $options[] = [
                        'option_text_en' => $optionData['option_text_en'],
                        'option_text_fr' => $optionData['option_text_fr'],
                        'option_subtitle_en' => $optionData['option_subtitle_en'] ?? null,
                        'option_subtitle_fr' => $optionData['option_subtitle_fr'] ?? null,
                        'order' => $index + 1,
                        'is_active' => true,
                    ];
                }
            }
        }

        if (!empty($options)) {
            $this->OptionRep->StoreOptionsForQuestion($questionId, $options);
        }
    }

    /**
     * Delete a question
     */
    public function DeleteQuestion($id)
    {
        try {
            $question = $this->QuestionRep->GetQuestion($id);
            $sectionId = $question->section_id;

            $this->QuestionRep->DeleteQuestion($id);

            Session::flash('message', __('messages.question_deleted'));
            Session::flash('icon', 'success');

            return redirect()->route('managequestions', ['section_id' => $sectionId]);
        } catch (Exception $e) {
            Session::flash('message', __('messages.something_went_wrong'));
            Session::flash('icon', 'danger');
            return redirect()->back();
        }
    }

    /**
     * Change question status
     */
    public function ChangeStatus($id, $status)
    {
        try {
            $this->QuestionRep->ChangeStatus($id, $status);
            return 1;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get question type options for dropdown
     */
    public function GetQuestionTypes()
    {
        return Question::getTypeOptions();
    }

    /**
     * Export questions for a section
     */
    public function ExportQuestions($sectionId)
    {
        $questions = $this->QuestionRep->GetQuestionsBySection($sectionId);
        $section = $this->SectionRep->GetSection($sectionId);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set Header
        $headers = [
            'ID' => 'id',
            'Question Text (EN)' => 'question_text_en',
            'Question Text (FR)' => 'question_text_fr',
            'Type' => 'question_type',
            'Helper Text (EN)' => 'helper_text_en',
            'Helper Text (FR)' => 'helper_text_fr',
            'Required' => 'is_required',
            'Active' => 'is_active',
            'Order' => 'order',
            'Options (Pipe Separated)' => 'options', // For select questions
            'Min Value' => 'min_value', // For rating
            'Max Value' => 'max_value', // For rating
            'Min Chars' => 'min_characters', // For text
            'Max Chars' => 'max_characters', // For text
            'Placeholder (EN)' => 'placeholder_en', // For text
            'Placeholder (FR)' => 'placeholder_fr', // For text
        ];

        $col = 1;
        foreach ($headers as $label => $key) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . '1', $label);
            $col++;
        }

        $row = 2;
        foreach ($questions as $question) {
            $col = 1;

            // Prepare Options String
            $optionsString = '';
            if ($question->requiresOptions()) {
                $optionsList = [];
                foreach ($question->options as $opt) {
                    $textEn = str_replace(['|', ':'], ['/', '-'], $opt->option_text_en);
                    $textFr = str_replace(['|', ':'], ['/', '-'], $opt->option_text_fr);
                    $optionsList[] = $textEn . ':' . $textFr;
                }
                $optionsString = implode('|', $optionsList);
            }

            // Extract Settings
            $settings = $question->settings ?? [];

            $data = [
                'id' => $question->id,
                'question_text_en' => $question->question_text_en,
                'question_text_fr' => $question->question_text_fr,
                'question_type' => $question->question_type,
                'helper_text_en' => $question->helper_text_en,
                'helper_text_fr' => $question->helper_text_fr,
                'is_required' => $question->is_required ? 'Yes' : 'No',
                'is_active' => $question->is_active ? 'Yes' : 'No',
                'order' => $question->order,
                'options' => $optionsString,
                'min_value' => $settings['min_value'] ?? '',
                'max_value' => $settings['max_value'] ?? '',
                'min_characters' => $settings['min_characters'] ?? '',
                'max_characters' => $settings['max_characters'] ?? '',
                'placeholder_en' => $settings['placeholder_en'] ?? '',
                'placeholder_fr' => $settings['placeholder_fr'] ?? '',
            ];

            foreach ($headers as $label => $key) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $row, $data[$key]);
                $col++;
            }
            $row++;
        }

        $fileName = 'questions_' . preg_replace('/[^A-Za-z0-9\-]/', '', $section->title_en) . '_' . date('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($fileName) . '"');
        $writer->save('php://output');
        exit;
    }

    /**
     * Import questions from file
     */
    public function ImportQuestions($request)
    {
        try {
            $file = $request->file('file');
            $sectionId = $request->section_id;

            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Skip header
            $header = array_shift($rows);

            foreach ($rows as $row) {
                // Ensure row has enough columns
                if (count($row) < 16) continue;
                if (empty($row[1])) continue; // Skip if no question text

                $id = isset($row[0]) && is_numeric($row[0]) ? (int)$row[0] : 0;

                // Parse Settings
                $settings = [];
                if (!empty($row[10])) $settings['min_value'] = (int)$row[10];
                if (!empty($row[11])) $settings['max_value'] = (int)$row[11];
                if (!empty($row[12])) $settings['min_characters'] = (int)$row[12];
                if (!empty($row[13])) $settings['max_characters'] = (int)$row[13];
                if (!empty($row[14])) $settings['placeholder_en'] = $row[14];
                if (!empty($row[15])) $settings['placeholder_fr'] = $row[15];

                $questionData = [
                    'section_id' => $sectionId,
                    'question_text_en' => $row[1],
                    'question_text_fr' => $row[2],
                    'question_type' => $row[3],
                    'helper_text_en' => $row[4],
                    'helper_text_fr' => $row[5],
                    'is_required' => strtolower($row[6]) === 'yes' ? 1 : 0,
                    'is_active' => strtolower($row[7]) === 'yes' ? 1 : 0,
                    'order' => (int)$row[8] ?: 0,
                    'settings' => !empty($settings) ? $settings : null,
                ];

                $question = $this->QuestionRep->StoreQuestion($questionData, $id);

                // Handle Options
                if (!empty($row[9]) && in_array($questionData['question_type'], ['single_select', 'multi_select'])) {
                    $rawOptions = explode('|', $row[9]);
                    $options = [];
                    foreach ($rawOptions as $index => $rawOpt) {
                        $parts = explode(':', $rawOpt);
                        if (count($parts) >= 2) {
                            $options[] = [
                                'option_text_en' => trim($parts[0]),
                                'option_text_fr' => trim($parts[1]),
                                'order' => $index + 1,
                                'is_active' => true
                            ];
                        } elseif (count($parts) == 1) {
                            $options[] = [
                                'option_text_en' => trim($parts[0]),
                                'option_text_fr' => trim($parts[0]), // Fallback
                                'order' => $index + 1,
                                'is_active' => true
                            ];
                        }
                    }
                    if (!empty($options)) {
                        // First delete existing options if updating
                        $this->OptionRep->DeleteOptionsByQuestion($question->id);
                        $this->OptionRep->StoreOptionsForQuestion($question->id, $options);
                    }
                }
            }

            logAdminActivity('Personalized Experience', 'Import Questions', null, "Imported questions for section ID: $sectionId");

            Session::flash('message', __('messages.questions_imported'));
            Session::flash('icon', 'success');
            return redirect()->route('managequestions', ['section_id' => $sectionId]);
        } catch (Exception $e) {
            Session::flash('message', __('messages.something_went_wrong') . ': ' . $e->getMessage());
            Session::flash('icon', 'danger');
            return redirect()->back();
        }
    }

    /**
     * Download example file for import
     */
    public function DownloadExample()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set Header
        $headers = [
            'ID' => 'id',
            'Question Text (EN)' => 'question_text_en',
            'Question Text (FR)' => 'question_text_fr',
            'Type' => 'question_type',
            'Helper Text (EN)' => 'helper_text_en',
            'Helper Text (FR)' => 'helper_text_fr',
            'Required' => 'is_required',
            'Active' => 'is_active',
            'Order' => 'order',
            'Options (Pipe Separated)' => 'options', // For select questions
            'Min Value' => 'min_value', // For rating
            'Max Value' => 'max_value', // For rating
            'Min Chars' => 'min_characters', // For text
            'Max Chars' => 'max_characters', // For text
            'Placeholder (EN)' => 'placeholder_en', // For text
            'Placeholder (FR)' => 'placeholder_fr', // For text
        ];

        $col = 1;
        foreach ($headers as $label => $key) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . '1', $label);
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
            $col++;
        }

        // Add Example Rows
        $examples = [
            [
                'id' => '',
                'question_text_en' => 'What is your gender?',
                'question_text_fr' => 'Quel est votre sexe ?',
                'question_type' => 'single_select',
                'helper_text_en' => 'Select one option',
                'helper_text_fr' => 'Sélectionnez une option',
                'is_required' => 'Yes',
                'is_active' => 'Yes',
                'order' => '1',
                'options' => 'Male : Homme | Female : Femme | Other : Autre',
                'min_value' => '',
                'max_value' => '',
                'min_characters' => '',
                'max_characters' => '',
                'placeholder_en' => '',
                'placeholder_fr' => '',
            ],
            [
                'id' => '',
                'question_text_en' => 'Which fruits do you like?',
                'question_text_fr' => 'Quels fruits aimez-vous ?',
                'question_type' => 'multi_select',
                'helper_text_en' => 'Select all that apply',
                'helper_text_fr' => 'Tout sélectionner qui s\'applique',
                'is_required' => 'No',
                'is_active' => 'Yes',
                'order' => '2',
                'options' => 'Apply : Pomme | Banana : Banane | Orange : Orange',
                'min_value' => '',
                'max_value' => '',
                'min_characters' => '',
                'max_characters' => '',
                'placeholder_en' => '',
                'placeholder_fr' => '',
            ],
            [
                'id' => '',
                'question_text_en' => 'Rate our service',
                'question_text_fr' => 'Évaluez notre service',
                'question_type' => 'rating_scale',
                'helper_text_en' => '1 to 5 stars',
                'helper_text_fr' => '1 à 5 étoiles',
                'is_required' => 'Yes',
                'is_active' => 'Yes',
                'order' => '3',
                'options' => '',
                'min_value' => '1',
                'max_value' => '5',
                'min_characters' => '',
                'max_characters' => '',
                'placeholder_en' => '',
                'placeholder_fr' => '',
            ],
            [
                'id' => '',
                'question_text_en' => 'Comments',
                'question_text_fr' => 'Commentaires',
                'question_type' => 'text_input',
                'helper_text_en' => 'Tell us more',
                'helper_text_fr' => 'Dites-nous en plus',
                'is_required' => 'No',
                'is_active' => 'Yes',
                'order' => '4',
                'options' => '',
                'min_value' => '',
                'max_value' => '',
                'min_characters' => '10',
                'max_characters' => '500',
                'placeholder_en' => 'Enter your comments here...',
                'placeholder_fr' => 'Entrez vos commentaires ici...',
            ],
        ];

        $row = 2;
        foreach ($examples as $data) {
            $col = 1;
            foreach ($headers as $label => $key) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $row, $data[$key]);
                $col++;
            }
            $row++;
        }

        $fileName = 'question_import_example.xlsx';
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($fileName) . '"');
        $writer->save('php://output');
    }
    /**
     * Delete all questions for a section
     */
    public function DeleteAllQuestions($sectionId)
    {
        try {
            $this->QuestionRep->DeleteAllQuestions($sectionId);

            Session::flash('message', __('messages.all_questions_deleted'));
            Session::flash('icon', 'success');

            return redirect()->route('managequestions', ['section_id' => $sectionId]);
        } catch (Exception $e) {
            Session::flash('message', __('messages.something_went_wrong'));
            Session::flash('icon', 'danger');
            return redirect()->back();
        }
    }
}
