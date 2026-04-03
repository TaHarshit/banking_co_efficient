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
     * Get all sections with their questions and options
     */
    public function getAllSectionsWithQuestions(): Collection
    {
        return $this->model->with(['options'])
            ->orderBy('section_name')
            ->get();
    }
}
