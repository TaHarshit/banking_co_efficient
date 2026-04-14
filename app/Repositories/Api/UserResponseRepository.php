<?php

namespace App\Repositories\Api;

use App\Models\UserResponse;
use App\Repositories\BaseRepository;

class UserResponseRepository extends BaseRepository
{
    public function model()
    {
        return UserResponse::class;
    }

    /**
     * Get all responses for a user
     */
    public function GetResponsesByUser($userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->with(['question.section', 'option'])
            ->get();
    }

    /**
     * Get response for a specific question by a user
     */
    public function GetResponseByQuestion($questionId, $userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('question_id', $questionId)
            ->first();
    }

    /**
     * Get all responses for a specific question by a user (for multi-select)
     */
    public function GetResponsesByQuestion($questionId, $userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('question_id', $questionId)
            ->get();
    }

    /**
     * Store a response
     */
    public function StoreResponse($data)
    {
        $responseData = [
            'user_id' => $data['user_id'],
            'question_id' => $data['question_id'],
            'response_type' => $data['response_type'],
            'option_id' => $data['option_id'] ?? null,
            'response_value' => $data['response_value'] ?? null,
            'rating_value' => $data['rating_value'] ?? null,
        ];

        return $this->model->create($responseData);
    }

    /**
     * Update or create a response for a question
     */
    public function UpdateOrCreateResponse($userId, $questionId, $data)
    {
        return $this->model->updateOrCreate(
            [
                'user_id' => $userId,
                'question_id' => $questionId,
            ],
            [
                'response_type' => $data['response_type'],
                'option_id' => $data['option_id'] ?? null,
                'response_value' => $data['response_value'] ?? null,
                'rating_value' => $data['rating_value'] ?? null,
            ]
        );
    }

    /**
     * Delete all responses for a user
     */
    public function DeleteResponsesByUser($userId)
    {
        return $this->model->where('user_id', $userId)->delete();
    }

    /**
     * Delete responses for a specific question by a user
     */
    public function DeleteResponsesByQuestion($userId, $questionId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('question_id', $questionId)
            ->delete();
    }

    /**
     * Check if user has completed all required questions
     */
    public function HasUserCompleted($userId, $requiredQuestionIds)
    {
        $answeredCount = $this->model
            ->where('user_id', $userId)
            ->whereIn('question_id', $requiredQuestionIds)
            ->distinct('question_id')
            ->count('question_id');

        return $answeredCount >= count($requiredQuestionIds);
    }

    /**
     * Get completion percentage for a user
     */
    public function GetCompletionPercentage($userId, $totalQuestions)
    {
        if ($totalQuestions == 0) {
            return 100;
        }

        $answeredCount = $this->model
            ->where('user_id', $userId)
            ->distinct('question_id')
            ->count('question_id');

        return round(($answeredCount / $totalQuestions) * 100);
    }
}
