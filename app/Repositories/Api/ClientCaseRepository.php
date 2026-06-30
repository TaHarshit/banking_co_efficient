<?php

namespace App\Repositories\Api;

use App\Models\ClientCase;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Auth;

class ClientCaseRepository extends BaseRepository
{
    public function model()
    {
        return ClientCase::class;
    }

    public function Store($data)
    {
        return $this->model->create($data);
    }

    public function GetUserCases($userId, $search = null, $rating = null)
    {
        $query = $this->model->where('user_id', $userId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('case_reference', 'LIKE', "%{$search}%")
                    ->orWhere('client_alias', 'LIKE', "%{$search}%")
                    ->orWhere('context_overview', 'LIKE', "%{$search}%");
            });
        }

        if ($rating !== null && $rating !== '') {
            $query->where('plan_rating', $rating);
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function GetCaseDetails($id, $userId)
    {
        return $this->model->where('id', $id)->where('user_id', $userId)->first();
    }
}
