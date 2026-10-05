<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\CaseStudyQuestion;
use App\Models\CaseStudyQuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BusinessPolicyController extends Controller
{
    private function getBusiness(): Business
    {
        /** @var Business $business */
        $business = Auth::guard('business')->user();
        return $business;
    }

    public function index()
    {
        $business = $this->getBusiness();
        $sections = $business->formatted_policy_sections;
        $questions = CaseStudyQuestion::with('options')
            ->where('business_id', $business->id)
            ->orderBy('section_name')
            ->paginate(20);

        $hasCustomQuestions = $questions->total() > 0;
        $globalQuestionsCount = CaseStudyQuestion::whereNull('business_id')->count();

        return view('business.policies.index', compact('business', 'sections', 'questions', 'hasCustomQuestions', 'globalQuestionsCount'));
    }

    public function updateMessage(Request $request)
    {
        $business = $this->getBusiness();

        if ($request->has('sections')) {
            $request->validate([
                'sections' => 'nullable|array|max:3',
                'sections.*.title_en' => 'nullable|string|max:255',
                'sections.*.title_fr' => 'nullable|string|max:255',
                'sections.*.title' => 'nullable|string|max:255',
                'sections.*.points' => 'nullable|array',
            ]);

            $cleanSections = [];
            $summaryLines = [];
            $hasAnyContent = false;

            $inputSections = $request->input('sections', []);
            for ($i = 0; $i < 3; $i++) {
                $secData = $inputSections[$i] ?? [];
                $titleEn = trim($secData['title_en'] ?? ($secData['title'] ?? ''));
                $titleFr = trim($secData['title_fr'] ?? ($secData['title'] ?? ''));
                $rawPoints = $secData['points'] ?? [];
                $points = [];

                if (is_array($rawPoints)) {
                    foreach ($rawPoints as $pt) {
                        if (is_array($pt)) {
                            $en = trim((string)($pt['en'] ?? ''));
                            $fr = trim((string)($pt['fr'] ?? ''));
                            if ($en !== '' || $fr !== '') {
                                $points[] = [
                                    'en' => $en !== '' ? $en : $fr,
                                    'fr' => $fr !== '' ? $fr : $en,
                                ];
                            }
                        } else {
                            $pStr = trim((string)$pt);
                            if ($pStr !== '') {
                                $points[] = [
                                    'en' => $pStr,
                                    'fr' => $pStr,
                                ];
                            }
                        }
                    }
                }

                if ($titleEn !== '' || $titleFr !== '' || !empty($points)) {
                    $hasAnyContent = true;
                }

                $cleanSections[] = [
                    'id' => $i + 1,
                    'title' => $titleEn ?: $titleFr,
                    'title_en' => $titleEn,
                    'title_fr' => $titleFr,
                    'points' => $points,
                ];

                if ($titleEn !== '' || !empty($points)) {
                    if ($titleEn !== '') {
                        $summaryLines[] = ($i + 1) . '. ' . $titleEn;
                    }
                    foreach ($points as $p) {
                        $summaryLines[] = '  • ' . $p['en'];
                    }
                    $summaryLines[] = '';
                }
            }

            $businessPoliciesMessage = $hasAnyContent ? trim(implode("\n", $summaryLines)) : null;

            $business->update([
                'policy_sections' => $hasAnyContent ? $cleanSections : null,
                'business_policies_message' => $businessPoliciesMessage,
            ]);
        } else {
            $request->validate([
                'business_policies_message' => 'nullable|string|max:5000',
            ]);

            $message = $request->business_policies_message;
            $cleanSections = null;
            if (!empty($message)) {
                $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)$message)), fn($l) => $l !== ''));
                $cleanPoints = array_map(fn($line) => ['en' => $line, 'fr' => $line], $lines);
                $cleanSections = [
                    ['id' => 1, 'title' => 'Policy Guidelines', 'title_en' => 'Policy Guidelines', 'title_fr' => 'Lignes directrices sur les politiques', 'points' => $cleanPoints],
                    ['id' => 2, 'title' => '', 'title_en' => '', 'title_fr' => '', 'points' => []],
                    ['id' => 3, 'title' => '', 'title_en' => '', 'title_fr' => '', 'points' => []],
                ];
            }

            $business->update([
                'business_policies_message' => $message,
                'policy_sections' => $cleanSections,
            ]);
        }

        return redirect()->route('business.policies.index')
            ->with('success', __('messages.message_updated_successfully'));
    }

    public function create()
    {
        return view('business.policies.create');
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

        $business = $this->getBusiness();

        $question = CaseStudyQuestion::create([
            'business_id' => $business->id,
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

        return redirect()->route('business.policies.index')
            ->with('success', __('messages.policy_question_created'));
    }

    public function edit($id)
    {
        $business = $this->getBusiness();
        $question = CaseStudyQuestion::with('options')
            ->where('id', $id)
            ->where('business_id', $business->id)
            ->firstOrFail();

        return view('business.policies.edit', compact('question'));
    }

    public function update(Request $request, $id)
    {
        $business = $this->getBusiness();
        $question = CaseStudyQuestion::where('id', $id)
            ->where('business_id', $business->id)
            ->firstOrFail();

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

        return redirect()->route('business.policies.index')
            ->with('success', __('messages.policy_question_updated'));
    }

    public function destroy($id)
    {
        $business = $this->getBusiness();
        $question = CaseStudyQuestion::where('id', $id)
            ->where('business_id', $business->id)
            ->firstOrFail();

        $question->delete();

        return redirect()->route('business.policies.index')
            ->with('success', __('messages.policy_question_deleted'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        $business = $this->getBusiness();
        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Remove header row
            array_shift($rows);

            foreach ($rows as $row) {
                if (empty($row[0])) continue;

                $question = CaseStudyQuestion::create([
                    'business_id' => $business->id,
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

            return redirect()->route('business.policies.index')
                ->with('success', __('messages.import') . ' successful!');
        } catch (\Exception $e) {
            return redirect()->route('business.policies.index')
                ->with('error', 'Error during import: ' . $e->getMessage());
        }
    }
}
