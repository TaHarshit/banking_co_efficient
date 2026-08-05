<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaseStudyQuestion;
use App\Models\CaseStudyQuestionOption;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CaseStudyQuestionController extends Controller
{
    public function index()
    {
        $questions = CaseStudyQuestion::with('options')->orderBy('section_name')->paginate(20);
        return view('admin.case_study_questions.index', compact('questions'));
    }

    public function create()
    {
        return view('admin.case_study_questions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'section_name_en' => 'required|string|max:255',
            'section_name_fr' => 'required|string|max:255',
            'question_en' => 'required|string',
            'question_fr' => 'required|string',
            'options' => 'required|array',
            'options.*.en' => 'required|string',
            'options.*.fr' => 'required|string',
            'options.*.is_correct' => 'sometimes|boolean'
        ]);

        $question = CaseStudyQuestion::create([
            'section_name' => $request->section_name_en,
            'section_name_en' => $request->section_name_en,
            'section_name_fr' => $request->section_name_fr,
            'question_en' => $request->question_en,
            'question_fr' => $request->question_fr,
        ]);

        foreach ($request->options as $option) {
            $question->options()->create([
                'option_en' => $option['en'],
                'option_fr' => $option['fr'],
                'is_correct' => isset($option['is_correct']) && $option['is_correct'] ? true : false,
            ]);
        }

        logAdminActivity('Case Study', 'Add', $question->id, "Added new case study question in section: {$request->section_name_en}", $request->all());

        return redirect()->route('admin.case_study_questions.index')->with('success', 'Question created successfully.');
    }

    public function edit(CaseStudyQuestion $question)
    {
        $question->load('options');
        return view('admin.case_study_questions.edit', compact('question'));
    }

    public function update(Request $request, CaseStudyQuestion $question)
    {
        $request->validate([
            'section_name_en' => 'required|string|max:255',
            'section_name_fr' => 'required|string|max:255',
            'question_en' => 'required|string',
            'question_fr' => 'required|string',
            'options' => 'required|array',
            'options.*.en' => 'required|string',
            'options.*.fr' => 'required|string',
            'options.*.is_correct' => 'sometimes|boolean'
        ]);

        $question->update([
            'section_name' => $request->section_name_en,
            'section_name_en' => $request->section_name_en,
            'section_name_fr' => $request->section_name_fr,
            'question_en' => $request->question_en,
            'question_fr' => $request->question_fr,
        ]);

        $question->options()->delete();

        foreach ($request->options as $option) {
            $question->options()->create([
                'option_en' => $option['en'],
                'option_fr' => $option['fr'],
                'is_correct' => isset($option['is_correct']) && $option['is_correct'] ? true : false,
            ]);
        }

        logAdminActivity('Case Study', 'Update', $question->id, "Updated case study question in section: {$request->section_name_en}", $request->all());

        return redirect()->route('admin.case_study_questions.index')->with('success', 'Question updated successfully.');
    }

    public function destroy(CaseStudyQuestion $question)
    {
        $id = $question->id;
        $section = $question->section_name_en ?: $question->section_name;
        $question->delete();
        logAdminActivity('Case Study', 'Delete', $id, "Deleted case study question from section: $section");
        return redirect()->route('admin.case_study_questions.index')->with('success', 'Question deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('file');
        
        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Remove header
            array_shift($rows);

            foreach ($rows as $row) {
                if (empty($row[0])) continue; // skip if section name is empty

                $question = CaseStudyQuestion::create([
                    'section_name' => $row[0],
                    'section_name_en' => $row[0],
                    'section_name_fr' => $row[0],
                    'question_en' => $row[1] ?? '',
                    'question_fr' => $row[2] ?? '',
                ]);

                // Option 1
                if (!empty($row[3]) || !empty($row[4])) {
                    $question->options()->create([
                        'option_en' => $row[3] ?? '',
                        'option_fr' => $row[4] ?? '',
                        'is_correct' => strtolower(trim($row[5] ?? '')) === 'yes' || $row[5] == 1,
                    ]);
                }

                // Option 2
                if (!empty($row[6]) || !empty($row[7])) {
                    $question->options()->create([
                        'option_en' => $row[6] ?? '',
                        'option_fr' => $row[7] ?? '',
                        'is_correct' => strtolower(trim($row[8] ?? '')) === 'yes' || $row[8] == 1,
                    ]);
                }

                // Option 3
                if (!empty($row[9]) || !empty($row[10])) {
                    $question->options()->create([
                        'option_en' => $row[9] ?? '',
                        'option_fr' => $row[10] ?? '',
                        'is_correct' => strtolower(trim($row[11] ?? '')) === 'yes' || $row[11] == 1,
                    ]);
                }

                // Option 4
                if (!empty($row[12]) || !empty($row[13])) {
                    $question->options()->create([
                        'option_en' => $row[12] ?? '',
                        'option_fr' => $row[13] ?? '',
                        'is_correct' => strtolower(trim($row[14] ?? '')) === 'yes' || $row[14] == 1,
                    ]);
                }
            }

            logAdminActivity('Case Study', 'Import', null, "Imported case study questions from file: " . $file->getClientOriginalName());

            return redirect()->route('admin.case_study_questions.index')->with('success', 'Import successful!');
        } catch (\Exception $e) {
            return redirect()->route('admin.case_study_questions.index')->with('error', 'Error during import: ' . $e->getMessage());
        }
    }
}
