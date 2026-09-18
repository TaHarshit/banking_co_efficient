<?php

namespace App\Repositories\Api;

use App\Models\CaseStudyQuestion;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class CaseStudyQuestionRepository extends BaseRepository
{
    /**
     * Specify the model class name
     */
    public function model(): string
    {
        return CaseStudyQuestion::class;
    }

    /**
     * Get all sections with their questions and options.
     * If $businessId is provided and the business has custom questions, return those.
     * Otherwise fallback to global/default questions (where business_id IS NULL).
     */
    public function getAllSectionsWithQuestions(?int $businessId = null): Collection
    {
        if ($businessId) {
            $hasCustom = $this->model->where('business_id', $businessId)->exists();
            if ($hasCustom) {
                return $this->model->with(['options'])
                    ->where('business_id', $businessId)
                    ->orderBy('section_name')
                    ->get();
            }
        }

        return $this->model->with(['options'])
            ->whereNull('business_id')
            ->orderBy('section_name')
            ->get();
    }
}
