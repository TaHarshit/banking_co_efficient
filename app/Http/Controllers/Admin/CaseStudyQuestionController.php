<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\CaseStudyQuestion;
use App\Models\CaseStudyQuestionOption;
use App\Models\Settings;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CaseStudyQuestionController extends Controller
{
    public function index(Request $request)
    {
        $query = CaseStudyQuestion::with(['options', 'business'])->orderBy('section_name');

        if ($request->filled('business_id')) {
            if ($request->business_id === 'global') {
                $query->whereNull('business_id');
            } else {
                $query->where('business_id', $request->business_id);
            }
        }

        $questions = $query->paginate(20)->withQueryString();
        $businesses = Business::orderBy('name')->get();
        $selectedBusiness = $request->query('business_id');

        $settings = Settings::first();
        if (!$settings) {
            $settings = Settings::create([]);
        }

        $targetBusinessId = null;
        $targetBusinessName = 'Global Default (Individual Users & Banks without custom policies)';
        $targetType = 'global';
        $policyMessage = $settings->business_policies_message;
        $sections = $settings->formatted_policy_sections;

        if ($request->filled('business_id') && $request->business_id !== 'global') {
            $business = Business::find($request->business_id);
            if ($business) {
                $targetBusinessId = $business->id;
                $targetBusinessName = $business->name;
                $targetType = 'business';
                $sections = $business->formatted_policy_sections;
                $policyMessage = $business->business_policies_message;
            }
        }

        return view('admin.case_study_questions.index', compact(
            'questions',
            'businesses',
            'selectedBusiness',
            'sections',
            'targetBusinessId',
            'targetBusinessName',
            'targetType',
            'policyMessage'
        ));
    }

    public function updatePolicySections(Request $request)
    {
        $targetBusinessId = $request->input('target_business_id');

        if ($request->has('sections')) {
            $request->validate([
                'sections' => 'nullable|array|max:3',
                'sections.*.title_en' => 'nullable|string|max:255',
                'sections.*.title_fr' => 'nullable|string|max:255',
                'sections.*.points' => 'nullable|array',
                'sections.*.points.*.en' => 'nullable|string|max:1000',
                'sections.*.points.*.fr' => 'nullable|string|max:1000',
            ]);

            $rawSections = $request->input('sections', []);
            $cleanSections = [];
            $formattedLines = [];
            $hasAnyContent = false;

            for ($i = 0; $i < 3; $i++) {
                $sec = $rawSections[$i] ?? [];
                $titleEn = trim((string)($sec['title_en'] ?? ''));
                $titleFr = trim((string)($sec['title_fr'] ?? ''));
                $points = $sec['points'] ?? [];

                $cleanPoints = [];
                $pointsEnOnly = [];

                if (is_array($points)) {
                    foreach ($points as $p) {
                        $en = is_array($p) ? trim((string)($p['en'] ?? '')) : trim((string)$p);
                        $fr = is_array($p) ? trim((string)($p['fr'] ?? '')) : '';

                        if ($en !== '' || $fr !== '') {
                            $cleanPoints[] = [
                                'en' => $en ?: $fr,
                                'fr' => $fr ?: $en,
                            ];
                            $pointsEnOnly[] = $en ?: $fr;
                        }
                    }
                }

                if ($titleEn !== '' || $titleFr !== '' || !empty($cleanPoints)) {
                    $hasAnyContent = true;
                }

                $cleanSections[] = [
                    'id' => $i + 1,
                    'title' => $titleEn ?: ($titleFr ?: 'Section ' . ($i + 1)),
                    'title_en' => $titleEn,
                    'title_fr' => $titleFr,
                    'points' => $cleanPoints,
                ];

                if ($titleEn !== '' || !empty($pointsEnOnly)) {
                    $displayTitle = $titleEn ?: ('Section ' . ($i + 1));
                    $formattedLines[] = "=== {$displayTitle} ===";
                    foreach ($pointsEnOnly as $pt) {
                        $formattedLines[] = "• {$pt}";
                    }
                    $formattedLines[] = "";
                }
            }

            $businessPoliciesMessage = $hasAnyContent ? trim(implode("\n", $formattedLines)) : null;

            if ($targetBusinessId && $targetBusinessId !== 'global') {
                $business = Business::findOrFail($targetBusinessId);
                $business->update([
                    'policy_sections' => $hasAnyContent ? $cleanSections : null,
                    'business_policies_message' => $businessPoliciesMessage,
                ]);
                $msg = 'Policy sections for ' . $business->name . ' updated successfully.';
            } else {
                $settings = Settings::first();
                if (!$settings) {
                    $settings = Settings::create([]);
                }
                $settings->update([
                    'policy_sections' => $hasAnyContent ? $cleanSections : null,
                    'business_policies_message' => $businessPoliciesMessage,
                ]);
                $msg = 'Global default policy sections updated successfully.';
            }

            return redirect()->back()->with('success', $msg);
        }

        if ($request->has('business_policies_message')) {
            $request->validate([
                'business_policies_message' => 'nullable|string|max:5000',
            ]);
            $msg = $request->business_policies_message;

            if ($targetBusinessId && $targetBusinessId !== 'global') {
                $business = Business::findOrFail($targetBusinessId);
                $business->update([
                    'business_policies_message' => $msg,
                    'policy_sections' => null,
                ]);
                $successMsg = 'Policy message for ' . $business->name . ' updated successfully.';
            } else {
                $settings = Settings::first();
                if (!$settings) {
                    $settings = Settings::create([]);
                }
                $settings->update([
                    'business_policies_message' => $msg,
                    'policy_sections' => null,
                ]);
                $successMsg = 'Global policy message updated successfully.';
            }

            return redirect()->back()->with('success', $successMsg);
        }

        return redirect()->back();
    }

    public function create()
    {
        $businesses = Business::orderBy('name')->get();
        return view('admin.case_study_questions.create', compact('businesses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'business_id' => 'nullable|exists:businesses,id',
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
            'business_id' => $request->filled('business_id') ? $request->business_id : null,
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

        logAdminActivity('Business Policies', 'Add', $question->id, "Added new policy question in section: {$request->section_name_en}", $request->all());

        return redirect()->route('admin.case_study_questions.index')->with('success', 'Question created successfully.');
    }

    public function edit(CaseStudyQuestion $question)
    {
        $question->load(['options', 'business']);
        $businesses = Business::orderBy('name')->get();
        return view('admin.case_study_questions.edit', compact('question', 'businesses'));
    }

    public function update(Request $request, CaseStudyQuestion $question)
    {
        $request->validate([
            'business_id' => 'nullable|exists:businesses,id',
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
            'business_id' => $request->filled('business_id') ? $request->business_id : null,
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

        logAdminActivity('Business Policies', 'Update', $question->id, "Updated policy question in section: {$request->section_name_en}", $request->all());

        return redirect()->route('admin.case_study_questions.index')->with('success', 'Question updated successfully.');
    }

    public function destroy(CaseStudyQuestion $question)
    {
        $id = $question->id;
        $section = $question->section_name_en ?: $question->section_name;
        $question->delete();
        logAdminActivity('Business Policies', 'Delete', $id, "Deleted policy question from section: $section");
        return redirect()->route('admin.case_study_questions.index')->with('success', 'Question deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
            'business_id' => 'nullable|exists:businesses,id',
        ]);

        $file = $request->file('file');
        $businessId = $request->filled('business_id') ? $request->business_id : null;
        
        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Remove header
            array_shift($rows);

            foreach ($rows as $row) {
                if (empty($row[0])) continue; // skip if section name is empty

                $question = CaseStudyQuestion::create([
                    'business_id' => $businessId,
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

            logAdminActivity('Business Policies', 'Import', null, "Imported policy questions from file: " . $file->getClientOriginalName());

            return redirect()->route('admin.case_study_questions.index')->with('success', 'Import successful!');
        } catch (\Exception $e) {
            return redirect()->route('admin.case_study_questions.index')->with('error', 'Error during import: ' . $e->getMessage());
        }
    }
}
