<?php

namespace App\Classes\Api;

use App\Repositories\Api\ClientCaseRepository;
use App\Repositories\Api\CaseStudyQuestionRepository;
use App\General\Validate;
use App\General\General;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class ClientCaseCls
{
    protected $clientCaseRepository;
    protected $caseStudyQuestionRepository;

    public function __construct(
        ClientCaseRepository $clientCaseRepository,
        CaseStudyQuestionRepository $caseStudyQuestionRepository
    ) {
        $this->clientCaseRepository = $clientCaseRepository;
        $this->caseStudyQuestionRepository = $caseStudyQuestionRepository;
    }

    public function CreateCase($postData)
    {
        try {
            // Validation
            $validator = Validate::required($postData, ['client_alias']);
            if ($validator->fails()) {
                return General::setResponse('VALIDATION_ERROR', $validator->errors()->first());
            }

            // Structure data for storage
            $data = [
                'user_id' => Auth::id(),
                'case_reference' => $postData['case_reference'] ?? null,
                'client_alias' => $postData['client_alias'],
                'context_overview' => $postData['context_overview'] ?? null,
                'case_details' => $postData['case_details'] ?? [],
            ];

            DB::beginTransaction();
            $case = $this->clientCaseRepository->Store($data);
            DB::commit();

            if ($case) {
                $response = General::setResponse('SUCCESS', 'Case created successfully.');
                $response['data'] = $case;
                return $response;
            } else {
                return General::setResponse('VALIDATION_ERROR', 'Failed to create case.');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function GetCases($search = null)
    {
        try {
            $cases = $this->clientCaseRepository->GetUserCases(Auth::id(), $search);
            $response = General::setResponse('SUCCESS', 'Cases retrieved successfully.');
            $response['data'] = $cases;
            return $response;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function GetCaseDetails($id)
    {
        try {
            $case = $this->clientCaseRepository->GetCaseDetails($id, Auth::id());

            if (!$case) {
                return General::setResponse('VALIDATION_ERROR', 'Case not found.');
            }

            $response = General::setResponse('SUCCESS', 'Case details retrieved successfully.');
            $response['data'] = $case;
            return $response;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }

    public function GetCaseStudySections($locale = 'en')
    {
        try {
            app()->setLocale($locale);

            $questions = $this->caseStudyQuestionRepository->getAllSectionsWithQuestions();
            
            $grouped = $questions->groupBy('section_name')->map(function ($sectionQuestions, $sectionName) use ($locale) {
                return [
                    'section_name' => $sectionName,
                    'locale' => $locale,
                    'questions' => $sectionQuestions->map(function ($question) {
                        return [
                            'id' => $question->id,
                            'question_text' => $question->question,
                            'options' => $question->options->map(function ($option) {
                                return [
                                    'id' => $option->id,
                                    'option_text' => $option->option,
                                    'is_correct' => $option->is_correct,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })->values();

            $response = General::setResponse('SUCCESS', 'Case study sections retrieved successfully.');
            $response['data'] = $grouped;
            return $response;
        } catch (Exception $e) {
            return General::setResponse('OTHER_ERROR', $e->getMessage());
        }
    }
}
