<?php

namespace App\Repositories\Api;

use App\Models\Client;
use App\Models\ClientCase;
use App\Models\AiJob;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class ClientRepository extends BaseRepository
{
    public function model()
    {
        return Client::class;
    }

    public function StoreOrUpdate($userId, array $data)
    {
        $clientId = trim((string) ($data['client_id'] ?? ''));
        $clientAlias = trim((string) ($data['client_alias'] ?? ''));
        $notes = $data['notes'] ?? null;
        $id = $data['id'] ?? null;

        if ($id) {
            $client = $this->model->where('id', $id)->where('user_id', $userId)->first();
            if ($client) {
                $client->update([
                    'client_id'    => $clientId ?: $client->client_id,
                    'client_alias' => $clientAlias ?: $client->client_alias,
                    'notes'        => $notes !== null ? $notes : $client->notes,
                ]);

                return $client;
            }
        }

        return $this->model->updateOrCreate(
            ['user_id' => $userId, 'client_id' => $clientId],
            [
                'client_alias' => $clientAlias ?: $clientId,
                'notes'        => $notes,
            ]
        );
    }

    public function GetPaginatedClients($userId, $search = null, $perPage = 10)
    {
        $query = $this->model->where('clients.user_id', $userId);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('clients.client_id', 'LIKE', "%{$search}%")
                  ->orWhere('clients.client_alias', 'LIKE', "%{$search}%")
                  ->orWhere('clients.notes', 'LIKE', "%{$search}%");
            });
        }

        // Select client records and aggregate metrics from client_cases
        $casesSubquery = DB::table('client_cases')
            ->select(
                'client_id',
                'user_id',
                DB::raw('COUNT(id) as total_cases'),
                DB::raw('MAX(created_at) as last_case_date')
            )
            ->where('user_id', $userId)
            ->groupBy('client_id', 'user_id');

        $paginator = $query->leftJoinSub($casesSubquery, 'case_stats', function ($join) {
                $join->on('clients.client_id', '=', 'case_stats.client_id')
                     ->on('clients.user_id', '=', 'case_stats.user_id');
            })
            ->select(
                'clients.*',
                DB::raw('COALESCE(case_stats.total_cases, 0) as total_cases'),
                'case_stats.last_case_date'
            )
            ->orderBy('clients.updated_at', 'desc')
            ->paginate($perPage);

        return $paginator;
    }

    public function FindByClientId($userId, $clientId)
    {
        if (empty($clientId)) {
            return null;
        }

        return $this->model->where('user_id', $userId)
            ->where('client_id', $clientId)
            ->first();
    }

    public function DeleteClient($userId, $clientIdOrId)
    {
        $clientIdOrId = trim((string) $clientIdOrId);
        if ($clientIdOrId === '') {
            return false;
        }

        $client = $this->model->where('user_id', $userId)
            ->where(function ($q) use ($clientIdOrId) {
                if (is_numeric($clientIdOrId)) {
                    $q->where('id', (int) $clientIdOrId)
                      ->orWhere('client_id', $clientIdOrId);
                } else {
                    $q->where('client_id', $clientIdOrId);
                }
            })
            ->first();

        $stringClientId = $client ? $client->client_id : $clientIdOrId;

        $cases = ClientCase::where('user_id', $userId)
            ->where('client_id', $stringClientId)
            ->get();

        if (! $client && $cases->isEmpty()) {
            return false;
        }

        DB::beginTransaction();
        try {
            if ($cases->isNotEmpty()) {
                $caseIds = $cases->pluck('id')->toArray();
                AiJob::whereIn('case_id', $caseIds)->delete();
                ClientCase::whereIn('id', $caseIds)->delete();
            }

            if ($client) {
                $client->delete();
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
