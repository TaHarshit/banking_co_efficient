<?php

namespace App\Classes\Api;

use App\Repositories\Admin\SectionRepository;
use App\Repositories\Admin\QuestionRepository;
use App\Repositories\Admin\QuestionOptionRepository;
use App\Repositories\Api\UserResponseRepository;
use App\Models\Question;
use App\Models\UserResponse;
use Exception;

class PersonalizedExperienceCls
{
    protected $SectionRep;
    protected $QuestionRep;
    protected $OptionRep;
    protected $ResponseRep;

    public function __construct(
        SectionRepository $SectionRep,
        QuestionRepository $QuestionRep,
        QuestionOptionRepository $OptionRep,
        UserResponseRepository $ResponseRep
    ) {
        $this->SectionRep = $SectionRep;
        $this->QuestionRep = $QuestionRep;
        $this->OptionRep = $OptionRep;
        $this->ResponseRep = $ResponseRep;
    }

    /**
     * Get all sections with questions for the experience flow
     * If businessId is provided, returns business-specific sections.
     * Falls back to admin sections if business has no sections.
     */
    public function GetExperienceData($locale = 'en', $businessId = null)
    {
        try {
            app()->setLocale($locale);

            // Get sections based on business_id with fallback to admin sections
            $sections = $this->SectionRep->GetActiveSectionsByBusiness($businessId);
            $result = [];

            foreach ($sections as $section) {
                $sectionData = [
                    'id' => $section->id,
                    'title' => $section->title,
                    'subtitle' => $section->subtitle,
                    'header' => $section->header,
                    'order' => $section->order,
                    'questions' => [],
                ];

                $questions = $this->QuestionRep->GetActiveQuestionsBySection($section->id);

                foreach ($questions as $question) {
                    $questionData = [
                        'id' => $question->id,
                        'type' => $question->question_type,
                        'text' => $question->question_text,
                        'helper_text' => $question->helper_text,
                        'is_required' => $question->is_required,
                        'settings' => $question->settings,
                        'order' => $question->order,
                        'options' => [],
                    ];

                    // Add options for select-type questions
                    if ($question->requiresOptions()) {
                        $options = $this->OptionRep->GetActiveOptionsByQuestion($question->id);
                        foreach ($options as $option) {
                            $questionData['options'][] = [
                                'id' => $option->id,
                                'text' => $option->option_text,
                                'subtitle' => $option->option_subtitle,
                            ];
                        }
                    }

                    // Add rating scale options for rating questions
                    if ($question->isRatingScale()) {
                        $settings = $question->settings ?? [];
                        $questionData['rating_options'] = [
                            'min' => $settings['min_value'] ?? 1,
                            'max' => $settings['max_value'] ?? 5,
                        ];

                        // Add rating row labels if they exist (for matrix-style ratings)
                        $options = $this->OptionRep->GetActiveOptionsByQuestion($question->id);
                        foreach ($options as $option) {
                            $questionData['options'][] = [
                                'id' => $option->id,
                                'text' => $option->option_text,
                                'subtitle' => $option->option_subtitle,
                            ];
                        }
                    }

                    $sectionData['questions'][] = $questionData;
                }

                $result[] = $sectionData;
            }

            return [
                'success' => true,
                'data' => $result,
                'total_sections' => count($result),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Submit user responses
     */
    public function SubmitResponses($userId, $responses)
    {
        try {
            foreach ($responses as $response) {
                $questionId = $response['question_id'];
                $question = $this->QuestionRep->GetQuestion($questionId);

                if (!$question) {
                    continue;
                }

                // Delete existing responses for this question (for updating)
                $this->ResponseRep->DeleteResponsesByQuestion($userId, $questionId);

                switch ($question->question_type) {
                    case Question::TYPE_SINGLE_SELECT:
                        if (isset($response['option_id'])) {
                            $this->ResponseRep->StoreResponse([
                                'user_id' => $userId,
                                'question_id' => $questionId,
                                'response_type' => UserResponse::TYPE_OPTION,
                                'option_id' => $response['option_id'],
                            ]);
                        }
                        break;

                    case Question::TYPE_MULTI_SELECT:
                        if (isset($response['option_ids']) && is_array($response['option_ids'])) {
                            foreach ($response['option_ids'] as $optionId) {
                                $this->ResponseRep->StoreResponse([
                                    'user_id' => $userId,
                                    'question_id' => $questionId,
                                    'response_type' => UserResponse::TYPE_OPTION,
                                    'option_id' => $optionId,
                                ]);
                            }
                        }
                        break;

                    case Question::TYPE_RATING_SCALE:
                        if (isset($response['rating_value'])) {
                            $this->ResponseRep->StoreResponse([
                                'user_id' => $userId,
                                'question_id' => $questionId,
                                'response_type' => UserResponse::TYPE_RATING,
                                'rating_value' => $response['rating_value'],
                            ]);
                        }
                        // Handle matrix-style ratings (multiple ratings per question)
                        if (isset($response['ratings']) && is_array($response['ratings'])) {
                            foreach ($response['ratings'] as $optionId => $ratingValue) {
                                $this->ResponseRep->StoreResponse([
                                    'user_id' => $userId,
                                    'question_id' => $questionId,
                                    'response_type' => UserResponse::TYPE_RATING,
                                    'option_id' => $optionId,
                                    'rating_value' => $ratingValue,
                                ]);
                            }
                        }
                        break;

                    case Question::TYPE_TEXT_INPUT:
                        if (isset($response['text_value']) && !empty($response['text_value'])) {
                            $this->ResponseRep->StoreResponse([
                                'user_id' => $userId,
                                'question_id' => $questionId,
                                'response_type' => UserResponse::TYPE_TEXT,
                                'response_value' => $response['text_value'],
                            ]);
                        }
                        break;
                }
            }

            return [
                'success' => true,
                'message' => 'Responses saved successfully',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get user's responses organized by sections
     */
    public function GetUserResponses($userId, $locale = 'en')
    {
        try {
            app()->setLocale($locale);
            $responses = $this->ResponseRep->GetResponsesByUser($userId);

            $sections = $responses->groupBy('question.section.id')->map(function ($sectionResponses, $sectionId) {
                $section = $sectionResponses->first()->question->section;

                $questions = $sectionResponses->groupBy('question.id')->map(function ($questionResponses, $questionId) {
                    $question = $questionResponses->first()->question;

                    $responsesData = $questionResponses->map(function ($response) {
                        $data = [
                            'response_type' => $response->response_type,
                        ];

                        if ($response->option_id && $response->option) {
                            $data['option_text'] = $response->option->option_text;
                            $data['option_subtitle'] = $response->option->option_subtitle;
                        }

                        if ($response->response_value) {
                            $data['response_value'] = $response->response_value;
                        }

                        if ($response->rating_value) {
                            $data['rating_value'] = $response->rating_value;
                        }

                        return $data;
                    });

                    return [
                        'id' => $question->id,
                        'text' => $question->question_text,
                        'type' => $question->question_type,
                        'helper_text' => $question->helper_text,
                        'responses' => $responsesData->values(),
                    ];
                });

                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'subtitle' => $section->subtitle,
                    'header' => $section->header,
                    'order' => $section->order,
                    'questions' => $questions->values(),
                ];
            });

            return [
                'success' => true,
                'data' => $sections->values(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get completion status for a user
     */
    public function GetCompletionStatus($userId)
    {
        try {
            $sections = $this->SectionRep->GetActiveSections();
            $totalQuestions = 0;

            foreach ($sections as $section) {
                $totalQuestions += $this->QuestionRep
                    ->GetActiveQuestionsBySection($section->id)
                    ->count();
            }

            $percentage = $this->ResponseRep->GetCompletionPercentage($userId, $totalQuestions);

            return [
                'success' => true,
                'total_questions' => $totalQuestions,
                'completion_percentage' => $percentage,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
