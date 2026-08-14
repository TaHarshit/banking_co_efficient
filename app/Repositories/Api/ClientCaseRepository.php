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

    public function GetUserCases($userId, $search = null, $rating = null, $clientId = null)
    {
        $query = $this->model->where('user_id', $userId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('case_reference', 'LIKE', "%{$search}%")
                    ->orWhere('client_alias', 'LIKE', "%{$search}%")
                    ->orWhere('client_id', 'LIKE', "%{$search}%")
                    ->orWhere('context_overview', 'LIKE', "%{$search}%");
            });
        }

        if ($rating !== null && $rating !== '') {
            $query->where('plan_rating', $rating);
        }

        if (! empty($clientId)) {
            $query->where('client_id', $clientId);
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function GetCaseDetails($id, $userId)
    {
        return $this->model->where('id', $id)->where('user_id', $userId)->first();
    }

    public function getPreviousClientCases($userId, $clientId, $excludeCaseId = null, int $limit = 3)
    {
        if (empty($clientId)) {
            return collect();
        }

        return $this->model->where('user_id', $userId)
            ->where('client_id', $clientId)
            ->when($excludeCaseId, fn ($q) => $q->where('id', '!=', $excludeCaseId))
            ->where(function ($q) {
                $q->whereNotNull('ai_analysis')
                    ->orWhereNotNull('action_plan');
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getDistinctClients($userId, $search = null)
    {
        $query = $this->model->where('user_id', $userId)
            ->whereNotNull('client_id')
            ->where('client_id', '!=', '');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('client_id', 'LIKE', "%{$search}%")
                    ->orWhere('client_alias', 'LIKE', "%{$search}%");
            });
        }

        return $query->select(
                'client_id',
                \Illuminate\Support\Facades\DB::raw('MAX(client_alias) as client_alias'),
                \Illuminate\Support\Facades\DB::raw('COUNT(id) as total_cases'),
                \Illuminate\Support\Facades\DB::raw('MAX(created_at) as last_case_date')
            )
            ->groupBy('client_id')
            ->orderBy('last_case_date', 'desc')
            ->get();
    }

    public function checkClientIdExists($userId, $clientId)
    {
        if (empty($clientId)) {
            return null;
        }

        return $this->model->where('user_id', $userId)
            ->where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function countClientCases($userId, $clientId)
    {
        if (empty($clientId)) {
            return 0;
        }

        return $this->model->where('user_id', $userId)
            ->where('client_id', $clientId)
            ->count();
    }
}
