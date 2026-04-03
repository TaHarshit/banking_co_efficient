<?php

namespace App\Classes\Business;

use Illuminate\Support\Facades\Session;
use App\Repositories\Business\QuestionRepository;
use App\Models\Question;
use Exception;

class QuestionCls
{
    protected $QuestionRep;

    public function __construct(QuestionRepository $QuestionRep)
    {
        $this->QuestionRep = $QuestionRep;
    }

    /**
     * Get all questions for a section
     */
    public function GetQuestionsBySection($sectionId, $businessId)
    {
        try {
            return $this->QuestionRep->GetQuestionsBySection($sectionId, $businessId);
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
            return $this->QuestionRep->GetQuestion($id, $businessId);
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
            return $this->QuestionRep->GetNextOrder($sectionId, $businessId);
        } catch (Exception $e) {
            return 1;
        }
    }

    /**
     * Store or update a question
     */
    public function StoreQuestion($request, $sectionId, $businessId, $id = 0)
    {
        try {
            $data = [
                'section_id' => $sectionId,
                'business_id' => $businessId,
                'question_type' => $request->question_type,
                'question_text_en' => $request->question_text_en,
                'question_text_fr' => $request->question_text_fr,
                'helper_text_en' => $request->helper_text_en,
                'helper_text_fr' => $request->helper_text_fr,
                'order' => $request->order ?? $this->GetNextOrder($sectionId, $businessId),
                'is_required' => $request->has('is_required') ? 1 : 0,
                'is_active' => $request->has('is_active') ? 1 : 0,
            ];

            // Add settings for rating scale
            if ($request->question_type === Question::TYPE_RATING_SCALE) {
                $data['settings'] = [
                    'min_value' => $request->min_value ?? 1,
                    'max_value' => $request->max_value ?? 5,
                ];
            }

            $question = $this->QuestionRep->StoreQuestion($data, $id);

            // Store options for select-type questions
            if (in_array($request->question_type, [Question::TYPE_SINGLE_SELECT, Question::TYPE_MULTI_SELECT])) {
                $options = $this->parseOptions($request);
                if (!empty($options)) {
                    $this->QuestionRep->StoreOptions($question->id, $options);
                }
            }

            $message = ($id > 0)
                ? __('messages.question_updated')
                : __('messages.question_created');

            Session::flash('message', $message);
            Session::flash('icon', 'success');

            return redirect()->route('business.questions', $sectionId);
        } catch (Exception $e) {
            Session::flash('message', __('messages.something_went_wrong'));
            Session::flash('icon', 'danger');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Parse options from request
     */
    private function parseOptions($request)
    {
        $options = [];
        $textsEn = $request->input('option_text_en', []);
        $textsFr = $request->input('option_text_fr', []);
        $subtitlesEn = $request->input('option_subtitle_en', []);
        $subtitlesFr = $request->input('option_subtitle_fr', []);

        foreach ($textsEn as $index => $textEn) {
            if (!empty($textEn)) {
                $options[] = [
                    'text_en' => $textEn,
                    'text_fr' => $textsFr[$index] ?? '',
                    'subtitle_en' => $subtitlesEn[$index] ?? null,
                    'subtitle_fr' => $subtitlesFr[$index] ?? null,
                ];
            }
        }

        return $options;
    }

    /**
     * Delete a question
     */
    public function DeleteQuestion($id, $businessId)
    {
        try {
            $question = $this->QuestionRep->GetQuestion($id, $businessId);
            $sectionId = $question ? $question->section_id : null;

            $this->QuestionRep->DeleteQuestion($id, $businessId);

            Session::flash('message', __('messages.question_deleted'));
            Session::flash('icon', 'success');

            if ($sectionId) {
                return redirect()->route('business.questions', $sectionId);
            }
            return redirect()->route('business.sections');
        } catch (Exception $e) {
            Session::flash('message', __('messages.something_went_wrong'));
            Session::flash('icon', 'danger');
            return redirect()->route('business.sections');
        }
    }

    /**
     * Update question order
     */
    public function UpdateOrder($orderedIds, $businessId)
    {
        try {
            return $this->QuestionRep->UpdateOrder($orderedIds, $businessId);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Change question status
     */
    public function ChangeStatus($id, $status, $businessId)
    {
        try {
            $this->QuestionRep->ChangeStatus($id, $status, $businessId);
            return 1;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get question type options
     */
    public function GetTypeOptions()
    {
        return Question::getTypeOptions();
    }

    /**
     * Export questions to CSV
     */
    public function ExportQuestions($sectionId, $businessId, $section)
    {
        try {
            $questions = $this->QuestionRep->GetQuestionsBySection($sectionId, $businessId);

            $filename = "questions_" . str_replace(' ', '_', $section->title_en) . "_" . date('Y-m-d_H-i-s') . ".csv";

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            // Create CSV content
            $csvContent = "ID,Question Type,Question (EN),Question (FR),Helper Text (EN),Helper Text (FR),Order,Required,Status,Options\n";

            foreach ($questions as $question) {
                // Format options as: OptionEN|OptionFR|OptionEN|OptionFR...
                $optionsStr = '';
                if ($question->options && $question->options->count() > 0) {
                    $optionParts = [];
                    foreach ($question->options as $option) {
                        $optionParts[] = $option->text_en . '|' . $option->text_fr;
                    }
                    $optionsStr = implode('||', $optionParts);
                }

                $csvContent .= sprintf(
                    "%d,%s,\"%s\",\"%s\",\"%s\",\"%s\",%d,%s,%s,\"%s\"\n",
                    $question->id,
                    $question->question_type,
                    str_replace('"', '""', $question->question_text_en),
                    str_replace('"', '""', $question->question_text_fr),
                    str_replace('"', '""', $question->helper_text_en ?? ''),
                    str_replace('"', '""', $question->helper_text_fr ?? ''),
                    $question->order,
                    $question->is_required ? 'Yes' : 'No',
                    $question->is_active ? 'Active' : 'Inactive',
                    str_replace('"', '""', $optionsStr)
                );
            }

            return response()->make($csvContent, 200, $headers);
        } catch (Exception $e) {
            Session::flash('message', 'Export failed: ' . $e->getMessage());
            Session::flash('icon', 'danger');
            return redirect()->back();
        }
    }

    /**
     * Import questions from CSV
     */
    public function ImportQuestions($request, $sectionId, $businessId)
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
                    // Skip empty rows
                    if (empty($row[1]) || empty($row[2])) {
                        continue;
                    }

                    $data = [
                        'section_id' => $sectionId,
                        'business_id' => $businessId,
                        'question_type' => strtolower(trim($row[1] ?? 'single_select')),
                        'question_text_en' => trim($row[2] ?? ''),
                        'question_text_fr' => trim($row[3] ?? ''),
                        'helper_text_en' => trim($row[4] ?? ''),
                        'helper_text_fr' => trim($row[5] ?? ''),
                        'order' => intval($row[6] ?? ($importedCount + 1)),
                        'is_required' => strtolower(trim($row[7] ?? 'No')) === 'yes',
                        'is_active' => strtolower(trim($row[8] ?? 'Active')) === 'active',
                    ];

                    $question = $this->QuestionRep->StoreQuestion($data);

                    // Handle options if provided (column 9)
                    if (!empty($row[9]) && in_array($data['question_type'], ['single_select', 'multi_select'])) {
                        $optionsStr = trim($row[9]);
                        $optionPairs = explode('||', $optionsStr);
                        $options = [];

                        foreach ($optionPairs as $pair) {
                            $parts = explode('|', $pair);
                            $textEn = trim($parts[0] ?? '');
                            $textFr = trim($parts[1] ?? $textEn);

                            if (!empty($textEn)) {
                                $options[] = [
                                    'text_en' => $textEn,
                                    'text_fr' => $textFr,
                                ];
                            }
                        }

                        if (!empty($options)) {
                            $this->QuestionRep->StoreOptions($question->id, $options);
                        }
                    }

                    $importedCount++;
                } catch (Exception $e) {
                    $errors[] = "Row " . ($importedCount + 2) . ": " . $e->getMessage();
                }
            }

            fclose($fileHandle);

            if ($importedCount > 0) {
                Session::flash('message', "Successfully imported {$importedCount} questions!");
                Session::flash('icon', 'success');
            }

            if (!empty($errors)) {
                Session::flash('warning', 'Some rows had errors: ' . implode('; ', array_slice($errors, 0, 3)));
            }

            return redirect()->route('business.questions', $sectionId);
        } catch (Exception $e) {
            Session::flash('message', 'Import failed: ' . $e->getMessage());
            Session::flash('icon', 'danger');
            return redirect()->back();
        }
    }

    /**
     * Download example CSV file
     */
    public function DownloadExample()
    {
        try {
            $filename = "personalized_experience_questions_example.csv";

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $csvContent = "ID,Question Type,Question (EN),Question (FR),Helper Text (EN),Helper Text (FR),Order,Required,Status,Options\n";
            $csvContent .= ',single_select,"What is your favorite genre?","Quel est votre genre préféré?","Select one","Sélectionnez un",1,Yes,Active,"Action|Action||Comedy|Comédie||Drama|Drame"' . "\n";
            $csvContent .= ',multi_select,"Select your interests","Sélectionnez vos intérêts","Select all that apply","Sélectionnez tous les correspondants",2,Yes,Active,"Sports|Sports||Music|Musique||Movies|Films"' . "\n";
            $csvContent .= ',rating_scale,"Rate your experience","Évaluez votre expérience","1-5 scale","Échelle 1-5",3,No,Active,' . "\n";
            $csvContent .= ',text_input,"What is your name?","Quel est votre nom?","Enter your full name","Entrez votre nom complet",4,Yes,Active,' . "\n";

            return response()->make($csvContent, 200, $headers);
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
            $questions = $this->QuestionRep->GetQuestionsBySection($sectionId, $businessId);
            $deletedCount = 0;

            foreach ($questions as $question) {
                $this->QuestionRep->DeleteQuestion($question->id, $businessId);
                $deletedCount++;
            }

            Session::flash('message', "Successfully deleted {$deletedCount} questions!");
            Session::flash('icon', 'success');
            return redirect()->route('business.questions', $sectionId);
        } catch (Exception $e) {
            Session::flash('message', 'Delete failed: ' . $e->getMessage());
            Session::flash('icon', 'danger');
            return redirect()->back();
        }
    }
}
